# Teach Me — Mobile API (Model C)

Base URL: your Symfony host (e.g. `http://127.0.0.1:8000`).

All JSON responses:

```json
{
  "success": true,
  "message": "...",
  "data": {},
  "errors": {},
  "meta": { "apiVersion": "1.0" }
}
```

## Authentication

`POST /api/login`

```json
{ "email": "parent@example.com", "password": "secret" }
```

Response `data.token` → use as `Authorization: Bearer <token>`.

## Enrollments (Model C)

### Request enrollment

`POST /api/enrollments`

```json
{
  "studentId": 1,
  "courseId": 2,
  "tutorId": 3,
  "parentNote": "Prefer afternoon sessions"
}
```

Creates `status: pending`. Staff approves in **Admin → Enrollments**.

### List / detail / cancel

- `GET /api/enrollments`
- `GET /api/enrollments/{id}` — includes `sessions` when active
- `DELETE /api/enrollments/{id}` — pending only

### Child schedule (online classes)

`GET /api/my-learners/{id}/schedule`

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Grade 3 Reading – Session 1",
      "scheduledAt": "2026-05-22T16:00:00+08:00",
      "durationMinutes": 45,
      "meetingUrl": "https://meet.google.com/abc-defg-hij",
      "status": "scheduled",
      "tutor": { "id": 2, "fullName": "Rain D.", "specialty": "Reading" },
      "course": { "id": 1, "title": "Grade 3 Reading" }
    }
  ]
}
```

Parent taps **Join class** → open `meetingUrl` in browser.

## Dashboard

`GET /api/dashboard/customer`

Includes: `pendingEnrollmentsCount`, `activeEnrollmentsCount`, `nextSession`, `recentActivity`, `syncToken`.

## Staff workflow (web)

1. **Admin → Enrollments** — approve, assign tutor
2. **Schedule online class** — add `meetingUrl` (Zoom / Meet)
3. **Mark completed** after lesson

Parents see updates automatically via **long-poll** (no pull-to-refresh required). See `docs/REALTIME_SYNC.md`.

## Real-time sync (no manual reload)

### Long-poll (recommended)

`GET /api/sync/wait?token={syncToken}&learnersToken={learnersToken}&dashboardToken={dashboardToken}&timeout=25`

When `data.changed` is true:

- `data.learners` — updated children (if `learnersChanged`)
- `data.enrollments` — enrollment list (if `dashboardChanged`)
- `data.dashboard` — home stats, `nextSession`, `recentActivity` (if `dashboardChanged`)
- Update stored tokens from response.

Run this in a background loop while the app is open.

### Snapshot

`GET /api/sync` — same fields, immediate response.
