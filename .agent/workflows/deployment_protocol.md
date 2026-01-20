---
description: Strict SOP for deploying changes to the VPS safely
---

# Deployment Protocol (SOP)

Follow these steps EXACTLY to ensure Local, GitHub, and VPS remain synchronized.

## Phase 1: Pre-Deployment Check
1.  **Check Status:**
    Run the verification script to see if you are ahead/behind.
    ```powershell
    .\VERIFY_SYNC.bat
    ```
2.  **Analyze Output:**
    *   If Local and Server Hashes match: You are synced.
    *   If Local is ahead: You need to deploy.
    *   If Server has "Modified Files": **STOP**. Ask user if we can overwrite server changes.

## Phase 2: Deployment (The Standard Way)
Do not manually copy files. Use the git flow.

1.  **Stage & Commit (Local):**
    ```bash
    git add .
    git commit -m "Description of changes"
    ```
2.  **Push (Local -> GitHub):**
    ```bash
    git push origin main
    ```
3.  **Pull & Update (Server):**
    *Connect via SSH and execute:*
    ```bash
    ssh root@202.10.44.146 "cd /var/www/isotank-system/api && git pull origin main && php artisan migrate --force && php artisan cache:clear && php artisan config:clear"
    ```

    *Alternatively, use the automated batch script:*
    ```powershell
    .\deploy_to_vps.bat
    ```

## Phase 3: Emergency Recovery (If Out-of-Sync)
If `VERIFY_SYNC.bat` shows the server is on a different branch or totally messed up:

1.  **Run the Repair Tool:**
    ```powershell
    .\LAUNCH_REPAIR.bat
    ```
    *This script performs a hard reset to match GitHub main, while preserving .env and storage.*

## Phase 4: Flutter Verification
After any API deployment:
1.  Restart the Flutter App (to clear session/cache).
2.  Verify the feature works on the `http://202.10.44.146/api` endpoint.
