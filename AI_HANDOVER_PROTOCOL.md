# ISOTANK SYSTEM - AI HANDOVER PROTOCOL
> **IMPORTANT FOR AI AGENT:** READ THIS BEFORE DOING ANYTHING RELATED TO DEPLOYMENT.

## 1. System Topology (The Two Sources of Truth)
We operate on **TWO SEPARATE** repositories. Always verify which one you are working on.

### A. Web System & API (Monorepo)
*   **Local Path:** `c:\laragon\www\isotank-system`
*   **GitHub Repo:** `andimuhhidayat2002-creator/isotank-system` (Branch: **main**)
*   **VPS Info:** IP `202.10.44.146` (User: `root`) -> Check `/var/www/isotank-system/api`

### B. Mobile App (Standalone Flutter)
*   **Local Path:** `c:\Users\USER\isotank_app`
*   **GitHub Repo:** `andimuhhidayat2002-creator/isotank-app`
*   **Note:** Do NOT use the `mobile/` folder inside the `isotank-system` monorepo. It is deprecated/out of sync.
*   **Build Output:** `build\app\outputs\flutter-apk\app-release.apk`

## 2. THE GOLDEN RULES (DO NOT VIOLATE)
1.  **NO ASSUMPTIONS:** Never assume the server is synced. ALWAYS run `VERIFY_SYNC.bat` first.
2.  **main BRANCH ONLY:** The server MUST run on `main`. Never switch to `master` or other branches.
3.  **NO MANUAL UPLOADS:** Do not use `scp` or `ftp` to patch PHP files individually unless completely unavoidable. Always Commit -> Push -> Pull.
4.  **SAFE MIGRATIONS:** Before running `php artisan migrate`, ensure a database backup exists.

## 3. Standard Operating Procedures (SOP)
*   **To Check Sync:** Run `.\VERIFY_SYNC.bat`
*   **To Deploy Code:** Run `.\deploy_to_vps.bat` (This script handles the git push -> ssh pull chain).
*   **If Server is Broken:** Refer to `.agent/workflows/deployment_protocol.md`

## 4. CRITICIAL ARCHITECTURE UPDATES (As of Jan 28, 2026 17:50)

### A. Multi-Category Support (T75, T11, T50)
*   Table `master_isotanks` now has `tank_category` column (Default: T75).
*   Table `inspection_items` has `applicable_categories` JSON column.
*   **API Logic:** `InspectionItemApiController` automatically filters items. If no `tank_category` param is provided, it defaults to **T75** (Backward Compatibility).

### B. Dynamic Receiver Validation
*   **Source of Truth:** Validation for Receiver Confirmation (`InspectionSubmitController`) is now **DYNAMIC**, sourced from `inspection_items` table (Filtered by `applicable_categories` to match the specific Tank Category).
*   **Do Not Hardcode:** Never revert to using `PdfGenerationService::getGeneralConditionItems()` for validation rules.

### C. Excel Import/Export
*   Templates now support `Tank Category` column.
*   Logic has been updated to parse this column or fallback to T75.
### D. Web Admin Dynamic Views (Jan 25, 2026)
*   **Global Dashboard:** Added Category Filter (All, T75, T11, T50) to filter all statistics (Active, Maintenance, Inspection, Alerts).
*   **Master Condition & Maintenance:** 'All' tab REMOVED. Default is now T75. Tabs: T75, T11, T50.
*   **Strict Category Display:** `isotanks/show.blade.php` and `inspection_show.blade.php` now STRICTLY filter items based on tank category. T75-specific sections (IBOX, Vacuum, Instruments, PSV) are totally hidden for T11/T50.
*   **T11 IBOX Integration:** IBOX readings (Temperature, Pressure, Level) for T11 are now integrated as **dynamic items** in Section C (Right Side) instead of being a standalone hardcoded section. This ensures they appear in the correct sequence (Section C) rather than jumping to Section F.
*   **Legacy Data Fallback:** Implemented `$legacyMap` to ensure old inspection data (e.g. key `frame` vs dynamic item `Frame`) displays correctly without "N/A" errors.
*   **Unified Reports:** Daily & Weekly Reports (Email) now show breakdown of Incoming, Outgoing, and Stock by **Category** (T75/T11/T50).

### E. Digital Signatures & PDF
*   **Signature Fix:** `User` model now correctly allows `signature_path` update.
*   **Conditional PDF:** `inspection_report.blade.php` now wraps legacy sections (IBOX, etc.) in checks. If the data is empty (as expected for T11/T50), the tables are hidden to keep the PDF clean.

