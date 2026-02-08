
import sys
import os
import json
import sqlite3
import pandas as pd
import numpy as np
import warnings
import contextlib
from datetime import datetime, timedelta

warnings.filterwarnings("ignore")

class NpEncoder(json.JSONEncoder):
    def default(self, obj):
        if isinstance(obj, np.integer): return int(obj)
        if isinstance(obj, np.floating): return float(obj)
        if isinstance(obj, np.ndarray): return obj.tolist()
        return super(NpEncoder, self).default(obj)

def get_connection():
    try:
        base_dir = os.path.dirname(os.path.abspath(__file__))
        env_path = os.path.join(base_dir, '..', '.env')
        
        db_config = {}
        if os.path.exists(env_path):
            with open(env_path, 'r') as f:
                for line in f:
                    line = line.strip()
                    if not line or line.startswith('#') or '=' not in line: continue
                    k, v = line.split('=', 1)
                    db_config[k.strip()] = v.strip().strip('"').strip("'")
        
        conn_type = db_config.get('DB_CONNECTION', 'mysql')
        
        if conn_type == 'sqlite':
            db_path = os.path.join(base_dir, '..', 'database', 'database.sqlite')
            return sqlite3.connect(db_path), 'sqlite'
        
        import mysql.connector
        return mysql.connector.connect(
            host=db_config.get('DB_HOST', '127.0.0.1'),
            user=db_config.get('DB_USERNAME', 'root'), 
            password=db_config.get('DB_PASSWORD', ''),
            database=db_config.get('DB_DATABASE', 'isotank_db'),
            port=int(db_config.get('DB_PORT', 3306))
        ), 'mysql'
    except Exception as e:
        raise Exception(f"DB Connection Failed: {str(e)}")

def analyze_maintenance(conn, db_type):
    stats = {}
    
    # 1. Pareto Faults (Top Items)
    try:
        q_faults = "SELECT source_item, COUNT(*) as count FROM maintenance_jobs WHERE status='closed' GROUP BY source_item ORDER BY count DESC LIMIT 10"
        df_faults = pd.read_sql_query(q_faults, conn)
        df_faults = df_faults.replace({np.nan: None})
        stats['top_faults'] = df_faults.to_dict(orient='records')
    except:
        stats['top_faults'] = []
    
    # 2. Lemon Tanks (Problematic Units)
    try:
        date_filter = "DATE_SUB(NOW(), INTERVAL 12 MONTH)" if db_type == 'mysql' else "date('now', '-12 months')"
        q_lemons = f"""
            SELECT i.iso_number, i.id as isotank_id, i.manufacturer, COUNT(j.id) as job_count
            FROM maintenance_jobs j
            JOIN master_isotanks i ON j.isotank_id = i.id
            WHERE j.created_at >= {date_filter}
            GROUP BY i.id
            ORDER BY job_count DESC LIMIT 10
        """
        df_lemons = pd.read_sql_query(q_lemons, conn)
        df_lemons = df_lemons.replace({np.nan: None})
        stats['lemon_tanks'] = df_lemons.to_dict(orient='records')
    except:
        stats['lemon_tanks'] = []
    
    # 3. Monthly Spend/Count Trend
    try:
        q_trend = f"""
            SELECT 
                DATE_FORMAT(completed_at, '%Y-%m') as month,
                COUNT(*) as total_jobs,
                SUM(total_cost) as total_spend,
                AVG(DATEDIFF(completed_at, created_at)) as avg_mttr

            FROM maintenance_jobs
            WHERE status='closed' AND completed_at >= {date_filter}
            GROUP BY month
            ORDER BY month ASC
        """
        if db_type == 'sqlite':
            q_trend = f"SELECT strftime('%Y-%m', completed_at) as month, COUNT(*) as total_jobs, SUM(total_cost) as total_spend, AVG(julianday(completed_at) - julianday(created_at)) as avg_mttr FROM maintenance_jobs WHERE status='closed' AND completed_at >= {date_filter} GROUP BY month ORDER BY month ASC"

            
        df_trend = pd.read_sql_query(q_trend, conn)
        df_trend = df_trend.replace({np.nan: None})
        stats['monthly_trend'] = df_trend.to_dict(orient='records')
        
        # Summary KPI based on trend
        stats['completed_30d'] = int(df_trend.iloc[-1]['total_jobs']) if not df_trend.empty else 0
        stats['avg_mttr'] = f"{df_trend['avg_mttr'].mean():.1f}d" if not df_trend.empty else "N/A"
    except:
         stats['monthly_trend'] = []
         stats['completed_30d'] = 0
         stats['avg_mttr'] = "N/A"

    # KPI: Open Deferred
    try:
        cursor = conn.cursor()
        cursor.execute("SELECT COUNT(*) FROM maintenance_jobs WHERE status='open'")
        stats['total_open'] = cursor.fetchone()[0]
        
        cursor.execute("SELECT COUNT(*) FROM maintenance_jobs WHERE priority='deferred'")
        stats['deferred'] = cursor.fetchone()[0]
    except:
        stats['total_open'] = 0
        stats['deferred'] = 0
        
    return stats

