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

## 4. CRITICIAL ARCHITECTURE UPDATES (As of Jan 20, 2026 19:30)

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

---
*Last Updated: Jan 27, 2026 14:55 - Antigravity Agent*
