import sys
import os
import json
import sqlite3
import pandas as pd
import numpy as np
import warnings

# === 1. SUPPRESS ALL WARNINGS ===
# This is crucial because Pandas emits "UserWarning: pandas only supports SQLAlchemy..."
# which pollutes stdout and causes PHP json_decode() to fail.
warnings.filterwarnings("ignore")

# Ensure native types for JSON serialization
class NpEncoder(json.JSONEncoder):
    def default(self, obj):
        if isinstance(obj, np.integer): return int(obj)
        if isinstance(obj, np.floating): return float(obj)
        if isinstance(obj, np.ndarray): return obj.tolist()
        return super(NpEncoder, self).default(obj)

# Try to import mysql.connector, handle if missing
try:
    import mysql.connector
    MYSQL_AVAILABLE = True
except ImportError:
    MYSQL_AVAILABLE = False

def load_env():
    """Simple .env parser"""
    env_vars = {}
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__))) # api/
    env_path = os.path.join(base_dir, '.env')
    
    if os.path.exists(env_path):
        with open(env_path, 'r') as f:
            for line in f:
                line = line.strip()
                if not line or line.startswith('#'):
                    continue
                if '=' in line:
                    key, value = line.split('=', 1)
                    env_vars[key.strip()] = value.strip().strip('"').strip("'")
    return env_vars

def get_db_connection():
    env = load_env()
    connection_type = env.get('DB_CONNECTION', 'sqlite')
    
    if connection_type == 'sqlite':
        base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
        # Check DB_DATABASE env first, else use default path
        db_name = env.get('DB_DATABASE', 'database.sqlite')
        # If DB_DATABASE is just a filename, assume it's in database/
        # If it's a full path, use it.
        if os.path.isabs(db_name):
             db_path = db_name
        else:
             # If it's just 'database.sqlite', look in database/
             if db_name == 'database.sqlite' or db_name.endswith('.sqlite'):
                 db_path = os.path.join(base_dir, 'database', db_name)
             else:
                  # Logic for other cases? Defaulting to database/database.sqlite
                  db_path = os.path.join(base_dir, 'database', 'database.sqlite')
        
        if not os.path.exists(db_path):
             # Try absolute path from standard laravel structure
             db_path = os.path.join(base_dir, 'database', 'database.sqlite')

        return sqlite3.connect(db_path), 'sqlite'
        
    elif connection_type == 'mysql':
        if not MYSQL_AVAILABLE:
            raise Exception("MySQL connector not installed")
            
        return mysql.connector.connect(
            host=env.get('DB_HOST', '127.0.0.1'),
            user=env.get('DB_USERNAME', 'root'),
            password=env.get('DB_PASSWORD', ''),
            database=env.get('DB_DATABASE', 'laravel'),
            port=int(env.get('DB_PORT', 3306))
        ), 'mysql'
        
    else:
        raise Exception(f"Unsupported database connection: {connection_type}")

