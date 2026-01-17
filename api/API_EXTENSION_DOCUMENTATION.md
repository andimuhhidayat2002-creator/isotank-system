# API DOCUMENTATION - EXTENSION ENDPOINTS
## Maintenance, PDF Inspection, & Receiver Confirmation

---

## 🔐 AUTHENTICATION

All endpoints require Bearer token authentication via Laravel Sanctum.

```
Authorization: Bearer {token}
```

---

## 📋 RECEIVER ENDPOINTS

### 1. Get Inspection Details for Receiver

**GET** `/api/inspector/jobs/{id}/receiver-details`

**Roles:** `receiver`, `inspector`

**Description:** Get inspection details with general condition items for receiver to review before confirming.

**Response:**
```json
{
  "success": true,
  "data": {
    "job": {
      "id": 1,
      "activity_type": "outgoing_inspection",
      "destination": "Singapore",
      "receiver_name": "John Doe",
      "status": "open"
    },
    "isotank": {
      "id": 1,
      "iso_number": "ISO-001",
      "product": "Chemical A",
      "owner": "Company X",
      "location": "Jakarta"
    },
    "inspector": {
      "id": 2,
      "name": "Inspector Jane"
    },
    "inspection_date": "2026-01-10",
    "destination": "Singapore",
    "receiver_name": "John Doe",
    "items": [
      {
        "key": "surface",
        "name": "Surface",
        "inspector_condition": "good",
        "inspector_condition_formatted": "Good"
      },
      {
        "key": "frame",
        "name": "Frame",
        "inspector_condition": "not_good",
        "inspector_condition_formatted": "Not Good"
      }
      // ... 8 more items (total 10)
    ],
    "already_confirmed": false
  }
}
```

---

### 2. Submit Receiver Confirmation

**POST** `/api/inspector/jobs/{id}/receiver-confirm`

**Roles:** `receiver`, `inspector`

**Description:** Submit ACCEPT/REJECT decisions for all 10 general condition items.

**Request Body (multipart/form-data):**
```json
{
  "confirmations": {
    "surface": {
      "decision": "ACCEPT",
      "remark": "Looks good",
      "photo": <file> // optional
    },
    "frame": {
      "decision": "REJECT",
      "remark": "Damaged on left side",
      "photo": <file> // optional
    },
    "tank_plate": {
      "decision": "ACCEPT"
    },
    "venting_pipe": {
      "decision": "ACCEPT"
    },
    "explosion_proof_cover": {
      "decision": "ACCEPT"
    },
    "grounding_system": {
      "decision": "ACCEPT"
    },
    "document_container": {
      "decision": "ACCEPT"
    },
    "safety_label": {
      "decision": "ACCEPT"
    },
    "valve_box_door": {
      "decision": "ACCEPT"
    },
    "valve_box_door_handle": {
      "decision": "ACCEPT"
    }
  }
}
```

**Validation Rules:**
- `confirmations.{item}.decision`: required, must be "ACCEPT" or "REJECT"
- `confirmations.{item}.remark`: optional, string, max 500 characters
- `confirmations.{item}.photo`: optional, image file, max 5MB

**Response (All Accepted):**
```json
{
  "success": true,
  "message": "All items accepted. Inspection completed and location updated.",
  "data": {
    "all_accepted": true,
    "job_status": "done",
    "location_updated": true,
    "confirmations": [
      {
        "id": 1,
        "inspection_log_id": 5,
        "item_name": "surface",
        "inspector_condition": "good",
        "receiver_decision": "ACCEPT",
        "receiver_remark": "Looks good",
        "receiver_photo_path": "receiver_confirmations/abc123.jpg",
        "created_at": "2026-01-10T10:00:00Z"
      }
      // ... 9 more confirmations
    ],
    "pdf_path": "inspection_pdfs/inspection_outgoing_5_1736496000.pdf"
  }
}
```

**Response (Any Rejected):**
```json
{
  "success": true,
  "message": "Some items rejected. Inspection marked as receiver_rejected. Location NOT updated.",
  "data": {
    "all_accepted": false,
    "job_status": "receiver_rejected",
    "location_updated": false,
    "confirmations": [...],
    "pdf_path": "inspection_pdfs/inspection_outgoing_5_1736496000.pdf"
  }
}
```

**Error Response (Already Confirmed):**
```json
{
  "success": false,
  "message": "Failed to record confirmation: Receiver confirmations already exist for this inspection. They cannot be modified."
}
```

---

## 🔧 ADMIN MAINTENANCE ENDPOINTS

### 3. List Maintenance Jobs

**GET** `/api/admin/maintenance`

**Roles:** `admin`

