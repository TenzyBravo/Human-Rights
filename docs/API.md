# Internal JSON Endpoints

These endpoints exist to serve the application's own JavaScript. They are **not** a public API:
there is no token scheme, no versioning and no stability guarantee. They are authenticated by
the same session cookie and the same middleware as every other route.

Base URL is wherever the application is installed, e.g. `http://localhost:8888/hrrams`.

---

## `GET /api/districts/{province}`

Feeds the cascading province → district select on the reporting wizard, registration and
profile forms.

**Access:** public (reference data only).
**Parameter:** `province` — integer province id. A non-numeric value returns `400`.

```json
[
  { "id": 13, "province_id": 5, "name": "Chilanga" },
  { "id": 11, "province_id": 5, "name": "Chongwe" },
  { "id": 12, "province_id": 5, "name": "Kafue" },
  { "id": 10, "province_id": 5, "name": "Lusaka" }
]
```

An unknown province id returns an empty array, not an error.

---

## `GET /api/statistics`

Aggregate figures for the public statistics page. Counts only — no titles, locations,
descriptions or identities.

**Access:** public.

```json
{
  "byCategory": [ { "label": "Gender-Based Violence", "value": 184 } ],
  "byProvince": [ { "label": "Lusaka", "value": 402 } ],
  "byStatus":   [ { "label": "investigating", "value": 62 } ],
  "trend":      [ { "label": "2026-07", "value": 118 } ]
}
```

---

## `GET /api/dashboard/charts`

Every series the staff dashboards need, in one response, so several charts share a single
fetch.

**Access:** requires a signed-in user holding `officer`, `supervisor`, `awareness` or `admin`.
Unauthenticated requests are redirected to `/login`; an authenticated user without one of those
roles gets `403` and an `access.denied` audit entry.

```json
{
  "byCategory": [ { "label": "Arbitrary Arrest & Detention", "value": 96 } ],
  "byProvince": [ { "label": "Copperbelt", "value": 211 } ],
  "byStatus":   [ { "label": "assigned", "value": 34 } ],
  "trend":      [ { "label": "2026-08", "value": 91 } ],
  "heatmap":    [ { "province": "Lusaka", "category": "Torture & Inhuman Treatment", "value": 12 } ],
  "caseStats":  {
    "total": 1284, "active": 62, "resolved": 180, "closed": 214,
    "escalated": 2, "unassigned": 5, "overdue": 7,
    "avg_resolution_days": 21.4
  }
}
```

`charts.js` selects its series with `data-chart-key`, so adding a key here is backward
compatible.

---

## `POST /notifications/read`

Marks notifications read.

**Access:** any signed-in user (own notifications only — the user id comes from the session,
never from the request).

| Field | Required | Meaning |
|---|---|---|
| `_token` | yes | CSRF token |
| `id` | no | A single notification id. Omit to mark all read. |

With an AJAX `Accept` or `X-Requested-With` header:

```json
{ "ok": true, "unread": 0 }
```

Otherwise the user is redirected back to the referring page.

---

## Conventions

**Authentication.** Session cookie only. The cookie is `HttpOnly`, `SameSite=Lax`, and
`Secure` under HTTPS. There is no bearer-token path.

**CSRF.** Every non-GET request requires `_token` in the body or `X-CSRF-Token` as a header.
A mismatch returns `419` with `{"error": "Session expired. Please reload the page."}` and
writes a `csrf.failed` audit entry.

**Errors.**

| Status | Meaning |
|---|---|
| `400` | Malformed parameter |
| `403` | Authenticated but not permitted |
| `404` | No matching route |
| `419` | Missing or invalid CSRF token |
| `500` | Server error — logged to `storage/logs/error.log` |

Error bodies are `{"error": "…"}`. A JSON response is returned when the request sends
`Accept: application/json` or `X-Requested-With: XMLHttpRequest`; otherwise an HTML error page
is rendered.

**Rate limiting.** None at the application layer for these endpoints. Add it at the web server
or WAF if you expose the installation publicly.

---

## Adding an endpoint

1. Add the route in `config/routes.php` with the middleware it needs — never leave it off.
2. Return through `$this->json(...)` (`View::json`) so the content type and encoding flags are
   consistent.
3. Return only what the client needs. Every field you add is a field an attacker gets for free
   if authorisation is ever wrong.
4. Aggregate in SQL, not PHP — see `Report::byCategory()` for the pattern.
5. If the endpoint exposes anything case-related, add an `Audit::log()` call.

## Deliberate non-features

There is no external write API, no webhook dispatch and no third-party integration surface.
Complaints only enter the system through the reporting form, which means every submission
passes the same validation, consent capture, MIME verification and audit path. Adding an
ingestion API would need its own authentication, rate limiting and abuse handling designed
first — it is not a small change, and it should not be bolted onto these endpoints.
