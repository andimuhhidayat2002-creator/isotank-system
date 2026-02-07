import sqlite3
import os
import json

def check_data():
    current_dir = os.path.dirname(os.path.abspath(__file__))
    db_path = os.path.join(current_dir, '..', 'database', 'database.sqlite')
    
    conn = sqlite3.connect(db_path)
    cursor = conn.cursor()
    
    print("=== DATA DIAGNOSTIC ===")
    
    # 1. Maintenance Statuses
    print("\n[Maintenance Jobs Status Distribution]")
    cursor.execute("SELECT status, COUNT(*) FROM maintenance_jobs GROUP BY status")
    rows = cursor.fetchall()
    if not rows:
        print("No maintenance jobs found.")
    for r in rows:
        print(f"Status: '{r[0]}' Count: {r[1]}")
        
    # 2. Inspection Logs Dates
    print("\n[Inspection Logs Date Range]")
    cursor.execute("SELECT MIN(created_at), MAX(created_at), COUNT(*) FROM inspection_logs")
    r = cursor.fetchone()
    print(f"Earliest: {r[0]}")
    print(f"Latest:   {r[1]}")
    print(f"Total:    {r[2]}")
    
    conn.close()

if __name__ == "__main__":
    check_data()
