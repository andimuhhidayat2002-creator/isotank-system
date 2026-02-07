
import sys
import os
import json
import sqlite3
import pandas as pd
import numpy as np
import warnings
from datetime import datetime, timedelta

warnings.filterwarnings("ignore")

class NpEncoder(json.JSONEncoder):
    def default(self, obj):
        if isinstance(obj, np.integer): return int(obj)
        if isinstance(obj, np.floating): return float(obj)
        if isinstance(obj, np.ndarray): return obj.tolist()
        return super(NpEncoder, self).default(obj)

def get_connection():
    env_path = os.path.join(os.path.dirname(__file__), '..', '.env')
    db_config = {}
    if os.path.exists(env_path):
        with open(env_path, 'r') as f:
            for line in f:
                if line.startswith('#') or '=' not in line: continue
                k, v = line.strip().split('=', 1)
                db_config[k] = v
    
    conn_type = db_config.get('DB_CONNECTION', 'mysql')
    if conn_type == 'sqlite':
        return sqlite3.connect(os.path.join(os.path.dirname(__file__), '..', 'database', 'database.sqlite')), 'sqlite'
    
    import mysql.connector
    return mysql.connector.connect(
        host=db_config.get('DB_HOST', '127.0.0.1'),
        user=db_config.get('DB_USERNAME', 'root'), 
        password=db_config.get('DB_PASSWORD', ''),
        database=db_config.get('DB_DATABASE', 'isotank'),
        port=int(db_config.get('DB_PORT', 3306))
    ), 'mysql'

def analyze_maintenance(conn, db_type):
    stats = {}
    
    # 1. Pareto Faults (Top Items)
    q_faults = "SELECT source_item, COUNT(*) as count FROM maintenance_jobs WHERE status='closed' GROUP BY source_item ORDER BY count DESC LIMIT 10"
    df_faults = pd.read_sql_query(q_faults, conn)
    df_faults = df_faults.replace({np.nan: None})
    stats['top_faults'] = df_faults.to_dict(orient='records')
    
    # 2. Lemon Tanks (Problematic Units)
    date_filter = "DATE_SUB(NOW(), INTERVAL 12 MONTH)" if db_type == 'mysql' else "date('now', '-12 months')"
    q_lemons = f"""
        SELECT i.iso_number, i.id as isotank_id, i.manufacturer, COUNT(*) as job_count
        FROM maintenance_jobs j
        JOIN master_isotanks i ON j.isotank_id = i.id
        WHERE j.created_at >= {date_filter}
        GROUP BY i.id
        ORDER BY job_count DESC LIMIT 10
    """
    df_lemons = pd.read_sql_query(q_lemons, conn)
    df_lemons = df_lemons.replace({np.nan: None})
    stats['lemon_tanks'] = df_lemons.to_dict(orient='records')
    
    # 3. Monthly Spend/Count Trend
    fmt = "%Y-%m"
    q_trend = f"""
        SELECT 
            strftime('{fmt}', created_at) as month, 
            COUNT(*) as count 
        FROM maintenance_jobs 
        WHERE created_at >= {date_filter}
        GROUP BY month 
        ORDER BY month
    """ if db_type == 'sqlite' else f"""
        SELECT 
            DATE_FORMAT(created_at, '{fmt}') as month, 
            COUNT(*) as count 
        FROM maintenance_jobs 
        WHERE created_at >= {date_filter}
        GROUP BY month 
        ORDER BY month
    """
    df_trend = pd.read_sql_query(q_trend, conn)
    df_trend = df_trend.replace({np.nan: None})
    stats['trend'] = {
        'labels': df_trend['month'].tolist(),
        'data': df_trend['count'].tolist()
    }
    
    # 4. Summary Statistics
    # Total Open Jobs
    q_open = "SELECT COUNT(*) as count FROM maintenance_jobs WHERE status IN ('open', 'in_progress')"
    df_open = pd.read_sql_query(q_open, conn)
    total_open = int(df_open['count'].iloc[0]) if not df_open.empty else 0
    
    # Deferred Jobs
    q_deferred = "SELECT COUNT(*) as count FROM maintenance_jobs WHERE status = 'deferred'"
    df_deferred = pd.read_sql_query(q_deferred, conn)
    deferred = int(df_deferred['count'].iloc[0]) if not df_deferred.empty else 0
    
    # Completed in last 30 days
    date_30d = "DATE_SUB(NOW(), INTERVAL 30 DAY)" if db_type == 'mysql' else "date('now', '-30 days')"
    q_completed = f"SELECT COUNT(*) as count FROM maintenance_jobs WHERE status = 'closed' AND completed_at >= {date_30d}"
    df_completed = pd.read_sql_query(q_completed, conn)
    completed_30d = int(df_completed['count'].iloc[0]) if not df_completed.empty else 0
    
    # Average MTTR (Mean Time To Repair) in hours
    q_mttr = f"""
        SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_hours
        FROM maintenance_jobs 
        WHERE status = 'closed' 
        AND completed_at IS NOT NULL
        AND created_at >= {date_30d}
    """ if db_type == 'mysql' else f"""
        SELECT AVG((julianday(completed_at) - julianday(created_at)) * 24) as avg_hours
        FROM maintenance_jobs 
        WHERE status = 'closed' 
        AND completed_at IS NOT NULL
        AND created_at >= {date_30d}
    """
    df_mttr = pd.read_sql_query(q_mttr, conn)
    avg_mttr_hours = df_mttr['avg_hours'].iloc[0] if not df_mttr.empty and df_mttr['avg_hours'].iloc[0] is not None else None
    
    if avg_mttr_hours:
        if avg_mttr_hours < 24:
            avg_mttr = f"{int(avg_mttr_hours)}h"
        else:
            avg_mttr = f"{int(avg_mttr_hours / 24)}d"
    else:
        avg_mttr = "N/A"
    
    stats['summary'] = {
        'total_open': total_open,
        'deferred': deferred,
        'completed_30d': completed_30d,
        'avg_mttr': avg_mttr
    }
    
    return stats