def calculate_stats(category='All'):
    conn = None
    try:
        conn, db_type = get_db_connection()
        cursor = conn.cursor(dictionary=True) if db_type == 'mysql' else conn.cursor()
        
        # SQLite cursor doesn't support dictionary=True natively in constructor, 
        # but we can set row_factory for similar behavior
        if db_type == 'sqlite':
            conn.row_factory = sqlite3.Row
            cursor = conn.cursor()

        stats = {
            'total_active': 0,
            'open_maintenance': 0,
            'deferred_maintenance': 0,
            'open_inspections': 0,
            'calibration_alerts': 0,
            'avg_repair_time': 'N/A',
            'repair_time_label': 'Maintenance Stats',
            'top_inspectors': [],
            'repair_time_label': 'Maintenance Stats',
            'top_inspectors': [],
            'problematic_tanks': [], # New: Most repaired tanks
            'vacuum_risks': [],
            'vacuum_stats': {} # New: Fleet health distribution
        }

        # SQL Helper helper to filter by category
        def with_category(base_sql, table_alias='i'):
            if category == 'All':
                return base_sql, []
            
            # For pandas/mysql connector compatibility, using params is safer but
            # simple string injection for 'T75'/'T11' etc is safe as they come from PHP router
            # valid categories: T75, T11, T50
            if category in ['T75', 'T11', 'T50']:
                if 'WHERE' in base_sql:
                    return f"{base_sql} AND {table_alias}.tank_category = '{category}'", []
                else:
                    return f"{base_sql} WHERE {table_alias}.tank_category = '{category}'", []
            return base_sql, []

        
        # 1. Total Active Isotanks
        query_active = "SELECT COUNT(*) as count FROM master_isotanks i WHERE i.status = 'active'"
        sql, _ = with_category(query_active, 'i')
        cursor.execute(sql)
        row = cursor.fetchone()
        stats['total_active'] = row['count'] if row else 0

        # 2. Open Maintenance
        query_maint = """
            SELECT COUNT(*) as count 
            FROM maintenance_jobs m
            JOIN master_isotanks i ON m.isotank_id = i.id
            WHERE m.status IN ('open', 'on_progress', 'in_progress')
        """
        sql, _ = with_category(query_maint, 'i')
        cursor.execute(sql)
        row = cursor.fetchone()
        stats['open_maintenance'] = row['count'] if row else 0

        # 3. Deferred Maintenance
        query_deferred = """
            SELECT COUNT(*) as count 
            FROM maintenance_jobs m
            JOIN master_isotanks i ON m.isotank_id = i.id
            WHERE m.status = 'deferred'
        """
        sql, _ = with_category(query_deferred, 'i')
        cursor.execute(sql)
        row = cursor.fetchone()
        stats['deferred_maintenance'] = row['count'] if row else 0

        # 4. Open Inspections
        query_insp = """
            SELECT COUNT(*) as count 
            FROM inspection_jobs j
            JOIN master_isotanks i ON j.isotank_id = i.id
            WHERE j.status IN ('open', 'in_progress')
        """
        sql, _ = with_category(query_insp, 'i')
        cursor.execute(sql)
        row = cursor.fetchone()
        stats['open_inspections'] = row['count'] if row else 0

        # 5. Calibration Alerts
        if db_type == 'sqlite':
            date_clause = "date(c.expiry_date) < date('now', '+1 month')"
        else:
            date_clause = "c.expiry_date < DATE_ADD(NOW(), INTERVAL 1 MONTH)"

        query_cal = f"""
            SELECT COUNT(DISTINCT c.isotank_id) as count 
            FROM master_isotank_components c
            JOIN master_isotanks i ON c.isotank_id = i.id
            WHERE {date_clause}
        """
        sql, _ = with_category(query_cal, 'i')
        cursor.execute(sql)
        row = cursor.fetchone()
        stats['calibration_alerts'] = row['count'] if row else 0

        # === ADVANCED ANALYTICS (Using Pandas) ===
        
        # 6. Inspector Performance (All Time Volume Leaderboard)
        # Removed 30 day filter to ensure data shows up even if no recent activity
        
        # Include ID for linking
        sql_inspector = """
            SELECT l.inspector_id, u.name, COUNT(*) as report_count 
            FROM inspection_logs l 
            JOIN users u ON l.inspector_id = u.id 
            JOIN master_isotanks i ON l.isotank_id = i.id
        """
        # Logic to add WHERE if category
        if category in ['T75', 'T11', 'T50']:
             sql_inspector += f" WHERE i.tank_category = '{category}'"
             
        sql_inspector += " GROUP BY l.inspector_id ORDER BY report_count DESC LIMIT 5"
        
        try:
            df_inspectors = pd.read_sql_query(sql_inspector, conn)
            # Ensure int type
            if not df_inspectors.empty:
                 df_inspectors['report_count'] = df_inspectors['report_count'].astype(int)
            stats['top_inspectors'] = df_inspectors.to_dict(orient='records')
        except Exception as e:
            # Fallback if query fails
            stats['top_inspectors'] = []


        # 7. Maintenance Efficiency (MTTR or Avg Age)
        # A. Try Closed Jobs (MTTR)
        query_mttr = """
            SELECT m.created_at, m.updated_at 
            FROM maintenance_jobs m
            JOIN master_isotanks i ON m.isotank_id = i.id
            WHERE m.status = 'closed'
        """
        if category in ['T75', 'T11', 'T50']:
             query_mttr += f" AND i.tank_category = '{category}'"
        query_mttr += " ORDER BY m.updated_at DESC LIMIT 100"

        try:
            df_mttr = pd.read_sql_query(query_mttr, conn)
            
            has_mttr = False
            if not df_mttr.empty:
                df_mttr['created_at'] = pd.to_datetime(df_mttr['created_at'])
                df_mttr['updated_at'] = pd.to_datetime(df_mttr['updated_at'])
                duration = (df_mttr['updated_at'] - df_mttr['created_at']).dt.total_seconds() / 3600
                avg_hours = duration.mean()
                
                if avg_hours > 0:
                    stats['repair_time_label'] = "MTTR (Closed Jobs)"
                    if avg_hours < 24:
                        stats['avg_repair_time'] = f"{round(avg_hours, 1)} Hours"
                    else:
                        stats['avg_repair_time'] = f"{round(avg_hours / 24, 1)} Days"
                    has_mttr = True

            # B. Fallback to Open Jobs Age
            if not has_mttr:
                query_open = """
                    SELECT m.created_at 
                    FROM maintenance_jobs m
                    JOIN master_isotanks i ON m.isotank_id = i.id
                    WHERE m.status IN ('open', 'on_progress', 'in_progress')
                """
                if category in ['T75', 'T11', 'T50']:
                    query_open += f" AND i.tank_category = '{category}'"
                
                df_open = pd.read_sql_query(query_open, conn)
                if not df_open.empty:
                    df_open['created_at'] = pd.to_datetime(df_open['created_at'])
                    now = pd.Timestamp.now()
                    duration = (now - df_open['created_at']).dt.total_seconds() / 3600
                    avg_hours = duration.mean()
                    
                    stats['repair_time_label'] = "Avg Age (Active Jobs)"
                    if avg_hours < 24:
                        stats['avg_repair_time'] = f"{round(avg_hours, 1)} Hours"
                    else:
                        stats['avg_repair_time'] = f"{round(avg_hours / 24, 1)} Days"
                else:
                    stats['repair_time_label'] = "Maintenance Data"
                    stats['avg_repair_time'] = "N/A" # Really no data
        except Exception as e:
             stats['avg_repair_time'] = "N/A"

        # 7.5 Problematic Isotanks (Most Maintenance Jobs)
        # Identify "Lemons" - tanks that return to maintenance often
        query_freq = """
            SELECT i.id as isotank_id, i.iso_number, COUNT(m.id) as job_count
            FROM maintenance_jobs m
            JOIN master_isotanks i ON m.isotank_id = i.id
            WHERE m.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        """ if db_type == 'mysql' else """
            SELECT i.id as isotank_id, i.iso_number, COUNT(m.id) as job_count
            FROM maintenance_jobs m
            JOIN master_isotanks i ON m.isotank_id = i.id
            WHERE m.created_at >= date('now', '-12 months')
        """
        
        if category in ['T75', 'T11', 'T50']:
             query_freq += f" AND i.tank_category = '{category}'"
        
        query_freq += " GROUP BY i.id ORDER BY job_count DESC LIMIT 3"

        try:
             df_freq = pd.read_sql_query(query_freq, conn)
             if not df_freq.empty:
                df_freq['job_count'] = df_freq['job_count'].astype(int)
                stats['problematic_tanks'] = df_freq.to_dict(orient='records')
        except Exception:
             stats['problematic_tanks'] = []


        # 8. Vacuum Analysis (Decay Rate)
        # Fetch vacuum logs for ACTIVE tanks, last 180 days
        date_cond = "v.check_datetime >= date('now', '-180 days')" if db_type == 'sqlite' else "v.check_datetime >= DATE_SUB(NOW(), INTERVAL 180 DAY)"
        
        query_vac = f"""
            SELECT v.isotank_id, v.vacuum_value_mtorr, v.check_datetime, i.iso_number
            FROM vacuum_logs v
            JOIN master_isotanks i ON v.isotank_id = i.id
            WHERE i.status = 'active'
            AND {date_cond}
        """
        if category in ['T75', 'T11', 'T50']:
             query_vac += f" AND i.tank_category = '{category}'"
             
        # Add order for easier pandas processing
        query_vac += " ORDER BY v.isotank_id, v.check_datetime ASC"
        
        try:
            df_vac = pd.read_sql_query(query_vac, conn)
            risks = []
            if not df_vac.empty:
                 df_vac['check_datetime'] = pd.to_datetime(df_vac['check_datetime'])
                 # Group by isotank
                 for iso_id, group in df_vac.groupby('isotank_id'):
                      if len(group) < 2: continue
                      
                      # Get last 3 points for trend
                      recent = group.tail(3)
                      if len(recent) < 2: continue

                      first = recent.iloc[0]
                      last = recent.iloc[-1]
                      
                      days = (last.check_datetime - first.check_datetime).days
                      if days < 1: days = 1 # Avoid div by zero
                      
                      diff = last.vacuum_value_mtorr - first.vacuum_value_mtorr
                      rate = diff / days # mTorr per day turnover
                      
                      current_val = last.vacuum_value_mtorr
                      
                      # Analysis thresholds:
                      # Rate > 0.05 mTorr/day is concerning
                      # OR current value approaching 8 mTorr
                      
                      # Prediction: Days until 8 mTorr
                      if rate > 0.02 and current_val < 30: # Only care if decaying and value is sane
                           days_to_fail = (8.0 - current_val) / rate
                           
                           # Only list if fail is imminent (e.g. within 60 days) or already failed (negative)
                           if days_to_fail < 60: 
                                risks.append({
                                    'isotank_id': int(first.isotank_id), # Add ID
                                    'iso_number': first.iso_number,
                                    'current_val': round(current_val, 2),
                                    'rate': round(rate, 3), # mTorr/day
                                    'days_to_fail': int(days_to_fail)
                                })
            
            # Sort: "Already Failed" (lowest negative) to "Imminent" (small positive)
            risks.sort(key=lambda x: x['days_to_fail'])
            stats['vacuum_risks'] = risks[:5]

            # 8.5 Vacuum Fleet Health (Distribution)
            # Latest vacuum reading for EACH tank
            # Group active tanks into health buckets
            if not df_vac.empty:
                 # Get latest reading per tank
                 latest_readings = df_vac.sort_values('check_datetime').groupby('isotank_id').tail(1)
                 
                 total_monitored = len(latest_readings)
                 if total_monitored > 0:
                     excellent = len(latest_readings[latest_readings['vacuum_value_mtorr'] < 3])
                     good = len(latest_readings[(latest_readings['vacuum_value_mtorr'] >= 3) & (latest_readings['vacuum_value_mtorr'] < 5)])
                     warning = len(latest_readings[latest_readings['vacuum_value_mtorr'] >= 5])
                     
                     stats['vacuum_stats'] = {
                         'total': total_monitored,
                         'excellent_pct': round((excellent / total_monitored) * 100),
                         'good_pct': round((good / total_monitored) * 100),
                         'warning_pct': round((warning / total_monitored) * 100),
                         'avg_value': round(latest_readings['vacuum_value_mtorr'].mean(), 2)
                     }
            
        except Exception as e:
            stats['vacuum_risks'] = []
            stats['vacuum_stats'] = {}

        # FINAL OUTPUT: MUST BE CLEAN JSON
        print(json.dumps(stats, cls=NpEncoder))

    except Exception as e:
        # Handle top level errors
        print(json.dumps({"error": str(e)}))
    finally:
        if conn:
            conn.close()

if __name__ == "__main__":
    category_arg = 'All'
    if len(sys.argv) > 1:
        category_arg = sys.argv[1]
    
    calculate_stats(category_arg)
