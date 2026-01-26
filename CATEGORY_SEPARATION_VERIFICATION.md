# VERIFICATION CHECKLIST: Tank Category Separation (T75, T11, T50)

## Database Items Summary (from tinker output)

### T75 Items (Legacy + Dynamic)
**Hardcoded Sections (Only for T75):**
- Section D: IBOX System
- Section E: Instruments (Pressure Gauge, Level Gauge)
- Section F: Vacuum System
- Section G: PSV (PSV1, PSV2, PSV3, PSV4)

**Dynamic Items:** (Filtered by applicable_categories = ["T75"])
- Various items with codes starting with standard patterns

### T11 Items (Dynamic Only)
**Categories:**
- A. Front View
- B. Rear View
- C. Right Side (includes I-Box Battery)
- D. Left Side (includes Remote Closure, Document Holder)
- E. Top (includes Air Inlet, Top discharge, Walkway, Manlid, Relief Valve, Antenna/GPS/4G)

**Item Codes:** T11_A_01 through T11_E_09
**Applicable Categories:** ["T11"]

**Should NOT have:**
- ❌ Section D: IBOX System (T75 only)
- ❌ Section E: Instruments (T75 only)
- ❌ Section F: Vacuum System (T75 only)
- ❌ Section G: PSV (T75 only)

### T50 Items (Dynamic Only)
**Categories:**
- A. Front Out Side View
- B. Rear Out Side View (includes Name Plate, Ladder, Level Gauge, Grounding)
- C. Right Side/Valve Box Observation (includes Valve Liquid Phase, Gas Phase, Thermometer, Pressure Gauge, Release Valve)
- D. Left Side (includes Valve Liquid Phase, Gas Phase, Thermometer, Pressure Gauge, Release Valve)
- E. Top (includes Safety Valve 1, Safety Valve 2, Walkway)

**Item Codes:** T50_A_01 through T50_E_05
**Applicable Categories:** ["T50"]

**Should NOT have:**
- ❌ Section D: IBOX System (T75 only)
- ❌ Section E: Instruments (T75 only)
- ❌ Section F: Vacuum System (T75 only)
- ❌ Section G: PSV (T75 only)

## Code Changes Made

### 1. inspection_show.blade.php (Inspection Detail Report)
✅ Added `$tankCat = $log->isotank->tank_category ?? 'T75';`
✅ Dynamic items filtered by `applicable_categories`
✅ Section D (IBOX) wrapped with `@if($tankCat == 'T75')`
✅ Section E (Instruments) wrapped with `@if($tankCat == 'T75')`
✅ Section F (Vacuum) wrapped with `@if($tankCat == 'T75')`
✅ Section G (PSV) wrapped with `@if($tankCat == 'T75')`

### 2. isotanks/show.blade.php (Master Isotank Detail)
✅ Added `$tankCat = $isotank->tank_category ?? 'T75';`
✅ Dynamic items filtered by `applicable_categories`
✅ Section D (IBOX) wrapped with `@if($tankCat == 'T75')`
✅ Section E (Instruments) wrapped with `@if($tankCat == 'T75')`
✅ Section F (Vacuum) wrapped with `@if($tankCat == 'T75')`
✅ Section G (PSV) wrapped with `@if($tankCat == 'T75')`

### 3. latest_inspections.blade.php (Latest Condition Master)
✅ Removed "All" tab
✅ Default category set to T75
✅ Dynamic columns based on selected category
✅ Hardcoded sections (IBOX, Instruments, Vacuum, PSV) only shown for T75

## Verification Steps

### For T11 Tank:
1. Open Inspection Detail for a T11 tank
2. Should see: Front View, Rear View, Right Side, Left Side, Top sections
3. Should NOT see: IBOX, Instruments, Vacuum, PSV sections
4. Check Latest Condition Master (T11 tab)
5. Verify no T75-specific columns appear

### For T50 Tank:
1. Open Inspection Detail for a T50 tank
2. Should see: Front Out Side View, Rear Out Side View, Right Side/Valve Box, Left Side, Top sections
3. Should NOT see: IBOX, Instruments, Vacuum, PSV sections
4. Check Latest Condition Master (T50 tab)
5. Verify no T75-specific columns appear

### For T75 Tank:
1. Open Inspection Detail for a T75 tank
2. Should see: Dynamic sections + IBOX + Instruments + Vacuum + PSV
3. Check Latest Condition Master (T75 tab)
4. Verify all T75 columns appear correctly

## Next Deployment
```bash
git add .
git commit -m "Hide PSV section for T11 and T50 tanks"
git push origin main
ssh root@202.10.44.146 "cd /var/www/isotank-system/api && git pull origin main && php artisan view:cache"
```