def analyze_vacuum(conn, db_type):
    stats = {}
    
    # 1. Manufacturer Performance (Avg Rate of Rise)
    # Strategy: Fetch last 2 valid readings for each tank to calculate curent rate
    # Then group by manufacturer
    
    date_filter = "DATE_SUB(NOW(), INTERVAL 6 MONTH)" if db_type == 'mysql' else "date('now', '-6 months')"
    
    q_logs = f"""
        SELECT v.isotank_id, v.vacuum_value_mtorr, v.check_datetime, m.manufacturer
        FROM vacuum_logs v
        JOIN master_isotanks m ON v.isotank_id = m.id
        WHERE m.status = 'active' 
        AND v.check_datetime >= {date_filter}
        ORDER BY v.isotank_id, v.check_datetime ASC
    """
    df = pd.read_sql_query(q_logs, conn)
    
    if df.empty:
        return {'manufacturers': [], 'worst_tanks': [], 'yearly_trend': {'labels': [], 'data': []}}

    df['check_datetime'] = pd.to_datetime(df['check_datetime'])
    
    # Calculate Rate per Tank
    tank_rates = []
    
    # Pre-calculate counts even for single readings
    total_monitored = df['isotank_id'].nunique() if not df.empty else 0
    latest_readings = df.sort_values('check_datetime').groupby('isotank_id').last()
    critical_count = len(latest_readings[latest_readings['vacuum_value_mtorr'] > 50]) if not latest_readings.empty else 0

    for iso_id, group in df.groupby('isotank_id'):
        if len(group) < 2: continue
        
        # Take last 2 readings
        last = group.iloc[-1]
        prev = group.iloc[-2]
        
        days = (last['check_datetime'] - prev['check_datetime']).days
        if days < 1: days = 1 # Avoid div by zero
        
        diff = last['vacuum_value_mtorr'] - prev['vacuum_value_mtorr']
        rate = diff / days
        
        # Filter noise
        if -5 < rate < 20: 
             tank_rates.append({
                 'isotank_id': int(iso_id),
                 'manufacturer': last['manufacturer'] if last['manufacturer'] else 'Unknown',
                 'rate': rate,
                 'current_val': last['vacuum_value_mtorr']
             })
             
    df_rates = pd.DataFrame(tank_rates)
    
    # Group by Manufacturer
    if not df_rates.empty:
        manu_perf = df_rates.groupby('manufacturer')['rate'].mean().reset_index()
        manu_perf = manu_perf.sort_values('rate', ascending=False) # Highest rate (worst) first
        manu_perf = manu_perf.replace({np.nan: None})
        stats['manufacturers'] = {
            'labels': manu_perf['manufacturer'].tolist(),
            'data': manu_perf['rate'].round(2).tolist()
        }
    else:
        stats['manufacturers'] = {'labels': [], 'data': []}
        
    # Yearly Trend (Avg Vacuum per Month)
    # Re-query simple aggregator
    date_filter_year = "DATE_SUB(NOW(), INTERVAL 12 MONTH)" if db_type == 'mysql' else "date('now', '-12 months')"
    fmt = "%Y-%m"
    q_trend = f"""
        SELECT 
            strftime('{fmt}', check_datetime) as month, 
            AVG(vacuum_value_mtorr) as avg_val 
        FROM vacuum_logs 
        WHERE check_datetime >= {date_filter_year}
        GROUP BY month 
        ORDER BY month
    """ if db_type == 'sqlite' else f"""
         SELECT 
            DATE_FORMAT(check_datetime, '{fmt}') as month, 
            AVG(vacuum_value_mtorr) as avg_val 
        FROM vacuum_logs 
        WHERE check_datetime >= {date_filter_year}
        GROUP BY month 
        ORDER BY month
    """
    df_trend = pd.read_sql_query(q_trend, conn)
    df_trend = df_trend.replace({np.nan: None})
    stats['yearly_trend'] = {
        'labels': df_trend['month'].tolist(),
        'data': df_trend['avg_val'].round(2).tolist()
    }
    
    # Summary Statistics
    # Used the pre-calculated total_monitored and critical_count
    
    # 3. Avg Rise Rate (from tank_rates calculated above)
    avg_rise_rate = df_rates['rate'].mean() if not df_rates.empty else 0
    avg_rise_rate_str = f"{avg_rise_rate:.2f} mTorr/d" if not df_rates.empty else "N/A"
    
    # 4. Best Manufacturer (lowest rise rate)
    best_manu = "N/A"
    if not df_rates.empty:
        manu_grp = df_rates.groupby('manufacturer')['rate'].mean()
        if not manu_grp.empty:
            best_manu = manu_grp.idxmin()
            
    stats['summary'] = {
        'total_monitored': int(total_monitored),
        'critical_tanks': int(critical_count),
        'avg_rise_rate': avg_rise_rate_str,
        'best_manufacturer': str(best_manu) if best_manu else "N/A"
    }
    
    return stats