**Query Parameters:**
- `status` (optional): Filter by status (open, on_progress, not_complete, closed)
- `isotank_id` (optional): Filter by isotank ID
- `page` (optional): Page number for pagination

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "isotank_id": 1,
        "source_item": "surface",
        "description": "Condition changed from good to not_good",
        "priority": "high",
        "planned_date": "2026-01-15",
        "status": "open",
        "before_photo": "maintenance/before_abc.jpg",
        "photo_during": null,
        "after_photo": null,
        "created_at": "2026-01-10T08:00:00Z",
        "isotank": {...},
        "creator": {...},
        "assignee": {...}
      }
    ],
    "per_page": 20,
    "total": 45
  }
}
```

---

### 4. Create Maintenance Job (Manual)

**POST** `/api/admin/maintenance`

**Roles:** `admin`

**Request Body (multipart/form-data):**
```json
{
  "isotank_id": 1,
  "source_item": "tank_plate",
  "description": "Rust detected during visual inspection",
  "priority": "high", // optional: low, medium, high, critical
  "planned_date": "2026-01-20", // optional
  "assigned_to": 5, // optional: user ID
  "before_photo": <file> // optional
}
```

**Validation Rules:**
- `isotank_id`: required, must exist in master_isotanks
- `source_item`: required, string, max 255 characters
- `description`: required, string
- `priority`: optional, must be one of: low, medium, high, critical
- `planned_date`: optional, valid date
- `assigned_to`: optional, must exist in users table
- `before_photo`: optional, image file, max 5MB

**Response:**
```json
{
  "success": true,
  "message": "Maintenance job created successfully",
  "data": {
    "id": 10,
    "isotank_id": 1,
    "source_item": "tank_plate",
    "description": "Rust detected during visual inspection",
    "priority": "high",
    "planned_date": "2026-01-20",
    "status": "open",
    "before_photo": "maintenance/before_xyz.jpg",
    "created_by": 1,
    "assigned_to": 5,
    "created_at": "2026-01-10T10:30:00Z",
    "isotank": {...},
    "creator": {...},
    "assignee": {...}
  }
}
```

---

### 5. Show Maintenance Job

**GET** `/api/admin/maintenance/{id}`

**Roles:** `admin`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "isotank_id": 1,
    "source_item": "surface",
    "description": "Condition changed from good to not_good",
    "priority": "high",
    "planned_date": "2026-01-15",
    "status": "on_progress",
    "before_photo": "maintenance/before_abc.jpg",
    "photo_during": "maintenance/during_abc.jpg",
    "after_photo": null,
    "work_description": "Cleaning and repainting in progress",
    "notes": "Using special coating",
    "created_by": 1,
    "assigned_to": 5,
    "completed_by": null,
    "completed_at": null,
    "triggered_by_inspection_log_id": 3,
    "sparepart": null,
    "qty": null,
    "created_at": "2026-01-10T08:00:00Z",
    "updated_at": "2026-01-10T14:00:00Z",
    "isotank": {...},
    "creator": {...},
    "assignee": {...},
    "completedBy": null,
    "triggeredByInspection": {...}
  }
}
```

---

### 6. Update Maintenance Job

**PUT** `/api/admin/maintenance/{id}`

**Roles:** `admin`

**Request Body:**
```json
{
  "description": "Updated description",
  "priority": "critical",
  "planned_date": "2026-01-18",
  "assigned_to": 6,
  "notes": "Urgent - requires immediate attention"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Maintenance job updated successfully",
  "data": {...}
}
```

---

### 7. Update Maintenance Status

**PUT** `/api/admin/maintenance/{id}/status`

**Roles:** `admin`

**Description:** Update maintenance status. Only maintenance activity can close maintenance.

**Request Body (multipart/form-data):**
```json
{
  "status": "closed",
  "work_description": "Cleaned and repainted surface. Applied protective coating.",
  "photo_during": <file>, // optional
  "after_photo": <file>, // optional
  "sparepart": "Protective coating XYZ", // optional
  "qty": 2 // optional
}
```

**Validation Rules:**
- `status`: required, must be one of: open, on_progress, not_complete, closed
- `work_description`: optional, string (required if status = closed)
- `photo_during`: optional, image file, max 5MB
- `after_photo`: optional, image file, max 5MB
- `sparepart`: optional, string
- `qty`: optional, integer

**Response:**
```json
{
  "success": true,
  "message": "Maintenance status updated successfully",
  "data": {
    "id": 1,
    "status": "closed",
    "work_description": "Cleaned and repainted surface. Applied protective coating.",
    "photo_during": "maintenance/during_abc.jpg",
    "after_photo": "maintenance/after_abc.jpg",
    "sparepart": "Protective coating XYZ",
    "qty": 2,
    "completed_at": "2026-01-10T16:00:00Z",
    "completed_by": 1,
    ...
  }
}
```

---

## 📄 PDF ENDPOINTS

### 8. Upload PDF (Existing - Updated)

