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

## 4. CRITICIAL ARCHITECTURE UPDATES (As of Jan 20, 2026 19:30)

### A. Multi-Category Support (T75, T11, T50)
*   Table `master_isotanks` now has `tank_category` column (Default: T75).
*   Table `inspection_items` has `applicable_categories` JSON column.
*   **API Logic:** `InspectionItemApiController` automatically filters items. If no `tank_category` param is provided, it defaults to **T75** (Backward Compatibility).

### B. Dynamic Receiver Validation
*   **Source of Truth:** Validation for Receiver Confirmation (`InspectionSubmitController`) is now **DYNAMIC**, sourced from `inspection_items` table.
*   **Do Not Hardcode:** Never revert to using `PdfGenerationService::getGeneralConditionItems()` for validation rules.

### C. Excel Import/Export
*   Templates now support `Tank Category` column.
*   Logic has been updated to parse this column or fallback to T75.
### D. Web Admin Dynamic Views (Jan 25, 2026)
*   **Global Dashboard:** Added Category Filter (All, T75, T11, T50) to filter all statistics (Active, Maintenance, Inspection, Alerts).
*   **Master Condition & Maintenance:** Now have filtering tabs (All, T75, T11, T50).
*   **Dynamic Columns:** `latest_inspections.blade.php` is refactored to dynamically render columns based on the selected category's inspection items.
*   **Legacy Hiding:** Hardcoded sections (IBOX, VACUUM, INSTRUMENTS, PSV) are **HIDDEN** for T11/T50 unless viewing 'All' or 'T75', or if data exists.

### E. Digital Signatures & PDF
*   **Signature Fix:** `User` model now correctly allows `signature_path` update.
*   **Conditional PDF:** `inspection_report.blade.php` now wraps legacy sections (IBOX, etc.) in checks. If the data is empty (as expected for T11/T50), the tables are hidden to keep the PDF clean.

### F. Migration Notes
*   **T11/T50 Items:** Specific items for T11 and T50 have been seeded.
*   **Category Logic:** Use `applicable_categories` in `InspectionItem` to control visibility.

---
*Last Updated: Jan 25, 2026 - Antigravity Agent*
