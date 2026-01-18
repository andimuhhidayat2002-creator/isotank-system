# Filling Status API Testing Guide

## Test API Endpoints dengan curl/Postman

### 1. Get All Filling Statuses
```bash
curl -X GET "http://localhost/api/filling-statuses" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "code": "ready_to_fill",
      "description": "Ready to Fill",
      "color": "#4CAF50",
      "icon": "check_circle_outline"
    },
    {
      "code": "filled",
      "description": "Filled",
      "color": "#2196F3",
      "icon": "check_circle"
    },
    {
      "code": "under_maintenance",
      "description": "Under Maintenance",
      "color": "#FF9800",
      "icon": "build_circle"
    },
    {
      "code": "waiting_team_calibration",
      "description": "Waiting Team Calibration",
      "color": "#FFC107",
      "icon": "schedule"
    },
    {
      "code": "class_survey",
      "description": "Class Survey",
      "color": "#9C27B0",
      "icon": "assignment"
    }
  ]
}
```

### 2. Get Filling Status Statistics
```bash
curl -X GET "http://localhost/api/filling-statuses/statistics" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "code": "ready_to_fill",
      "description": "Ready to Fill",
      "count": 15,
      "color": "#4CAF50"
    },
    {
      "code": "filled",
      "description": "Filled",
      "count": 25,
      "color": "#2196F3"
    },
    {
      "code": null,
      "description": "No Status",
      "count": 10,
      "color": "#9E9E9E"
    }
  ]
}
```

### 3. Get Yard Positions (includes filling_status_code)
```bash
curl -X GET "http://localhost/api/yard/positions" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "placed": [
      {
        "id": 1,
        "slot_id": 123,
        "isotank": {
          "id": 45,
          "isotank_number": "KAYU1234",
          "current_cargo": "Chemical A",
          "filling_status": "Ready to Fill",
          "filling_status_code": "ready_to_fill",
          "status": "active",
          "activity": "STORAGE"
        }
      }
    ],
    "unplaced": [...],
    "stats": {...},
    "filling_status_stats": {
      "ready_to_fill": {
        "description": "Ready to Fill",
        "count": 5
      }
    }
  }
}
```

## Postman Collection

### Setup
1. Create new collection "Filling Status API"
2. Add environment variable `base_url` = `http://localhost/api`
3. Add environment variable `token` = your auth token

### Requests

#### 1. Get Filling Statuses
- **Method**: GET
- **URL**: `{{base_url}}/filling-statuses`
- **Headers**:
  - Authorization: `Bearer {{token}}`
  - Accept: `application/json`

#### 2. Get Statistics
- **Method**: GET
- **URL**: `{{base_url}}/filling-statuses/statistics`
- **Headers**:
  - Authorization: `Bearer {{token}}`
  - Accept: `application/json`

#### 3. Submit Inspection with Filling Status
- **Method**: POST
- **URL**: `{{base_url}}/inspector/jobs/{job_id}/submit`
- **Headers**:
  - Authorization: `Bearer {{token}}`
  - Accept: `application/json`
  - Content-Type: `application/json`
- **Body** (JSON):
```json
{
  "filling_status_code": "ready_to_fill",
  "filling_status_desc": "Ready to Fill",
  "surface": "good",
  "frame": "good",
  "is_draft": "false"
}
```

## Testing Checklist

- [ ] `/api/filling-statuses` returns all 5 status codes
- [ ] `/api/filling-statuses/statistics` shows correct counts
- [ ] `/api/yard/positions` includes `filling_status_code` in isotank data
- [ ] Yard map displays correct colors based on `filling_status_code`
- [ ] Inspector can select filling status in form
- [ ] Filling status saves to database correctly
- [ ] Dashboard shows filling status breakdown
- [ ] No status isotanks don't cause errors

## PowerShell Test Script (Windows)

```powershell
# Set your token
$token = "YOUR_TOKEN_HERE"
$baseUrl = "http://localhost/api"

# Test 1: Get Filling Statuses
Write-Host "Testing: Get Filling Statuses" -ForegroundColor Green
$response1 = Invoke-RestMethod -Uri "$baseUrl/filling-statuses" `
    -Headers @{
        "Authorization" = "Bearer $token"
        "Accept" = "application/json"
    } -Method Get
$response1 | ConvertTo-Json -Depth 10

# Test 2: Get Statistics
Write-Host "`nTesting: Get Statistics" -ForegroundColor Green
$response2 = Invoke-RestMethod -Uri "$baseUrl/filling-statuses/statistics" `
    -Headers @{
        "Authorization" = "Bearer $token"
        "Accept" = "application/json"
    } -Method Get
$response2 | ConvertTo-Json -Depth 10

# Test 3: Get Yard Positions
Write-Host "`nTesting: Get Yard Positions" -ForegroundColor Green
$response3 = Invoke-RestMethod -Uri "$baseUrl/yard/positions" `
    -Headers @{
        "Authorization" = "Bearer $token"
        "Accept" = "application/json"
    } -Method Get
$response3.data.filling_status_stats | ConvertTo-Json -Depth 10
```

## Expected Results

✅ All endpoints return `200 OK`
✅ Data structure matches expected format
✅ Colors are hex codes (#4CAF50, etc.)
✅ Counts are accurate
✅ No null/undefined errors

## Troubleshooting

### Error: 401 Unauthorized
- Check if token is valid
- Ensure user has correct role (admin/inspector/maintenance/management)

### Error: 500 Internal Server Error
- Check Laravel logs: `storage/logs/laravel.log`
- Verify database migration ran successfully
- Ensure `MasterIsotank::getValidFillingStatuses()` exists

### Empty Statistics
- Check if isotanks have `filling_status_code` set
- Run seeder or manually update some isotanks for testing

### Yard Map Not Showing Colors
- Clear browser cache
- Check browser console for JavaScript errors
- Verify `filling_status_code` is in API response
