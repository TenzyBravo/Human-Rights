# Security Documentation

## 1. OWASP Top 10 (2021) control mapping

### A01 — Broken Access Control

- Every route declares its requirement in `config/routes.php`; `Middleware::apply()` enforces
  it **before** the controller runs. There is no route that reaches a controller unchecked.
- Permissions are resolved per request from the user's roles (`Auth::permissions()`), not
  cached in the session, so a revoked role takes effect on the next request.
- Record-level ownership is checked in addition to permissions:
  - `CaseController::authorisedCase()` — an officer without `case.view_any` may only open a
    case where `officer_id` matches their own id.
  - `ReportController::showMine()` — a citizen may only open their own report.
  - `CaseController::mayViewAttachment()` — evidence is released only to a case-wide permission
    holder, the reporter who filed it, or the assigned officer.
- Only `case.close` holders may set `resolved`, `closed` or `rejected`, so an officer cannot
  conclude their own investigation.
- An administrator cannot remove their own admin role or suspend their own account.
- Menu items and buttons use the same `can()` check as the gate, so the UI never advertises a
  forbidden action — but the gate, not the hidden button, is the control.
- Every refusal writes an `access.denied` audit entry with the required permission.

### A02 — Cryptographic Failures

- Passwords: `password_hash()` with bcrypt. Hashes are never selected into a view and are on
  the audit redaction list.
- Sensitive columns: AES-256-GCM through `Crypto` — authenticated encryption, so tampering is
  detected rather than silently accepted. Layout is `nonce(12) || tag(16) || ciphertext`.
- Reset and verification tokens: 32 bytes from `random_bytes()`. Reset tokens are stored only
  as a SHA-256 hash, so a database leak cannot be replayed. They expire in one hour and are
  single-use (`used_at`).
- Remember-me tokens are stored hashed and compared with `hash_equals()`.
- `app.force_https` redirects to TLS and emits HSTS; `security.cookie_secure` marks cookies
  secure.

### A03 — Injection

- Every query in the codebase is a prepared statement with bound parameters.
  `PDO::ATTR_EMULATE_PREPARES` is `false`, so binding happens server-side.
- Controllers never build SQL; all data access lives in models.
- The two places SQL identifiers are assembled — `Model::safeOrder()` and the
  `exists`/`unique` validation rules — whitelist against `^[a-zA-Z0-9_.]+$` patterns and
  return early on anything else. `ReportsController::groupBy()` receives only
  developer-supplied constants.
- Command execution: the backup routine passes credentials through `MYSQL_PWD` rather than the
  command line (keeping them out of the process list) and wraps every argument in
  `escapeshellarg()`.
- Header injection: the subject line in `Mailer::send()` has CR/LF stripped.

### A04 — Insecure Design

- Anonymous reporting is a first-class path, not an afterthought: when
  `is_anonymous` is set, identity fields are never persisted, and the interface states plainly
  that the reference number cannot be recovered.
- Reports and their case files are created in one transaction, so a complaint can never exist
  without a case.
- Evidence upload happens *after* the transaction commits, so a rejected file never discards an
  otherwise valid report.
- Deletion is soft throughout, so investigative and audit history survives account removal.
- Failure messages are uniform: login never reveals whether an account exists, and password
  reset returns the same confirmation either way.

### A05 — Security Misconfiguration

- Only `public/` is web-exposed. `app/`, `config/`, `database/` and `storage/` each carry a
  `Require all denied` `.htaccess`, and the root `.htaccess` rewrites everything into `public/`.
- `Options -Indexes -MultiViews`; dotfiles and `.sql`/`.log`/`.ini`/`.bak` are denied by
  `FilesMatch`.
- `display_errors` is off in production and driven by `app.debug`; errors always go to
  `storage/logs/php-error.log`.
- Machine-specific credentials live in `config/config.local.php`, which is gitignored — the
  tracked `config.php` contains only placeholders.
- Security headers are set in PHP (so they apply even without `mod_headers`) and again in
  `.htaccess`.

### A06 — Vulnerable and Outdated Components