def analyze_vacuum(conn, db_type):
    stats = {}
    date_filter = "DATE_SUB(NOW(), INTERVAL 6 MONTH)" if db_type == 'mysql' else "date('now', '-6 months')"
    date_filter_year = "DATE_SUB(NOW(), INTERVAL 12 MONTH)" if db_type == 'mysql' else "date('now', '-12 months')"
    
    # Live Data (Current Status) for KPI Cards
    # Use measurement status table directly for reliability
    try:
        q_live = """
            SELECT COUNT(*) as total, 
                   SUM(CASE WHEN vacuum_mtorr > 5 THEN 1 ELSE 0 END) as critical 
            FROM master_isotank_measurement_statuses
        """
        df_live = pd.read_sql_query(q_live, conn)
        live_total = int(df_live['total'].iloc[0]) if not df_live.empty else 0
        live_critical = int(df_live['critical'].iloc[0]) if not df_live.empty else 0
    except:
        live_total = 0
        live_critical = 0

    # Historical Rate Calculation
    # Does not throw exception but sets empty df on fail
    q_logs = f"""
        SELECT v.isotank_id, v.vacuum_value_mtorr, v.check_datetime, m.manufacturer
        FROM vacuum_logs v
        JOIN master_isotanks m ON v.isotank_id = m.id
        WHERE v.check_datetime >= {date_filter}
        ORDER BY v.isotank_id, v.check_datetime ASC
    """
    try:
        df = pd.read_sql_query(q_logs, conn)
    except:
        df = pd.DataFrame()

    tank_rates = []
    if not df.empty:
        try:
            df['check_datetime'] = pd.to_datetime(df['check_datetime'])
            
            for iso_id, group in df.groupby('isotank_id'):
                if len(group) < 2: continue
                
                last = group.iloc[-1]
                prev = group.iloc[-2]
                
                days = (last['check_datetime'] - prev['check_datetime']).days
                if days < 1: days = 1
                
                diff = last['vacuum_value_mtorr'] - prev['vacuum_value_mtorr']
                rate = diff / days
                
                if -5 < rate < 20: 
                     tank_rates.append({
                         'isotank_id': int(iso_id),
                         'manufacturer': str(last['manufacturer']) if last['manufacturer'] else 'Unknown',
                         'rate': float(rate),
                         'current_val': float(last['vacuum_value_mtorr'])
                     })
        except:
             pass # Skip rate calc on error
    
    df_rates = pd.DataFrame(tank_rates)
    
    # Best Manufacturer & Avg Rate
    avg_rise_rate_str = "N/A"
    best_manu = "N/A"
    
    if not df_rates.empty:
        try:
            avg_rise_rate = df_rates['rate'].mean()
            avg_rise_rate_str = f"{avg_rise_rate:.2f} mTorr/d"
            
            manu_perf = df_rates.groupby('manufacturer')['rate'].mean().reset_index()
            manu_perf = manu_perf.sort_values('rate', ascending=False)
            manu_perf = manu_perf.replace({np.nan: None})
            
            stats['manufacturers'] = {
                'labels': manu_perf['manufacturer'].tolist(),
                'data': manu_perf['rate'].round(2).tolist()
            }
            
            manu_grp = df_rates.groupby('manufacturer')['rate'].mean()
            if not manu_grp.empty:
                best_manu = manu_grp.idxmin()
        except:
             stats['manufacturers'] = {'labels': [], 'data': []}
    else:
        stats['manufacturers'] = {'labels': [], 'data': []}

    # Yearly Trend
    try:
        q_trend = f"""
            SELECT 
                DATE_FORMAT(check_datetime, '%Y-%m') as month, 
                AVG(vacuum_value_mtorr) as avg_val 
            FROM vacuum_logs 
            WHERE check_datetime >= {date_filter_year}
            GROUP BY month 
            ORDER BY month
        """
        if db_type == 'sqlite':
             q_trend = f"SELECT strftime('%Y-%m', check_datetime) as month, AVG(vacuum_value_mtorr) as avg_val FROM vacuum_logs WHERE check_datetime >= {date_filter_year} GROUP BY month ORDER BY month"
             
        df_trend = pd.read_sql_query(q_trend, conn)
        df_trend = df_trend.replace({np.nan: None})
        stats['yearly_trend'] = {
            'labels': df_trend['month'].tolist(),
            'data': df_trend['avg_val'].round(2).tolist()
        }
    except:
        stats['yearly_trend'] = {'labels': [], 'data': []}
    
    # Final Summary for Cards
    display_total_monitored = live_total if live_total > 0 else (df['isotank_id'].nunique() if not df.empty else 0)
    
    stats['summary'] = {
        'total_monitored': int(display_total_monitored),
        'critical_tanks': int(live_critical),
        'avg_rise_rate': avg_rise_rate_str,
        'best_manufacturer': str(best_manu)
    }
    
    return stats