### F. Migration Notes
*   **T11/T50 Items:** Specific items for T11 and T50 have been seeded.
*   **Category Logic:** Use `applicable_categories` in `InspectionItem` to control visibility.

### G. Stability Fixes (Jan 26, 2026)
*   **Null Safety:** Added null-safe operators and explicit checks for `$log->isotank` to prevent 500 errors on legacy logs or logs with missing relationships.
*   **Syntax Integrity:** Fixed blade syntax errors (duplicate `@endif`) in report templates.

### H. T11 Item Limitation (Jan 26, 2026 20:00)
*   **Strict Item Filter:** T11 inspection items are now strictly limited to the 14 items requested (matching standard inspection flow).
*   **Explicit Labels:** Data labels for T11 now explicitly include section prefixes (e.g., `FRONT:`, `REAR:`) to ensure no confusion in flat list views.
*   **Submission Sync:** `InspectionLog` now strictly requires a new submission (after Jan 26 19:30) for dynamic items to correctly populate database JSON.

### I. T50 Item Limitation (Jan 27, 2026 05:30)
*   **Strict Item Filter:** T50 inspection items are now strictly limited to the 27 items requested (matching standard inspection flow provided in photo).
*   **Explicit Labels:** Data labels for T50 now explicitly include section prefixes (e.g., `FRONT:`, `REAR:`, `RIGHT:`, `LEFT:`, `TOP:`) for parity with the T11 style.
*   **Submission Sync:** `InspectionLog` for T50 now expects the new prefixed labels for correct parsing.
*   **Technical Diagram:** Added T50 technical diagram (Rear, Side, Top view) to the PDF report specifically for T50 category tanks.
*   **Numeric Readings:** Added numeric input items for T50: Level Gauge (%), Thermometer (°C), and Pressure Gauge (MPa) placed below their respective condition items.
*   **Valve Box Addition:** Added "Valve Box" inspection items to both Left Side and Right Side sections for T50.
*   **Master Sync Fix:** Updated `InspectionSubmitController` to exclude numeric values from `MasterIsotankItemStatus` to prevent "Data truncated" errors (only condition strings are allowed in that table).

### J. Dynamic Item Management & Descriptive Headers (Jan 27, 2026 19:35)
*   **Full Headers:** Applied `$categoryMap` logic across all admin views (`inspection_show`, `isotanks/show`, `latest_inspections`) and PDF reports. Categories now show full titles (e.g., "B. GENERAL CONDITION") instead of single letters.
*   **Dynamic Category Selection:** The `Inspection Item` management page now features a dynamic category dropdown in Add/Edit modals.
    *   **Context Aware:** JavaScript is scoped to the active modal to prevent interference between items.
    *   **Real-time Swap:** Options change instantly when toggling Tank Type checkboxes (T75 = Standard, T11 = Position-based FRONT/REAR, T50 = Descriptive Position).
*   **Stability Re-engineering:**
    *   **Fixed 500 ParseError**: Resolved Blade engine conflict with JS comments.
    *   **Safety**: Added variable checks for T11/T50 crash prevention.