def analyze_inspector(conn, db_type):
    stats = {}
    
    date_filter = "DATE_SUB(NOW(), INTERVAL 6 MONTH)" if db_type == 'mysql' else "date('now', '-6 months')"
    
    # 1. Total Inspections by Inspector
    q_vol = f"""
        SELECT inspector_name, COUNT(*) as count 
        FROM inspection_logs 
        WHERE created_at >= {date_filter}
        AND inspector_name IS NOT NULL AND inspector_name != ''
        GROUP BY inspector_name 
        ORDER BY count DESC LIMIT 10
    """
    df_vol = pd.read_sql_query(q_vol, conn)
    df_vol = df_vol.replace({np.nan: "Unknown"})
    stats['volume'] = {
        'labels': df_vol['inspector_name'].tolist(),
        'data': df_vol['count'].tolist()
    }
    
    # 2. Issues Found (Strictness) - Simplified Logic and Reduced Strictness on Query
    # Count how many details have condition NOT 'Good'
    q_issues = f"""
        SELECT l.inspector_name, 
               COUNT(d.id) as total_checks,
               SUM(CASE WHEN d.condition_value IN ('fail', 'monitor', 'damage', 'dirty', 'poor') THEN 1 ELSE 0 END) as issues_found
        FROM inspection_logs l
        JOIN inspection_log_details d ON l.id = d.inspection_id
        WHERE l.created_at >= {date_filter}
        AND l.inspector_name IS NOT NULL AND l.inspector_name != ''
        GROUP BY l.inspector_name
        ORDER BY issues_found DESC LIMIT 10
    """
    # Removed HAVING > 10 to ensure we get data even if few inspections exist
    
    # Alternative: Recent Activity Trend
    fmt = "%Y-%m"
    q_trend = f"""
        SELECT 
            strftime('{fmt}', created_at) as month, 
            COUNT(*) as count 
        FROM inspection_logs 
        WHERE created_at >= {date_filter}
        GROUP BY month 
        ORDER BY month
    """ if db_type == 'sqlite' else f"""
        SELECT 
            DATE_FORMAT(created_at, '{fmt}') as month, 
            COUNT(*) as count 
        FROM inspection_logs 
        WHERE created_at >= {date_filter}
        GROUP BY month 
        ORDER BY month
    """
    df_trend = pd.read_sql_query(q_trend, conn)
    df_trend = df_trend.replace({np.nan: None})
    stats['trend'] = {
        'labels': df_trend['month'].tolist(),
        'data': df_trend['count'].tolist()
    }

    return stats
    import contextlib
    
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'No mode specified'}))
        sys.exit(1)
        
    mode = sys.argv[1]
    
    # Suppress all stdout during processing to prevent noise
    with open(os.devnull, 'w') as devnull:
        with contextlib.redirect_stdout(devnull):
            conn = None
            result = {}
            try:
                conn, db_type = get_connection()
                if mode == 'maintenance':
                    result = analyze_maintenance(conn, db_type)
                elif mode == 'vacuum':
                    result = analyze_vacuum(conn, db_type)
                elif mode == 'inspector':
                    result = analyze_inspector(conn, db_type)
                else:
                    result = {'error': 'Invalid mode'}
            except Exception as e:
                result = {'error': str(e)}
            finally:
                if conn: 
                    try: conn.close()
                    except: pass
    
    # Print only the final JSON to actual stdout
    print(json.dumps(result, cls=NpEncoder))