**POST** `/api/inspector/jobs/{id}/upload-pdf`

**Roles:** `inspector`, `receiver`

**Description:** Upload PDF report for inspection. Note: PDFs are now auto-generated, but this endpoint remains for manual uploads if needed.

**Request Body (multipart/form-data):**
```json
{
  "pdf": <file> // PDF file, max 10MB
}
```

**Response:**
```json
{
  "success": true,
  "message": "PDF uploaded successfully",
  "data": {
    "pdf_path": "inspection_pdfs/manual_upload_123.pdf",
    "pdf_url": "http://localhost:8000/storage/inspection_pdfs/manual_upload_123.pdf"
  }
}
```

---

## 🔄 INSPECTION SUBMISSION (Updated)

### 9. Submit Inspection

**POST** `/api/inspector/jobs/{id}/submit`

**Roles:** `inspector`

**Description:** Submit inspection. Now auto-generates PDF for incoming inspections.

**Response (Updated):**
```json
{
  "success": true,
  "message": "Inspection submitted successfully",
  "data": {
    "inspection_log": {...},
    "job_status": "done",
    "pdf_path": "inspection_pdfs/inspection_5_1736496000.pdf" // NEW
  }
}
```

---

## 📊 GENERAL CONDITION ITEMS

The following 10 items are used for receiver confirmation:

1. `surface` - Surface
2. `frame` - Frame
3. `tank_plate` - Tank Plate
4. `venting_pipe` - Venting Pipe
5. `explosion_proof_cover` - Explosion Proof Cover
6. `grounding_system` - Grounding System
7. `document_container` - Document Container
8. `safety_label` - Safety Label
9. `valve_box_door` - Valve Box Door
10. `valve_box_door_handle` - Valve Box Door Handle

---

## ⚠️ IMPORTANT NOTES

1. **Receiver Confirmations are IMMUTABLE:**
   - Once submitted, they cannot be modified or deleted
   - Attempting to submit again will return an error

2. **Location Update Logic:**
   - Location is updated ONLY if ALL 10 items are ACCEPTED
   - If ANY item is REJECTED, location is NOT updated
   - Job status becomes `receiver_rejected` instead of `done`

3. **PDF Auto-Generation:**
   - Incoming inspections: PDF generated on submission
   - Outgoing inspections: PDF generated after receiver confirmation
   - PDFs are stored in `storage/app/public/inspection_pdfs/`

4. **Maintenance Closure:**
   - ONLY maintenance activity can close maintenance
   - Inspection NEVER closes maintenance
   - Closing requires `work_description` and sets `completed_at` timestamp

5. **Photo Handling:**
   - All photos are stored in `storage/app/public/`
   - Receiver confirmation photos: `receiver_confirmations/`
   - Maintenance photos: `maintenance/`
   - Inspection photos: `inspections/`

---

## 🧪 TESTING EXAMPLES

### Test Receiver Confirmation (All Accept)

```bash
curl -X POST http://localhost:8000/api/inspector/jobs/1/receiver-confirm \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "confirmations": {
      "surface": {"decision": "ACCEPT"},
      "frame": {"decision": "ACCEPT"},
      "tank_plate": {"decision": "ACCEPT"},
      "venting_pipe": {"decision": "ACCEPT"},
      "explosion_proof_cover": {"decision": "ACCEPT"},
      "grounding_system": {"decision": "ACCEPT"},
      "document_container": {"decision": "ACCEPT"},
      "safety_label": {"decision": "ACCEPT"},
      "valve_box_door": {"decision": "ACCEPT"},
      "valve_box_door_handle": {"decision": "ACCEPT"}
    }
  }'
```

### Test Receiver Confirmation (With Reject)

```bash
curl -X POST http://localhost:8000/api/inspector/jobs/1/receiver-confirm \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "confirmations": {
      "surface": {"decision": "REJECT", "remark": "Damaged"},
      "frame": {"decision": "ACCEPT"},
      "tank_plate": {"decision": "ACCEPT"},
      "venting_pipe": {"decision": "ACCEPT"},
      "explosion_proof_cover": {"decision": "ACCEPT"},
      "grounding_system": {"decision": "ACCEPT"},
      "document_container": {"decision": "ACCEPT"},
      "safety_label": {"decision": "ACCEPT"},
      "valve_box_door": {"decision": "ACCEPT"},
      "valve_box_door_handle": {"decision": "ACCEPT"}
    }
  }'
```

### Test Manual Maintenance Creation

```bash
curl -X POST http://localhost:8000/api/admin/maintenance \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "isotank_id": 1,
    "source_item": "tank_plate",
    "description": "Rust detected during visual inspection",
    "priority": "high",
    "planned_date": "2026-01-20"
  }'
```

---

**Last Updated:** 2026-01-10  
**API Version:** 1.0 (Extension)
