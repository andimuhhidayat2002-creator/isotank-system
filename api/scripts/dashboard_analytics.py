import sys
import os
import json
import sqlite3
import pandas as pd # Powerhouse for analytics

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
            'calibration_alerts': 0
        }

        # 1. Total Active Isotanks
        query_active = "SELECT COUNT(*) as count FROM master_isotanks WHERE status = 'active'"
        params_active = []
        if category != 'All':
            query_active += " AND tank_category = %s" if db_type == 'mysql' else " AND tank_category = ?"
            params_active.append(category)
        
        cursor.execute(query_active, params_active)
        result = cursor.fetchone()
        stats['total_active'] = result['count'] if result else 0

        # 2. Open Maintenance
        query_maint = """
            SELECT COUNT(*) as count 
            FROM maintenance_jobs m
            JOIN master_isotanks i ON m.isotank_id = i.id
            WHERE m.status IN ('open', 'on_progress')
        """
        params_maint = []
        if category != 'All':
            query_maint += " AND i.tank_category = %s" if db_type == 'mysql' else " AND i.tank_category = ?"
            params_maint.append(category)
            
        cursor.execute(query_maint, params_maint)
        result = cursor.fetchone()
        stats['open_maintenance'] = result['count'] if result else 0

        # 3. Deferred Maintenance
        query_deferred = """
            SELECT COUNT(*) as count 
            FROM maintenance_jobs m
            JOIN master_isotanks i ON m.isotank_id = i.id
            WHERE m.status = 'deferred'
        """
        params_deferred = []
        if category != 'All':
            query_deferred += " AND i.tank_category = %s" if db_type == 'mysql' else " AND i.tank_category = ?"
            params_deferred.append(category)
            
        cursor.execute(query_deferred, params_deferred)
        result = cursor.fetchone()
        stats['deferred_maintenance'] = result['count'] if result else 0

        # 4. Open Inspections
        query_insp = """
            SELECT COUNT(*) as count 
            FROM inspection_jobs j
            JOIN master_isotanks i ON j.isotank_id = i.id
            WHERE j.status IN ('open', 'in_progress')
        """
        params_insp = []
        if category != 'All':
            query_insp += " AND i.tank_category = %s" if db_type == 'mysql' else " AND i.tank_category = ?"
            params_insp.append(category)
            
        cursor.execute(query_insp, params_insp)
        result = cursor.fetchone()
        stats['open_inspections'] = result['count'] if result else 0

        # 5. Calibration Alerts
        # Date logic differs between MySQL and SQLite
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
        params_cal = []
        if category != 'All':
            query_cal += " AND i.tank_category = %s" if db_type == 'mysql' else " AND i.tank_category = ?"
            params_cal.append(category)
            
        cursor.execute(query_cal, params_cal)
        result = cursor.fetchone()
        stats['calibration_alerts'] = result['count'] if result else 0

        # === ADVANCED ANALYTICS (Using Pandas) ===
        # Load data into DataFrame for complex analysis
        
        # 6. Inspector Performance (Top 5 Inspectors by Volume This Month)
        if db_type == 'mysql':
            time_filter = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        else:
            time_filter = "WHERE created_at >= date('now', '-30 days')"
            
        sql_inspector = f"""
            SELECT u.name, COUNT(*) as report_count 
            FROM inspection_logs l 
            JOIN users u ON l.inspector_id = u.id 
            {time_filter}
            GROUP BY l.inspector_id 
            ORDER BY report_count DESC 
            LIMIT 5
        """
        # Pandas read_sql automatically handles column names
        df_inspectors = pd.read_sql_query(sql_inspector, conn)
        stats['top_inspectors'] = df_inspectors.to_dict(orient='records')
        
        # 7. Maintenance Efficiency (Mean Time To Repair - MTTR)
        # Only consider CLOSED jobs
        query_mttr = """
            SELECT created_at, updated_at 
            FROM maintenance_jobs 
            WHERE status = 'closed'
            ORDER BY updated_at DESC
            LIMIT 100 
        """
        # Limit 100 to keep it fast, or remove limit for full accuracy
        df_mttr = pd.read_sql_query(query_mttr, conn)
        
        if not df_mttr.empty:
            # Conversion to datetime
            df_mttr['created_at'] = pd.to_datetime(df_mttr['created_at'])
            df_mttr['updated_at'] = pd.to_datetime(df_mttr['updated_at'])
            
            # Calculate duration in days, allow fractions
            duration_hours = (df_mttr['updated_at'] - df_mttr['created_at']).dt.total_seconds() / 3600
            avg_hours = duration_hours.mean()
            
            # Formating logic
            if avg_hours < 24:
                stats['avg_repair_time'] = f"{round(avg_hours, 1)} Hours"
            else:
                stats['avg_repair_time'] = f"{round(avg_hours / 24, 1)} Days"
        else:
            stats['avg_repair_time'] = "N/A"

        
        print(json.dumps(stats))

    except Exception as e:
        print(json.dumps({"error": str(e)}))
    finally:
        if conn:
            conn.close()

if __name__ == "__main__":
    category_arg = 'All'
    if len(sys.argv) > 1:
        category_arg = sys.argv[1]
    
    calculate_stats(category_arg)
