
import sys
import os
import json
import sqlite3
import pandas as pd
import numpy as np
import warnings
from datetime import datetime, timedelta

# Suppress warnings
warnings.filterwarnings("ignore")

class NpEncoder(json.JSONEncoder):
    def default(self, obj):
        if isinstance(obj, np.integer):
            return int(obj)
        if isinstance(obj, np.floating):
            return float(obj)
        if isinstance(obj, np.ndarray):
            return obj.tolist()
        return super(NpEncoder, self).default(obj)

def get_db_connection():
    # Load .env to find DB credentials (simple parsing)
    env_path = os.path.join(os.path.dirname(__file__), '..', '.env')
    db_config = {}
    if os.path.exists(env_path):
        with open(env_path, 'r') as f:
            for line in f:
                if line.startswith('#') or '=' not in line: continue
                key, val = line.strip().split('=', 1)
                db_config[key] = val

    connection = db_config.get('DB_CONNECTION', 'mysql')
    
    if connection == 'sqlite':
        db_path = os.path.join(os.path.dirname(__file__), '..', 'database', 'database.sqlite')
        return sqlite3.connect(db_path), 'sqlite'
    else:
        import mysql.connector
        return mysql.connector.connect(
            host=db_config.get('DB_HOST', '127.0.0.1'),
            user=db_config.get('DB_USERNAME', 'root'),
            password=db_config.get('DB_PASSWORD', ''),
            database=db_config.get('DB_DATABASE', 'isotank_system'),
            port=int(db_config.get('DB_PORT', 3306))
        ), 'mysql'

