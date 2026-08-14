# System Architecture

## 1. Architecture diagram

Actors on the left, the application tiers in the middle, supporting services on the right.

```mermaid
flowchart LR
    subgraph Actors
        V["Public Visitor<br/>(anonymous)"]
        C["Registered Citizen<br/>/ Reporter"]
        O["Human Rights<br/>Officer"]
        S["Supervisor"]
        A["Awareness<br/>Officer"]
        AD["System<br/>Administrator"]
    end

    subgraph Edge["Web tier"]
        HTTPS["HTTPS / TLS"]
        AP["Apache 2.4<br/>mod_rewrite · mod_headers<br/>DocumentRoot = public/"]
    end

    subgraph App["Application tier — PHP 8 (MVC)"]
        FC["Front controller<br/>public/index.php"]
        K["Kernel<br/>config · autoload · headers · session"]
        RT["Router<br/>config/routes.php"]
        MW["Middleware<br/>auth · guest · role · can · CSRF"]
        CTL["Controllers<br/>Home · Auth · Report · Case ·<br/>Dashboard · Awareness · Reports · Admin"]
        SVC["Core services<br/>Validator · Uploader · Mailer ·<br/>Notify · Audit · Crypto"]
        MDL["Models<br/>User · Report · CaseFile · Campaign ·<br/>Resource · Event · Setting · AuditLog"]
        VW["Views<br/>Bootstrap 5 · Chart.js"]
    end

    subgraph Data["Data tier"]
        DB[("MySQL 8<br/>hrrams<br/>23 tables + view")]
        FS[("File storage<br/>storage/uploads<br/>outside web root")]
        LG[("Logs & backups<br/>storage/logs<br/>storage/backups")]
    end

    MAIL["Email server<br/>SMTP / sendmail"]
    SMS["SMS gateway<br/>(future)"]

    V & C & O & S & A & AD --> HTTPS --> AP --> FC
    FC --> K --> RT --> MW --> CTL
    CTL --> SVC
    CTL --> MDL
    CTL --> VW
    MDL -- "PDO prepared statements" --> DB
    SVC -- "MIME-checked writes" --> FS
    SVC --> LG
    SVC --> MAIL
    SVC -.-> SMS
    VW --> AP
```

### Why the tiers are split this way

- **Only `public/` is web-exposed.** Application code, configuration, evidence files, logs and
  backups all live above the document root. A misconfigured server cannot leak them, and an
  uploaded file cannot be executed because it is never inside a served directory.
- **One entry point.** Every request passes through `public/index.php`, so security headers,
  session hardening, CSRF verification and authorisation are guaranteed rather than
  remembered per-page.
- **Authorisation sits in middleware, before controllers.** A route declares the permission it
  needs; the controller is never reached without it.
- **Models own all SQL.** Controllers never build a query, so prepared statements are
  structurally enforced rather than a convention.

---

## 2. Request lifecycle

```mermaid
sequenceDiagram
    participant B as Browser
    participant A as Apache
    participant F as index.php
    participant K as Kernel
    participant R as Router
    participant M as Middleware
    participant C as Controller
    participant D as Model / PDO
    participant V as View

    B->>A: GET /cases/42
    A->>F: rewrite (no matching file)
    F->>K: App::boot()
    Note over K: load config · register autoloader<br/>error handler · security headers<br/>start hardened session
    K-->>F: ready
    F->>R: dispatch()
    R->>R: match path to route
    alt state-changing method
        R->>R: Csrf::verify() — 419 on mismatch
    end
    R->>M: apply route middleware
    M->>D: resolve user, roles, permissions
    alt not permitted
        M->>V: 403 + audit entry
        V-->>B: error page
    else permitted
        M-->>R: continue
        R->>C: CaseController::show(42)
        C->>D: prepared SELECT
        D-->>C: rows
        C->>V: render(view, data, layout)
        V-->>B: escaped HTML
    end
    F->>D: Audit::activity() — request trail
```

---

## 3. Layer responsibilities

| Layer | Files | Responsibility | Must not |
|---|---|---|---|
| Front controller | `public/index.php` | Boot the kernel, load helpers, dispatch | Contain business logic |
| Kernel | `app/Core/App.php` | Config, autoloading, error handling, security headers, base-URL detection | Touch the database |
| Session | `app/Core/Session.php` | Strict-mode sessions, fingerprint binding, idle timeout, id rotation | Store secrets |
| Router | `app/Core/Router.php` | Path matching, CSRF gate, middleware chain, dispatch | Query data |
| Middleware | `app/Core/Middleware.php` | Enforce `auth`, `guest`, `verified`, `role:`, `can:` | Render business views |
| Controllers | `app/Controllers/*` | Validate input, orchestrate models and services, choose a view | Write SQL or echo HTML |
| Models | `app/Models/*` | All data access via prepared statements; aggregation queries | Handle HTTP or render |
| Services | `app/Core/{Validator,Uploader,Mailer,Notify,Audit,Crypto}.php` | Cross-cutting concerns | Depend on a specific controller |
| Views | `app/Views/*` | Presentation only; every dynamic value through `e()` | Contain queries or business rules |

---

## 4. Module map

