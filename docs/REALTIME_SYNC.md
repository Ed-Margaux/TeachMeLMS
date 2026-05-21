# Teach Me — Real-time sync (web admin + mobile)

No manual reload needed when the other side changes data. Uses **long-polling** (works with PHP built-in server, no WebSocket server required).

## How it works

1. Client stores a **sync token** (fingerprint of DB state).
2. Client calls **wait** endpoint; server holds the request up to ~25s until the token changes.
3. When `changed: true`, client applies new data (HTML partial on web, JSON on mobile).

## Mobile app (parent)

### Recommended: long-poll loop

After login, keep a background task:

```
GET /api/sync/wait?token={syncToken}&learnersToken={learnersToken}&dashboardToken={dashboardToken}&timeout=25
Authorization: Bearer <jwt>
```

When `data.changed === true`:

| Flag | Action |
|------|--------|
| `learnersChanged` | Replace learners list from `data.learners` |
| `dashboardChanged` | Update home from `data.dashboard`; replace enrollments from `data.enrollments` |
| either | Store new `syncToken`, `learnersToken`, `dashboardToken` |

Initial tokens: empty strings on first call, or from `GET /api/dashboard/customer` (`syncToken`).

### One-shot snapshot

`GET /api/sync?token=...&learnersToken=...&dashboardToken=...`

Same payload shape as wait, without blocking.

### Legacy

`GET /api/sync/learners` — still works; prefer `/api/sync/wait`.

## Web admin (staff)

Loaded automatically on every admin page (`public/js/admin-realtime-sync.js`).

- Long-poll: `GET /admin/sync/wait?token=...&timeout=25` (session cookie auth)
- On change: refresh all elements with `data-tm-sync-partial-url`
- Live pages today:
  - **Students** (`/students`)
  - **Enrollments** list and detail (`/admin/enrollments`)

Disable on a page: `<body data-tm-realtime="off">`.

## What triggers a change

| Action | Mobile sees | Admin sees |
|--------|-------------|------------|
| Parent adds child (mobile) | — | Students list updates |
| Staff edits student | Learners sync | Students list |
| Parent requests enrollment | — | Enrollments list |
| Staff approves / schedules class | Dashboard, enrollments, schedule | Enrollment detail |
| Staff marks session completed | Dashboard activity | Sessions table |

## Mobile implementation sketch (Flutter)

```dart
void startSyncLoop() async {
  while (authenticated) {
    final res = await api.get('/api/sync/wait', queryParameters: {
      'token': state.syncToken,
      'learnersToken': state.learnersToken,
      'dashboardToken': state.dashboardToken,
      'timeout': '25',
    });
    if (res.data['changed'] == true) {
      if (res.data['learnersChanged']) applyLearners(res.data['learners']);
      if (res.data['dashboardChanged']) {
        applyDashboard(res.data['dashboard']);
        applyEnrollments(res.data['enrollments']);
      }
      state.updateTokens(res.data);
    }
  }
}
```

Also call `onSyncChanged` when app returns to foreground.

## Note

This is **near real-time** (typically under 25 seconds). For instant push you would add Mercure/WebSockets later; long-poll is enough for the final project demo.