def run_analytics(report_type, target_date):
    stats = {}
    conn, db_type = get_db_connection()
    
    try:
        # Date objects
        date_obj = datetime.strptime(target_date, '%Y-%m-%d')
        
        # 1. MOVEMENT SUMMARY (Daily or Weekly)
        if report_type == 'daily':
            start_date = target_date
            end_date = target_date # Inclusive
            # Comparison (Previous Day)
            prev_start = (date_obj - timedelta(days=1)).strftime('%Y-%m-%d')
        else: # weekly
            # Start of week (Monday)
            start_of_week = date_obj - timedelta(days=date_obj.weekday())
            start_date = start_of_week.strftime('%Y-%m-%d')
            end_date = (start_of_week + timedelta(days=6)).strftime('%Y-%m-%d')
            prev_start = (start_of_week - timedelta(days=7)).strftime('%Y-%m-%d')

        # SQL Queries use param binding or f-string (safe here as date is validated)
        # INCOMING (Gate In)
        q_incoming = f"""
            SELECT i.tank_category, COUNT(*) as count
            FROM inspection_jobs j
            JOIN master_isotanks i ON j.isotank_id = i.id
            WHERE j.activity_type = 'incoming_inspection'
            AND DATE(j.created_at) BETWEEN '{start_date}' AND '{end_date}'
            GROUP BY i.tank_category
        """
        
        # OUTGOING (Official)
        q_outgoing = f"""
            SELECT i.tank_category, COUNT(*) as count
            FROM inspection_logs l
            JOIN master_isotanks i ON l.isotank_id = i.id
            WHERE l.inspection_type = 'outgoing_inspection'
            AND l.receiver_confirmed_at IS NOT NULL
            AND DATE(l.receiver_confirmed_at) BETWEEN '{start_date}' AND '{end_date}'
            GROUP BY i.tank_category
        """
        
        df_in = pd.read_sql_query(q_incoming, conn)
        df_out = pd.read_sql_query(q_outgoing, conn)
        
        # Process Summary Breakdown
        def format_breakdown(df):
            if df.empty: return "0"
            parts = []
            for _, row in df.iterrows():
                cat = row['tank_category'] if row['tank_category'] else 'Unknown'
                count = int(row['count'])
                parts.append(f"{cat}: {count}")
            return ", ".join(parts)

        stats['incoming_total'] = int(df_in['count'].sum()) if not df_in.empty else 0
        stats['incoming_desc'] = format_breakdown(df_in)
        
        stats['outgoing_total'] = int(df_out['count'].sum()) if not df_out.empty else 0
        stats['outgoing_desc'] = format_breakdown(df_out)
        
        # 2. STOCK LEVEL (Snapshot at End Date)
        q_stock = """
            SELECT tank_category, location, COUNT(*) as count
            FROM master_isotanks
            WHERE status = 'active'
            GROUP BY tank_category, location
        """
        df_stock = pd.read_sql_query(q_stock, conn)
        
        site_stock = df_stock[df_stock['location'] == 'SMGRS']
        other_stock = df_stock[df_stock['location'] != 'SMGRS']
        
        stats['stock_site'] = int(site_stock['count'].sum())
        stats['stock_site_desc'] = format_breakdown(site_stock)
        
        stats['stock_other'] = int(other_stock['count'].sum())
        stats['stock_other_desc'] = ", ".join([f"{r['location']}: {r['count']}" for _, r in other_stock.groupby('location')['count'].sum().reset_index().iterrows()])

        # 3. MAINTENANCE KPI
        # Open Jobs
        q_maint = "SELECT status, COUNT(*) as count FROM maintenance_jobs WHERE status IN ('open', 'on_progress', 'deferred') GROUP BY status"
        df_maint = pd.read_sql_query(q_maint, conn)
        stats['open_maintenance'] = int(df_maint['count'].sum())
        
        # Completed in Period
        q_maint_c = f"SELECT COUNT(*) as count FROM maintenance_jobs WHERE status = 'closed' AND DATE(updated_at) BETWEEN '{start_date}' AND '{end_date}'"
        df_maint_c = pd.read_sql_query(q_maint_c, conn)
        stats['completed_maintenance'] = int(df_maint_c['count'].iloc[0]) if not df_maint_c.empty else 0

        # 4. TREND ANALYSIS (Python Exclusive Feature)
        # Calculate growth/decline compared to previous period
        # We need historical data. 
        # For simplicity, we compare with a dummy static for now or just analyze the current composition.
        
        # Filling Status Distribution
        q_fill = "SELECT filling_status_code, COUNT(*) as count FROM master_isotanks WHERE status = 'active' GROUP BY filling_status_code"
        df_fill = pd.read_sql_query(q_fill, conn)
        
        fill_dist = {}
        for _, row in df_fill.iterrows():
            code = row['filling_status_code'] if row['filling_status_code'] else 'No Status'
            fill_dist[code] = int(row['count'])
        
        stats['filling_distribution'] = fill_dist

        # 5. GENERATE TEXT CHARTS (ASCII or HTML representation)
        # Since we can't easily send images, let's create a text-based bar chart for the email body.
        def text_bar_chart(data_dict):
            # Sort by value
            sorted_items = sorted(data_dict.items(), key=lambda item: item[1], reverse=True)
            if not sorted_items: return ""
            max_val = sorted_items[0][1]
            if max_val == 0: return ""
            
            output = []
            for k, v in sorted_items:
                bar_len = int((v / max_val) * 20)
                bar = '█' * bar_len
                label = k.replace('_', ' ').title()
                output.append(f"{label:<20} |{bar} {v}")
            return "\n".join(output)

        stats['ascii_chart_stock'] = text_bar_chart(fill_dist)
        
        # Format dates for display
        stats['date_range_display'] = date_obj.strftime('%d %b %Y') if report_type == 'daily' else f"{start_date} - {end_date}"

        print(json.dumps(stats, cls=NpEncoder))
        
    except Exception as e:
        print(json.dumps({"error": str(e)}))
    finally:
        conn.close()

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(json.dumps({"error": "Missing arguments"}))
    else:
        run_analytics(sys.argv[1], sys.argv[2])