### K. Receiver Process & Data Integrity Fixes (Jan 28, 2026 17:50)
*   **Phantom Rejection Fix:** `receiverConfirm` endpoint now returns `all_accepted` boolean to prevent Flutter app from falsely showing "Items Rejected" message when everything is fine.
*   **Filling Status Logic:** For Outgoing inspections, `receiverConfirm` now correctly reads the `filling_status_code` from the **InspectionLog** (Inspector's input) instead of the stale `InspectionJob` data. This ensures the Master Isotank status updates correctly after confirmation.
*   **Key Normalization:** Fixed `updateMasterItemStatus` to robustly handle dynamic items with special characters (keys like `GPS/4G` are now correctly mapped to `GPS_4G` from the request). This resolves the issue where "GPS/4G/LP LAN" and "Pressure Regulator" were not updating in Admin Detail view.
*   **PDF Generation:** Confirmed Outgoing PDF generation is triggered upon Receiver Confirmation.

---
*Last Updated: Jan 28, 2026 17:50 - Antigravity Agent*

### M. Robust Data Lookup Strategy (Feb 1, 2026)
*   **Problem:** Mobile app and Legacy data often use inconsistent JSON keys (e.g. `vacuum_port_suction_condition` vs `port_suction` vs `Port Suction Condition`).
*   **Solution:** Logic in `inspection_show.blade.php` (Web) and `inspection_report.blade.php` (PDF) has been updated to use a **Multi-Key Fallback Strategy**.
*   **Legacy Map:** The T75 item `Port Suction Condition` is now explicitly mapped to the `vacuum_port_suction_condition` database column in the `$legacyMap` array, ensuring it behaves identically to other legacy T75 items.
*   **Vanilla JS Modal:** The photo modal in Inspection Detail view now uses **Vanilla JS**, removing dependency on `window.bootstrap` which was causing zoom issues.

### N. Data Privacy & Storage (Security Audit Feb 1, 2026)
*   **Status:** CONFIRMED SECURE.
*   **Configuration:** `filesystems.php` defines the `local` disk root as `storage_path('app')`. This directory is **ABOVE** the public web root.
*   **Access Control:** All photos are accessed strictly via the `MediaController` route (`/admin/media/{path}`) which is protected by the `auth:web` middleware in `routes/web.php`.
*   **No Symlinks:** There are no symbolic links exposing `storage/app/inspections` to `public/storage`.
*   **Conclusion:** Photos are NOT accessible via direct URL on the internet. They require a valid administrator login session to view.

### O. System Logic & Dashboard Integrity Updates (Feb 1, 2026 20:20)
*   **1. Maintenance Block Logic (Flutter):**
    *   **Relaxed:** If an item is "Not Good" BUT has an *existing* open maintenance ticket, `photo` & `remark` become optional (to prevent blocking). Warns user with orange box.
    *   **Robust Matching (Feb 2, 2026):** App now checks `source_item` against Code, Label, and Case-Insensitive variations to ensure existing tickets are detected even if naming differs slightly.
    *   **Strict:** If it is a NEW defect, photo/remark remains mandatory.
*   **2. Vacuum Validity Check:**
    *   **Strict:** Submission is now BLOCKED if `vacuum_check_datetime` is > 10 months old (Red error). Inspector MUST re-check.
*   **3. Dashboard Occupancy & Location:**
    *   **Fix:** Charts now count "Not Specified" / NULL status as **Empty**.
    *   **Dynamic:** Charts now support custom statuses (e.g. `filled m29`) derived from DB, instead of just hardcoded values.
*   **4. Filled Status Logic Protection (Server):**
    *   **Preservation:** If Admin wrote a specific status (e.g. `filled m29`) and Inspector selects generic "Filled", the specific status is **PRESERVED** (logic checks for substring).
    *   **Overwrite:** If Inspector selects a different state (e.g. "Empty"), it overwrites as normal.
    *   **Location Update (Receiver Confirm):** Isotank location now forcibly updates to the Destination from the Inspection Log (or Job plan). If missing, warns but does not block.
*   **5. Master List Optimization:**
    *   **Search Fix:** Changed pagination to `get()` (load all) on Master Isotank view to ensure client-side search finds ALL records, not just the first 50.
*   **6. Excel Import Fix:**
    *   Importing Inspection Excel with empty `filling_status` column no longer overwrites existing status with NULL. It preserves the existing value.

### P. Maintenance Excel Date Parsing Fix (Feb 2, 2026 20:35)
*   **Problem:** Maintenance Excel import was failing with "Trying to access array offset on false" error for all 1624 rows. Investigation revealed the issue was caused by overly strict date validation logic introduced to prevent a "2027 overflow bug".
*   **Root Cause:** The strict validation using `DateTime::getLastErrors()` was rejecting ALL valid dates from the Excel file, causing `$plannedDate` to remain null and default to `now()`. This made previously working Excel files fail completely.
*   **Solution:** Reverted to the original working date parsing logic (commit `318c012`) that uses `Carbon::createFromFormat()` with try-catch, but maintained the fix of prioritizing `m/d/Y` (American format) before `d/m/Y` to prevent incorrect date interpretation.
*   **Result:** Successfully imported 1617 maintenance jobs (7 errors from invalid/empty rows). Date range: 2024-02-08 to 2026-01-22. No more 2027 dates.
*   **Key Learning:** When fixing bugs, preserve the working logic and only change what's necessary. The strict validation was solving a problem that didn't exist in the actual Excel data.
*   **File Modified:** `api/app/Imports/MaintenanceImport.php`

### Q. Deployment Automation & SSH Security (Feb 2, 2026 22:00)
*   **SSH Key Setup:** Generated Ed25519 SSH key on local machine and added it to VPS `~/.ssh/authorized_keys`.
*   **Result:** Deployment scripts (`deploy_to_vps.bat`) and `ssh` commands now run without password prompts. This prevents the "hanging/stuck processes" issue caused by hidden password requests in background scripts.

### R. System Monitoring & Activity Logs (Feb 2, 2026 22:05)
*   **Audit Trail:** Created `activity_logs` table and `ActivityLog` model to record system-wide actions.
*   **Automatic Write Logging:** Implemented `LogActivity` middleware that automatically records all `POST`, `PUT`, `PATCH`, and `DELETE` requests across Web Admin and API.
    *   **Privacy Aware:** Automatically filters out sensitive fields like `password`, `_token`, and `signature_data` from being stored in logs.
*   **Media Access Audit:** Explicitly added logging to `MediaController@show`. Every time a private inspection photo is viewed, it records the User ID, File Path, IP Address, and Timestamp.
*   **Deployment Integrity:** Successful migration and deployment of logging features to VPS.

### S. Domain & SSL Security Deployment (Feb 2, 2026 22:45)
*   **Domain Name:** Registered and pointed `kayanconect.com` to VPS IP `202.10.44.146`.
*   **SSL Implementation:** Installed Certbot and issued a valid Let's Encypt SSL certificate for `kayanconect.com`.
*   **Security Hardening:** 
    *   Configured Nginx to force HTTP to HTTPS redirection.
    *   Updated Laravel `APP_URL` to `https://kayanconect.com`.
    *   Enabled HSTS and secure cookie flags.
*   **Result:** The system is now fully encrypted. All data transfer between users and the server is secure.

---
### T. Web Admin UI Refinement (Feb 4, 2026 06:40)
*   **Dark Mode Fixes:** Applied `bg-dark`, `text-white`, and `table-dark` classes to `isotanks/show.blade.php` and `inspection_show.blade.php` to resolve white-text-on-white-background issues.
*   **Login Redesign:** Replaced the logo image with clean text "ISOTANK MANAGEMENT SYSTEM" and subtext "PT Kayan LNG Nusantara" in `login.blade.php`.
*   **Grid Layout Logic:** Applied `align-items-start` to row containers in detail views to prevent vertical centering gaps caused by differing column heights.
*   **Tab System:** Optimized tab names (shortened) and CSS to prevents wrapping/breaking on smaller screens. Fixed a critical HTML structure bug (stray div) that was breaking the layout for the 'Vacuum' tab.

---
### U. T75 PDF Structure Fix (Feb 4, 2026 13:35)
*   **Problem:** "D. IBOX SYSTEM" section appeared twice in T75 PDFs - once from dynamic items loop (showing only "IBOX Condition") and again from hardcoded section (showing Battery, Pressure, Temp, Level), breaking the ABCDEFG sequence.
*   **Root Cause:** Dynamic items loop was rendering all categories including D, E, F, G, then hardcoded sections re-rendered D, E, F, G with detailed data.
*   **Solution:** 
    *   Added `@continue` filter in dynamic loop to skip categories `d`, `e`, `f`, `g` for T75 tanks.
    *   Consolidated "IBOX Condition" into the manual D. IBOX SYSTEM section as the first item.
    *   All T75 special sections (D. IBOX, E. INSTRUMENTS, F. VACUUM, G. PSV) now render only once with complete data.
*   **Result:** PDF now displays correct sequence: **A. DATA → B. GENERAL CONDITION → C. VALVES & PIPING → D. IBOX SYSTEM → E. INSTRUMENTS → F. VACUUM SYSTEM → G. SAFETY VALVES (PSV)**
*   **File Modified:** `api/resources/views/pdf/inspection_report.blade.php`

---
### V. Login Page Animated SVG Logo (Feb 4, 2026 13:50)
*   **Replacement:** Changed from plain text logo to custom animated SVG logo matching company branding.
*   **Design Elements:**
    *   **Hexagonal Frame** (green gradient) with double-layer and glow filter
    *   **ISO Tank Illustration** (silver/gray metallic) inside hexagon with detail rings
    *   **Top Tank Silhouette** (blue) in upper right corner
    *   **Text "ISOTANK"** - "ISO" (navy blue #1e3a8a) + "TANK" (dark green #2d5016)
    *   **Subtitle** "Management System" (gray)
    *   **Decorative Lines** (orange gradient)
    *   **Company Name** "PT. KAYAN LNG NUSANTARA" (navy blue)
*   **Animations Implemented:**
    *   Logo fade-in with scale on page load (1.2s)
    *   Hexagon pulse/breathing effect with glow (3s infinite loop)
    *   Tank metallic glow shimmer (4s infinite loop)
    *   Top tank floating animation (2.5s infinite loop)
*   **Result:** Premium, professional login experience matching the dark mode industrial theme
*   **File Modified:** `api/resources/views/auth/login.blade.php`

---
*Last Updated: Feb 4, 2026 13:50 - Antigravity Agent*


