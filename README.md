# ISOTANK INSPECTION SYSTEM

## System Overview

A comprehensive isotank inspection management system with:
- **Backend**: Laravel 11 API + MySQL
- **Frontend**: Flutter (Android-first, offline-capable)
- **Authentication**: Laravel Sanctum (Token-based)

## Architecture Principles

1. **Inspection logs are IMMUTABLE** (insert-only)
2. **Master tables** store ONLY latest summarized condition per isotank
3. **Master tables updated ONLY by backend system logic**
4. **Inspection NEVER closes maintenance**
5. **Database is the SINGLE SOURCE OF TRUTH**
6. **UI is NEVER a source of truth**

---

## Backend Setup

### Prerequisites
- PHP 8.2+
- MySQL 8.0+
- Composer
- Laravel 11

### Installation

```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
```

### Database Configuration

Update `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=isotank_system
DB_USERNAME=root
DB_PASSWORD=
```

### Run Migrations

```bash
php artisan migrate:fresh
php artisan db:seed --class=AdminUserSeeder
```

### Start Development Server

```bash
php artisan serve
```

API will be available at: `http://localhost:8000/api`

---

## Default Users

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@isotank.com | password |
| Inspector | inspector@isotank.com | password |
| Maintenance | maintenance@isotank.com | password |
| Management | management@isotank.com | password |

---

## API Endpoints

### Authentication

```
POST /api/login
POST /api/logout (protected)
GET /api/me (protected)
POST /api/register (admin only)
```

### Example Login Request

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "inspector@isotank.com",
    "password": "password"
  }'
```

Response:
```json
{
  "success": true,
  "token": "1|xxxxxxxxxxxxx",
  "user": {
    "id": 2,
    "name": "Inspector User",
    "email": "inspector@isotank.com",
    "role": "inspector"
  }
}
```

---

## Database Schema

### Core Tables

1. **master_isotanks** - Master isotank registry
2. **users** - System users with roles
3. **class_surveys** - Compliance tracking
4. **inspection_jobs** - Planned inspection activities
5. **inspection_logs** - Immutable inspection records (ALL items B-G)
6. **maintenance_jobs** - Maintenance tracking
7. **calibration_logs** - Calibration activities
8. **vacuum_logs** - Vacuum readings
9. **vacuum_suction_activities** - 5-day vacuum suction tracking
10. **master_isotank_item_status** - Dashboard summary
11. **excel_upload_logs** - Bulk upload audit trail

---

## Roles & Permissions

### Admin
- Web + API access
- Manage master isotanks (CRUD, activate/deactivate)
- Upload Excel bulk activities
- Create inspection/maintenance/calibration jobs
- Monitor all activities

### Inspector
- API only (Flutter app)
- View assigned inspection jobs
- Submit inspection forms
- Cannot create activities

### Maintenance
- API only (Flutter app)
- View assigned maintenance jobs
- Update maintenance status
- Upload before/after photos
- Cannot create activities

### Management
- API only (Flutter app)
- Read-only access
- View dashboards and reports

---

## Critical Business Rules

### Inspection Default Values

**Incoming Inspection:**
- Default values loaded from `master_isotank_item_status`

**Outgoing Inspection:**
- Default values loaded ONLY from MOST RECENT INCOMING inspection
- MUST NOT load from previous outgoing inspections

### Special Location Rule

When Admin creates INCOMING inspection job:
- `master_isotanks.location` MUST be FORCE-UPDATED to "SMGRS"
- Regardless of previous location
- Backend logic ONLY

### Maintenance Trigger Matrix

| Old Status | New Status | Trigger Maintenance? |
|------------|------------|---------------------|
| good | not_good | ✅ YES |
| good | need_attention | ✅ YES |
| need_attention | not_good | ✅ YES |
| not_good | not_good | ❌ NO (unless new evidence) |

### Vacuum Suction Activity

Triggered ONLY when:
- `vacuum_value > 8 mTorr`

---

## Inspection Items

### B. General Condition (10 items)
- Surface, Frame, Tank Plate, Venting Pipe, Explosion Proof Cover
- Grounding System, Document Container, Safety Label
- Valve Box Door, Valve Box Door Handle

### C. Valve & Pipe System (7 items)
- Valve Condition, Valve Position, Pipe & Joint
- Air Source Connection, ESDV, Blind Flange, PRV

### D. IBOX System (5 items)
- IBOX Condition, Pressure, Temperature, Level, Battery %

### E. Instrument (Multi-stage for Outgoing)
- Pressure Gauge (2 readings ≥ 6 hours apart)
- Level Gauge (2 readings)
- IBOX Temperature (2 readings)

### F. Vacuum System
- Vacuum value, Temperature, Check datetime

### G. PSV (1-4)
- Condition, Serial Number, Calibration Date, Valid Until
- Status (valid/expired/rejected)
- Replacement fields (if rejected)

### UI Symbol Mapping

| Stored Value | Display |
|--------------|---------|
| good | ✅ |
| not_good | ❌ |
| need_attention | ⚠️ |
| na | N/A |

---

## Excel Bulk Upload

### A. Incoming/Outgoing Inspection

Columns:
- `iso_number` (MANDATORY)
- `planned_date` (optional)
- `destination` (OUTGOING only)

### B. Maintenance

Columns:
- `iso_number` (MANDATORY)
- `item_name` (MANDATORY, must match inspection item)
- `description` (MANDATORY)
- `priority` (optional)
- `planned_date` (optional)

### C. Calibration

Columns:
- `iso_number` (MANDATORY)
- `item_name` (MANDATORY, calibratable items only)
- `description` (MANDATORY)
- `planned_date` (optional)
- `vendor` (optional)

---

## Development Status

✅ **Completed:**
- Database schema (all tables)
- Eloquent models (all relationships)
- Authentication (Sanctum)
- Role-based middleware
- Default user seeder

⏳ **In Progress:**
- API Controllers
- Business logic implementation
- Flutter app

---

## Testing

```bash
# Run tests
php artisan test

# Test API authentication
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@isotank.com","password":"password"}'
```

---

## License

Proprietary - All rights reserved

---

## Support

For questions or issues, contact the development team.
