# Latest Condition Buttons Logic Update (Vite Sync)

## Problem
The previous fix added external CDN links for DataTables Buttons, JSZip, and PDFMake. However, `resources/js/app.js` (bundled via Vite) **already imports these libraries** and assigns them to the global `window` object. 
This caused a "dependency conflict" and "race condition":
1.  **Duplicate Loading:** Multiple versions of DataTables/jQuery were trying to load.
2.  **Race Condition:** The inline script in `latest_inspections.blade.php` was executing *before* the huge `app.js` module bundle was fully downloaded and parsed. Result: `$` or `DataTable` was undefined when the code ran.

## Solution Implemented
1.  **Removed CDNs:** Deleted all `<script src="...">` and `<link>` tags related to DataTables/Buttons from the Blade view. We now rely 100% on the project's own `app.js`.
2.  **Polling Mechanism:** Implemented a robust `waitForDependencies()` function in the inline script.
    -   It checks for `window.jQuery`, `$.fn.DataTable`, `window.JSZip`, and `window.pdfMake` every 100ms.
    -   It **only** initializes the table once all these libraries are confirmed to be present.
3.  **Event Binding:** Used `.off('click').on('click')` to prevent duplicate event bindings if the script ever re-runs or re-initializes.

## Why This Fixes It
-   **Guaranteed Order:** No matter how slow `app.js` loads (network lag, large bundle), the table initialization code now patiently waits for it.
-   **Single Source of Truth:** We use the exact versions of libraries defined in `package.json`, preventing version mismatch errors (like "DataTable is not a function").
-   **No Redundant Requests:** The client browser doesn't need to fetch 5 extra JS/CSS files from external CDNs.

## Checklist for Validation
- [x] Search bar filters rows correctly.
- [x] Excel button downloads `.xlsx`.
- [x] PDF button generates a readable PDF (A1 Landscape).
- [x] No console errors about "DataTable is not defined" or "JSZip is missing".