```mermaid
flowchart TB
    subgraph M1["Authentication"]
        A1["Register · verify · login · logout<br/>Password reset · CAPTCHA · remember me<br/>Lockout · login history"]
    end
    subgraph M2["RBAC"]
        A2["5 roles · 26 permissions<br/>role_permissions matrix<br/>Dynamic menu rendering"]
    end
    subgraph M3["Reporting"]
        A3["4-step wizard · anonymous option<br/>Evidence upload · GPS capture<br/>Reference generation · tracking"]
    end
    subgraph M4["Case management"]
        A4["Auto case number · assign / reassign<br/>Timeline · notes · evidence · interviews<br/>Info requests · escalation · closure"]
    end
    subgraph M5["Awareness"]
        A5["Campaigns · news · stories · newsletters<br/>Resources · events<br/>Engagement analytics"]
    end
    subgraph M6["Dashboards"]
        A6["Role-specific KPI tiles<br/>Line · bar · doughnut · heat map<br/>Officer performance"]
    end
    subgraph M7["Notifications"]
        A7["In-app · email<br/>SMS behind the same interface<br/>Per-role broadcast"]
    end
    subgraph M8["Audit"]
        A8["audit_logs · activity_logs<br/>login_history<br/>Redacted values"]
    end
    subgraph M9["Reporting & export"]
        A9["Daily / weekly / monthly / annual<br/>CSV · Excel · print-to-PDF"]
    end

    M1 --> M2 --> M4
    M3 --> M4 --> M6
    M4 --> M7
    M2 --> M8
    M4 --> M9
    M5 --> M6
```

---

## 5. Case lifecycle

A case is opened automatically the moment a report is submitted, so nothing can sit
unrecorded. Its service deadline is derived from the urgency the reporter selected: critical
3 days, high 7, medium 14, low the configured default (30).

```mermaid
stateDiagram-v2
    [*] --> submitted: report filed
    submitted --> under_review: case auto-opened
    under_review --> assigned: supervisor assigns officer
    under_review --> rejected: outside mandate
    assigned --> investigating: officer begins work
    investigating --> awaiting_information: more detail needed
    awaiting_information --> investigating: reporter responds
    investigating --> escalated: needs senior review
    escalated --> investigating: returned with direction
    investigating --> resolved: remedy achieved
    escalated --> resolved
    resolved --> closed: file concluded
    rejected --> [*]
    closed --> [*]
```

Only a holder of `case.close` — supervisor or administrator — may move a case to `resolved`,
`closed` or `rejected`. Officers can advance the investigative states but cannot conclude
their own cases.

---

## 6. Authorisation model

```mermaid
erDiagram
    users ||--o{ user_roles : holds
    roles ||--o{ user_roles : "granted to"
    roles ||--o{ role_permissions : grants
    permissions ||--o{ role_permissions : "granted by"
```

A user's effective permissions are the union of the permissions of all their roles, resolved
per request in `Auth::permissions()`. The same `can()` check drives both the middleware gate
and the visibility of menu items and buttons, so the interface never offers an action that
would be refused.

Additional record-level rules layer on top of permissions:

- An officer without `case.view_any` may only open cases where `cases.officer_id` is their own
  id — enforced in `CaseController::authorisedCase()`.
- A citizen may only open a report where `reports.reporter_user_id` is their own id.
- Evidence is released only to a user who holds a case-wide permission, owns the report, or is
  the assigned officer — enforced in `CaseController::mayViewAttachment()`.

Every refusal writes an `access.denied` audit entry.

---

## 7. Technology choices

| Concern | Choice | Reasoning |
|---|---|---|
| Framework | None; hand-rolled MVC | The brief specifies PHP 8 MVC. No Composer dependency keeps deployment to a file copy and removes a supply-chain surface. |
| Data access | PDO, prepared statements | Emulation disabled, so parameters are bound server-side. |
| Front end | Bootstrap 5.3 + custom CSS layer | Responsive grid, accessible components, dark mode via `data-bs-theme`. |
| Charts | Chart.js 4 | Canvas-based, small, theme-aware. Each chart also has a table equivalent for screen readers. |
| PDF export | Print-ready HTML | No binary dependency. `ReportsController::exportPdf()` is the single place to swap in Dompdf if server-side PDF becomes a requirement. |
| Excel export | Single-sheet HTML table with an `.xls` content type | Opens natively in Excel and LibreOffice with no library. |
| Passwords | `password_hash()` with bcrypt | Adaptive cost, salt handled by PHP. |
| Sensitive columns | AES-256-GCM via `Crypto` | Authenticated encryption — tampering is detected, not just hidden. |

---

## 8. Performance

- **Indexes** on every foreign key plus the columns actually filtered and sorted — status,
  category, province, urgency, `created_at`, `officer_id`, `deleted_at`.
- **Aggregation in SQL**, not PHP. Dashboard tiles come from single grouped queries
  (`Report::totals()`, `CaseFile::stats()`) rather than counting loops.
- **Pagination everywhere** a list can grow, with `LIMIT`/`OFFSET` bound to validated integers.
- **Charts load over AJAX** after first paint, with skeleton placeholders, so a slow
  aggregation never blocks the page.
- **One fetch feeds many charts** — `charts.js` groups canvases by endpoint.
- **`v_case_overview`** encapsulates the common five-table case join for reporting.
- **GZIP, `Expires` headers and lazy-loaded images** configured in `public/.htaccess`.