def analyze_inspector(conn, db_type):
    stats = {}
    date_filter = "DATE_SUB(NOW(), INTERVAL 6 MONTH)" if db_type == 'mysql' else "date('now', '-6 months')"
    date_filter_vol = "DATE_SUB(NOW(), INTERVAL 6 YEAR)" if db_type == 'mysql' else "date('now', '-6 years')" # Longer range for volume to ensure data

    # 1. Volume by Inspector (Robust Join)
    # Using COALESCE for name
    try:
        q_vol = f"""
            SELECT COALESCE(u.name, 'Unknown') as inspector_name, COUNT(*) as count 
            FROM inspection_logs l
            LEFT JOIN users u ON l.inspector_id = u.id
            WHERE l.created_at >= {date_filter}
            GROUP BY inspector_name 
            ORDER BY count DESC LIMIT 10
        """
        df_vol = pd.read_sql_query(q_vol, conn)
        df_vol = df_vol.replace({np.nan: "Unknown"})
        stats['volume'] = {
            'labels': df_vol['inspector_name'].tolist(),
            'data': df_vol['count'].tolist()
        }
    except:
        stats['volume'] = {'labels': [], 'data': []}

    # 2. Trend (Volume over time)
    try:
        fmt = "%Y-%m"
        q_trend = f"""
            SELECT 
                DATE_FORMAT(created_at, '{fmt}') as month, 
                COUNT(*) as count 
            FROM inspection_logs 
            WHERE created_at >= {date_filter}
            GROUP BY month 
            ORDER BY month
        """
        if db_type == 'sqlite':
            q_trend = f"SELECT strftime('{fmt}', created_at) as month, COUNT(*) as count FROM inspection_logs WHERE created_at >= {date_filter} GROUP BY month ORDER BY month"
            
        df_trend = pd.read_sql_query(q_trend, conn)
        df_trend = df_trend.replace({np.nan: None})
        stats['trend'] = {
            'labels': df_trend['month'].tolist(),
            'data': df_trend['count'].tolist()
        }
    except:
        stats['trend'] = {'labels': [], 'data': []}
        
    return stats

def main():
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'No mode provided'}))
        sys.exit(1)
        
    mode = sys.argv[1]
    result = {'error': 'Execution failed without exception'} # Default error
    
    try:
        # Redirect stdout to devnull to suppress libraries noise, but catch errors
        with open(os.devnull, 'w') as devnull:
             with contextlib.redirect_stdout(devnull):
                 conn, db_type = get_connection()
                 
                 if mode == 'maintenance':
                     result = analyze_maintenance(conn, db_type)
                 elif mode == 'vacuum':
                     result = analyze_vacuum(conn, db_type)
                 elif mode == 'inspector':
                     result = analyze_inspector(conn, db_type)
                 
                 if conn: conn.close()
                 
    except Exception as e:
        result = {'error': f"Script Error: {str(e)}"}
    
    # Final Output
    print(json.dumps(result, cls=NpEncoder))

if __name__ == "__main__":
    main()
