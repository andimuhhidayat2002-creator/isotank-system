# ISOTANK SYSTEM - AI HANDOVER PROTOCOL
> **IMPORTANT FOR AI AGENT:** READ THIS BEFORE DOING ANYTHING RELATED TO DEPLOYMENT.

## 1. System Topology (The Triple Source of Truth)
We follow a strict **GIT-FLOW** sync looking like this:
`[Local PC]`  -> `(git push)` -> `[GitHub Main]` -> `(ssh git pull)` -> `[VPS Server]`

*   **Local Path:** `c:\laragon\www\isotank-system`
*   **GitHub Repo:** `andimuhhidayat2002-creator/isotank-system` (Branch: **main**)
*   **VPS Info:** IP `202.10.44.146` (User: `root`)
*   **VPS Path:** `/var/www/isotank-system` (Code lives inside `/api` subfolder!)

## 2. THE GOLDEN RULES (DO NOT VIOLATE)
1.  **NO ASSUMPTIONS:** Never assume the server is synced. ALWAYS run `VERIFY_SYNC.bat` first.
2.  **main BRANCH ONLY:** The server MUST run on `main`. Never switch to `master` or other branches.
3.  **NO MANUAL UPLOADS:** Do not use `scp` or `ftp` to patch PHP files individually unless completely unavoidable. Always Commit -> Push -> Pull.
4.  **SAFE MIGRATIONS:** Before running `php artisan migrate`, ensure a database backup exists.

## 3. Standard Operating Procedures (SOP)
*   **To Check Sync:** Run `.\VERIFY_SYNC.bat`
*   **To Deploy Code:** Run `.\deploy_to_vps.bat` (This script handles the git push -> ssh pull chain).
*   **If Server is Broken:** Refer to `.agent/workflows/deployment_protocol.md`

## 4. Current State (As of Jan 20, 2026)
*   Server successfully migrated to `api/` subfolder structure.
*   Server synced to generic GitHub `main`.
*   Flutter App uses `http://202.10.44.146/api` as Base URL.

---
*Created by Antigravity Agent to ensure consistency across sessions.*