- No Composer dependencies — the only third-party code is Bootstrap, Bootstrap Icons and
  Chart.js, loaded from a CDN with pinned versions and constrained by CSP.
- To remove the CDN dependency entirely, download those three files into
  `public/assets/vendor/` and update the four layout files.

### A07 — Identification and Authentication Failures

- Account lockout after `security.max_login_attempts` (default 5) for
  `security.lockout_minutes` (default 15), recorded as `account.locked`.
- An arithmetic CAPTCHA appears after two failed attempts in a session — accessible, and no
  third-party service or image processing involved.
- Password policy enforced server-side by the `password` rule: minimum length plus upper,
  lower, digit and symbol. The client-side meter is guidance only.
- Session id regenerated on every successful sign-in and rotated every
  `security.session_regenerate` seconds.
- Sessions are bound to a user-agent and language fingerprint; a mismatch destroys the session.
- Idle timeout of `security.session_timeout` seconds, with a browser warning two minutes out.
- Email verification is required before sign-in when
  `security.require_email_verification` is on.
- `login_history` records every attempt, successful or not, with IP and user agent. Users can
  review their own history from their profile.

### A08 — Software and Data Integrity Failures

- Uploads are verified by `finfo` MIME sniffing against an allow-list; the client-supplied type
  is ignored. Extensions are derived from the sniffed type, never from the filename.
- Stored filenames are 16 random bytes, so a crafted name cannot traverse or overwrite.
- A SHA-256 checksum is recorded for every attachment.
- Backup downloads accept only filenames matching `^hrrams-\d{8}-\d{6}\.sql$`.
- The audit log is append-only — there is no interface path that edits or deletes an entry.

### A09 — Security Logging and Monitoring Failures

Three complementary logs:

| Table | Captures |
|---|---|
| `audit_logs` | Security-relevant actions with before/after values, actor, IP, user agent |
| `activity_logs` | Every request — route, method, actor, IP |
| `login_history` | Every authentication attempt and its outcome |

Recorded actions include `login.success`, `login.failed`, `account.locked`, `logout`,
`user.registered`, `user.verified`, `user.created`, `user.updated`, `user.deleted`,
`user.roles_changed`, `user.status_changed`, `password.changed`, `password.reset`,
`password.reset_requested`, `report.submitted`, `case.assigned`, `case.status_changed`,
`case.activity.*`, `evidence.uploaded`, `evidence.viewed`, `role.permissions_changed`,
`category.*`, `settings.updated`, `awareness.*`, `report.exported`, `backup.created`,
`backup.downloaded`, `backup.failed`, `access.denied` and `csrf.failed`.

Passwords, hashes, tokens, 2FA secrets and national IDs are replaced with `[redacted]`
before an audit row is written. Audit writes are wrapped in try/catch so logging can never
break the request it is recording — a failure is written to `storage/logs/audit.log` instead.

### A10 — Server-Side Request Forgery

The application makes no outbound HTTP requests. The only user-supplied URL — a resource's
`external_url` — is validated with `FILTER_VALIDATE_URL`, stored, and only ever rendered as a
link carrying `rel="noopener noreferrer"`. It is never fetched server-side.

---

## 2. Cross-Site Scripting

- Every dynamic value in a view passes through `e()` (`htmlspecialchars` with `ENT_QUOTES`,
  `ENT_SUBSTITUTE`, UTF-8). Values are escaped at the point of output, not on input, so the
  database holds exactly what the user typed.
- Data embedded in HTML attributes for JavaScript uses `ejson()`, which applies
  `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` and then escapes the result.
- Editor-authored rich text goes through `safeHtml()`, which strips all tags outside a small
  allow-list and removes every attribute from those that survive — so no `onerror`, no
  `javascript:` href.
- A Content Security Policy restricts `default-src` to `'self'`, allows only the pinned CDN for
  scripts and styles, and sets `frame-ancestors 'self'`, `base-uri 'self'` and
  `form-action 'self'`.
- The legacy `X-XSS-Protection` filter is explicitly disabled (`0`) because it introduces
  vulnerabilities of its own; CSP supersedes it.

