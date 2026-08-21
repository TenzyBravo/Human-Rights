# HRRAMS — Human Rights Reporting & Awareness Management System

A secure, responsive web platform for promoting human rights awareness, receiving and
managing human-rights complaints, tracking investigations to resolution, and publishing
awareness campaigns.

Built with **PHP 8+ (MVC, no framework dependency)**, **MySQL 8+**, **Bootstrap 5** and
**Chart.js**. Runs on Apache under MAMP, XAMPP, WAMP or LAMP.

---

## Quick start

```bash
# 1. Create the database and load the schema + demo data
mysql -u root -p < database/schema.sql
mysql -u root -p hrrams < database/seed.sql
```

```bash
# 2. Point the app at your database (copy, then edit the copy)
cp config/config.php config/config.local.php
```

Set `db.port` to `8889` for MAMP or `3306` for XAMPP/WAMP/LAMP, and replace
`security.app_key` with a fresh value:

```bash
openssl rand -base64 32
```

```bash
# 3. Make runtime directories writable
chmod -R 775 storage
```

Then serve the project so that **`public/` is the document root** and open it in a browser.
See [docs/INSTALLATION.md](docs/INSTALLATION.md) for per-server instructions.

### Demo accounts

Seeded for evaluation. **Delete or change every one of these before going live.**

| Role | Email | Password |
|---|---|---|
| System Administrator | `admin@hrrams.test` | `Admin@12345` |
| Supervisor | `supervisor@hrrams.test` | `Super@12345` |
| Human Rights Officer | `officer@hrrams.test` | `Officer@12345` |
| Awareness Officer | `awareness@hrrams.test` | `Aware@12345` |
| Registered Citizen | `citizen@hrrams.test` | `Citizen@12345` |

---

## What it does

**Public** — awareness campaigns, news, success stories, downloadable resources, community
events, aggregate statistics, a four-step reporting wizard (with anonymous submission), and
reference-number case tracking. No account required to report.

**Citizen** — report history, per-report timeline of public updates, printable
acknowledgement receipt, profile management, notifications.

**Officer** — dashboard of assigned cases with KPI tiles and charts, case timeline, notes,
evidence upload, interview scheduling, information requests, status updates.

**Supervisor** — assignment and reassignment with live workload figures, escalation,
approval and closure, departmental statistics, officer performance reporting.

**Awareness Officer** — campaign, news, resource and event management, plus engagement
analytics.

**Administrator** — user and role management, a live permission matrix, report categories,
system settings, the full audit log, and database backup.

---

## Project layout

```
app/
  Core/          Kernel, router, auth, session, CSRF, validation, uploads, mail, audit, crypto
  Controllers/   Request handling — one controller per module
  Models/        Data access; every query is a prepared statement
  Views/         Templates (layouts, partials, per-module views)
config/
  config.php     Application configuration (config.local.php overrides it, untracked)
  routes.php     The route table, with per-route middleware
database/
  schema.sql     Normalised MySQL schema, 23 tables + a reporting view
  seed.sql       Roles, permissions, provinces, categories, settings, demo content
docs/            Installation, architecture, ERD, database, security, manuals, deployment
public/          The ONLY web-exposed directory — front controller and static assets
storage/         Evidence uploads, logs and backups — denied to HTTP, .gitignored
```

---

## Security summary

Prepared statements throughout; output escaped at the point of use with `e()`; per-session
CSRF tokens on every state-changing request; bcrypt password hashing; account lockout after
repeated failures; session fingerprinting, idle timeout and id rotation; role- and
permission-based authorisation enforced in middleware before controllers run; evidence
stored outside the web root and streamed only to authorised users after a MIME-sniff check;
AES-256-GCM for sensitive columns; a redacting audit log; and a restrictive Content Security
Policy. Details and the reasoning behind each control are in
[docs/SECURITY.md](docs/SECURITY.md).

---

## Documentation

| Document | Contents |
|---|---|
| [SYSTEM-DESIGN-AND-AUTHENTICATION.md](docs/SYSTEM-DESIGN-AND-AUTHENTICATION.md) | **Design phase deliverable** — architecture diagram, ERD, the five wireframes, and the full authentication/RBAC specification with verification evidence |
| [INSTALLATION.md](docs/INSTALLATION.md) | Requirements, setup for MAMP/XAMPP/LAMP, troubleshooting |
| [HOSTING-HOSTINGER.md](docs/HOSTING-HOSTINGER.md) | **Going live on Hostinger (hPanel)** — the layout their fixed document root forces, plus TLS, SMTP and backups |
| [HOSTING-CPANEL.md](docs/HOSTING-CPANEL.md) | Going live on other shared cPanel hosts |
| [ARCHITECTURE.md](docs/ARCHITECTURE.md) | System architecture diagram, request lifecycle, layering |
| [ERD.md](docs/ERD.md) | Entity-relationship diagram in crow's-foot notation |
| [DATABASE.md](docs/DATABASE.md) | Table-by-table reference, indexes, conventions |
| [WIREFRAMES.md](docs/WIREFRAMES.md) | Screen wireframes for every major page |
| [SECURITY.md](docs/SECURITY.md) | OWASP Top 10 control mapping and hardening checklist |
| [USER-MANUAL.md](docs/USER-MANUAL.md) | Guide for citizens, officers and supervisors |
| [ADMIN-GUIDE.md](docs/ADMIN-GUIDE.md) | Guide for administrators and awareness officers |
| [API.md](docs/API.md) | Internal JSON endpoints |
| [DEPLOYMENT.md](docs/DEPLOYMENT.md) | Production deployment and go-live checklist |
| [BACKUP-RECOVERY.md](docs/BACKUP-RECOVERY.md) | Backup strategy and restore procedure |

---

## License

Released for the operating organisation's internal use. Review before redistribution.
