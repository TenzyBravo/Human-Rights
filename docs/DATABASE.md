# Database Documentation

Database: **`hrrams`** · Engine: **InnoDB** · Charset: **utf8mb4 / utf8mb4_unicode_ci**
23 tables and 1 view. Normalised to third normal form.

## Conventions

Every business table carries:

| Column | Purpose |
|---|---|
| `id` | Surrogate primary key. `BIGINT UNSIGNED` where high volume is expected. |
| `created_at` | Set by `DEFAULT CURRENT_TIMESTAMP`. |
| `updated_at` | Maintained by `ON UPDATE CURRENT_TIMESTAMP`. |
| `created_by` / `updated_by` | The acting user, where a person is responsible. Populated automatically by `Model::create()` / `updateById()`. |
| `deleted_at` | Soft deletion. `NULL` means live; every model query filters on it. |

Audit and history tables (`audit_logs`, `activity_logs`, `login_history`) are append-only and
deliberately have no `updated_at` or `deleted_at`.

---

## Table reference

### Access control

**`roles`** — the five roles. `slug` (unique) is what the code checks; `name` is what users
see. `is_staff` distinguishes internal roles from `citizen`.

**`permissions`** — 26 capability records grouped by `module` (reports, cases, awareness,
dashboard, admin, reporting). `slug` is the string passed to `can:` in routes.

**`role_permissions`** — the matrix. Composite primary key `(role_id, permission_id)`, both
foreign keys `ON DELETE CASCADE`.

**`user_roles`** — a user may hold several roles; effective permissions are the union.

### Users

**`users`** — accounts for every role. Notable columns:

| Column | Notes |
|---|---|
| `email` | Unique. Stored lower-case. |
| `password_hash` | bcrypt via `password_hash()`. |
| `national_id_enc` | `VARBINARY(512)`, AES-256-GCM. Never selected into a view. |
| `status` | `pending` → `active` → `suspended` / `locked`. |
| `email_verified_at`, `verify_token` | Verification flow. Token cleared on use. |
| `failed_attempts`, `locked_until` | Brute-force lockout. Reset on success. |
| `remember_token` | SHA-256 of the cookie value, never the value itself. |
| `twofa_secret`, `twofa_enabled` | Reserved for TOTP; columns exist, flow not yet built. |

**`password_resets`** — `token_hash` is a unique SHA-256; the plaintext token exists only in
the emailed link. One-hour `expires_at`, single-use via `used_at`.

**`login_history`** — every attempt with `email`, `ip_address`, `user_agent`, `successful`.
Keeps the email even when no user matched, so attacks on non-existent accounts are visible.

### Geography

**`provinces`** — the ten provinces, seeded. **`districts`** — child records, unique per
`(province_id, name)`, driving the cascading select via `/api/districts/{province}`.

### Reporting

**`report_categories`** — twelve seeded categories with `icon` (a Bootstrap Icons class) and
`is_active`. Categories already used by reports are deactivated rather than deleted.

**`reports`** — the complaint itself.

| Column | Notes |
|---|---|
| `reference_no` | Unique, `HR-YYYY-NNNNNN`, generated per year by `Report::nextReference()`. |
| `reporter_user_id` | `NULL` for anonymous or guest submissions. `ON DELETE SET NULL`. |
| `is_anonymous` | When 1, every identity column is left `NULL` at insert time. |
| `latitude` / `longitude` | `DECIMAL(10,7)` — about 1 cm precision, no float rounding. |
| `urgency` | Drives the case SLA: critical 3 days, high 7, medium 14, low the configured default. |
| `consent_given` | The consent declaration is mandatory and recorded per report. |
| `status` | Mirrors the case status so public tracking needs no join. |

Indexes: `status`, `category_id`, `province_id`, `reporter_user_id`, `created_at`, `deleted_at`.

### Case management

**`cases`** — one row per report, enforced by a unique key on `report_id`.

| Column | Notes |
|---|---|
| `case_number` | Unique, `CASE-YYYY-NNNNN`. |
| `officer_id` | Current owner. `NULL` until assigned; `ON DELETE SET NULL`. |
| `supervisor_id` | Whoever last assigned it. |
| `priority` | Seeded from the report's urgency; may be adjusted independently. |
| `due_at` | The SLA deadline. Anything past it with `resolved_at IS NULL` is overdue. |
| `resolved_at` / `closed_at` | Feed the average-resolution metric. |
| `resolution_summary` | Shared with the complainant on conclusion. |

**`case_assignments`** — full allocation history. On reassignment the previous row's
`unassigned_at` is stamped and a new row inserted; nothing is overwritten.