> **Known CSP gap.** `'unsafe-inline'` is still allowed for scripts and styles because the
> views carry small inline handlers. Tightening this to a nonce is the highest-value CSP
> improvement available — see §5.

---

## 3. Cross-Site Request Forgery

- A 32-byte per-session token is required on every `POST`, `PUT`, `PATCH` and `DELETE`.
  `Router::dispatch()` calls `Csrf::verify()` before any middleware or controller, so a route
  cannot forget it.
- Tokens are compared with `hash_equals()`. A failure returns 419, writes a `csrf.failed`
  audit entry, and does not process the request.
- Session cookies are `HttpOnly` with `SameSite=Lax`, and `Secure` under HTTPS.
- `View::back()` only follows a `Referer` on the application's own origin.

---

## 4. File upload security

| Control | Implementation |
|---|---|
| Type verification | `finfo` MIME sniff against `uploads.allowed_mimes`; client type ignored |
| Extension | Derived from the sniffed MIME, never from the uploaded filename |
| Size | Per-file cap from `uploads.max_bytes` (20 MB); PHP `upload_max_filesize` as a second gate |
| Count | `uploads.max_files` (10) per submission |
| Storage location | `storage/uploads/{entity}/{Y}/{m}/` — outside the document root |
| Filename | 16 random bytes, hex-encoded |
| Permissions | Files 0644, created directories 0775; the tree is HTTP-denied |
| Integrity | SHA-256 checksum stored per file |
| Serving | Only via `CaseController::attachment()` after an authorisation check, with `X-Content-Type-Options: nosniff`, `Content-Disposition` and a no-store cache policy |
| Audit | `evidence.uploaded` on write, `evidence.viewed` on every read |

---

## 5. Hardening checklist for production

- [ ] `app.debug = false`, `app.env = 'production'`
- [ ] Fresh `security.app_key` (`openssl rand -base64 32`), backed up securely
- [ ] All demo accounts deleted or re-passworded
- [ ] A dedicated MySQL user with only `SELECT, INSERT, UPDATE, DELETE` on `hrrams` — not root
- [ ] TLS certificate installed; `app.force_https = true`, `security.cookie_secure = true`
- [ ] HSTS header uncommented in `public/.htaccess`
- [ ] `mail.driver` switched from `log` to `smtp`
- [ ] `storage/`, `config/`, `app/` and `database/` confirmed to return 403 over HTTP
- [ ] `config/config.local.php` present and outside version control
- [ ] Automated off-server backups scheduled, and a restore rehearsed
- [ ] Log review scheduled — weekly for `access.denied` and `login.failed` clusters
- [ ] Consider replacing `'unsafe-inline'` in the CSP with a per-request nonce
- [ ] Consider enabling TOTP two-factor authentication for staff roles (the
      `users.twofa_secret` and `twofa_enabled` columns are already in place)
- [ ] Rate-limit `/login`, `/register` and `/report` at the web-server or WAF layer, since
      application-level lockout is per-account rather than per-IP

---

## 6. Data protection and confidentiality

- **Minimisation.** Anonymous reports store no identity fields at all. Nothing is collected
  "just in case".
- **Purpose limitation.** The consent declaration on the reporting form states exactly what
  the data will be used for, and consent is recorded per report (`consent_given`).
- **Access on need.** Complainant contact details appear only on the case detail page, only to
  the assigned officer or a case-wide permission holder, and every view is audited.
- **Public statistics are aggregate only.** `Report::publicStats()` and the statistics page
  return counts. No title, location, description or identity is ever published.
- **Tracking reveals status only.** The public tracker shows the state of a case, never its
  notes, evidence or officer.
- **Retention.** Soft deletion preserves the investigative record. Define a retention period
  with legal counsel and implement purging of `deleted_at` rows and old `activity_logs` as a
  scheduled task; the application does not assume a policy on your behalf.

---

## 7. Reporting a vulnerability

Email the address configured in **Administration → Settings → `contact_email`** with a
description, the affected URL, and reproduction steps. Please do not open a public issue for a
security defect.