**`case_activities`** — the append-only investigation timeline. `type` covers `note`,
`status_change`, `assignment`, `evidence`, `interview`, `information_request`, `finding`,
`escalation` and `closure`. `old_value`/`new_value` record status transitions;
`scheduled_at` carries interview times; `is_internal = 0` marks entries a complainant may see.
Indexed on `(case_id, created_at)`.

### Attachments

**`attachments`** — polymorphic, keyed by `(entity_type, entity_id)` where `entity_type` is
`report`, `case_activity`, `campaign`, `resource` or `user`. `stored_name` is a relative path
under `storage/uploads`; `mime_type` is the sniffed type; `checksum` is SHA-256. One upload
pipeline and one authorisation check serve every entity.

### Awareness

**`awareness_campaigns`** — campaigns, news, articles, success stories and newsletters, split
by `type`. Unique `slug` for clean URLs; `status` is `draft`/`published`/`archived`; `views`
is incremented on each public read.

**`educational_resources`** — documents, videos, audio, images, links and training material.
Either a `file_path` (`attachment:{id}`) or an `external_url`. `downloads` counts opens.

**`events`** — community events with `venue`, `province_id`, `starts_at`/`ends_at`, `capacity`
and `status`.

**`comments`** — polymorphic over campaigns, resources and events, with `is_approved` for
moderation. The schema and model support it; no public submission form is exposed yet.

### Operations

**`notifications`** — per-user in-app messages with `channel` (`system`/`email`/`sms`),
`link`, `read_at` and `sent_at`. Indexed on `(user_id, read_at)`.

**`audit_logs`** — the security trail. `old_values`/`new_values` are `JSON`, redacted before
writing. Indexed on `(user_id, created_at)`, `action` and `(entity_type, entity_id)`.

**`activity_logs`** — one row per request: `summary`, `route`, `method`, `ip_address`.
The highest-growth table; prune it on a schedule.

**`settings`** — runtime configuration as key/value with `stype` (`string`/`int`/`bool`/`json`)
and `group_name` for the settings screen. Credentials and keys are **not** stored here — they
live in `config/config.php`.

---

## View

**`v_case_overview`** — encapsulates the five-table case join (case → report → category →
province → officer) and computes `age_days`. Use it for ad-hoc reporting so the join logic
stays in one place:

```sql
SELECT category_name, COUNT(*) AS total, ROUND(AVG(age_days), 1) AS avg_age
FROM v_case_overview
WHERE status NOT IN ('closed', 'resolved', 'rejected')
GROUP BY category_name
ORDER BY total DESC;
```

---

## Useful queries

**Open cases past their deadline, worst first**

```sql
SELECT case_number, title, officer_name, age_days
FROM v_case_overview
WHERE status NOT IN ('resolved', 'closed', 'rejected')
ORDER BY age_days DESC;
```

**Monthly submission volume for the last year**

```sql
SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS reports
FROM reports
WHERE deleted_at IS NULL AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
GROUP BY month ORDER BY month;
```

**Average resolution time by category**

```sql
SELECT rc.name, COUNT(c.id) AS concluded,
       ROUND(AVG(TIMESTAMPDIFF(DAY, c.opened_at, c.resolved_at)), 1) AS avg_days
FROM cases c
JOIN reports r            ON r.id = c.report_id
JOIN report_categories rc ON rc.id = r.category_id
WHERE c.resolved_at IS NOT NULL AND c.deleted_at IS NULL
GROUP BY rc.id ORDER BY avg_days DESC;
```

**Failed sign-in attempts by address in the last day**

```sql
SELECT email, ip_address, COUNT(*) AS attempts, MAX(created_at) AS latest
FROM login_history
WHERE successful = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
GROUP BY email, ip_address
HAVING attempts > 3
ORDER BY attempts DESC;
```

---

## Maintenance

**Prune request logs** (keep 90 days):

```sql
DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

Keep `audit_logs` far longer — it is the accountability record. Archive rather than delete.

**Clear expired reset tokens:**

```sql
DELETE FROM password_resets WHERE expires_at < NOW() OR used_at IS NOT NULL;
```

**Find attachment rows whose file has gone missing** — compare `stored_name` against the
filesystem before restoring from backup:

```sql
SELECT id, entity_type, entity_id, stored_name, size_bytes
FROM attachments WHERE deleted_at IS NULL ORDER BY created_at DESC;
```

**Check integrity after a restore:**

```sql
SELECT
  (SELECT COUNT(*) FROM reports WHERE deleted_at IS NULL) AS reports,
  (SELECT COUNT(*) FROM cases   WHERE deleted_at IS NULL) AS cases,
  (SELECT COUNT(*) FROM reports r
     LEFT JOIN cases c ON c.report_id = r.id
    WHERE c.id IS NULL AND r.deleted_at IS NULL)          AS reports_without_case;
```

The last figure should be zero — every report gets a case in the same transaction.
