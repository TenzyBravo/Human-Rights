# Administrator Guide

Covers the System Administrator and Awareness Officer roles.

---

# Part 1 — System Administrator

## First hour on a new installation

1. Sign in as `admin@hrrams.test` / `Admin@12345`.
2. **Administration → Users** — create your real administrator account, sign in as that, then
   delete or re-password every demo account. Do this before anything else.
3. **Administration → Settings** — set the organisation name, tagline, contact email, phone
   and hotline. These appear across the public site and in emails.
4. **Administration → Categories** — adjust the twelve seeded categories to match your
   mandate.
5. **Administration → Roles** — review the permission matrix against how your team actually
   works.
6. **Administration → Backup** — take a baseline backup and confirm you can download it.
7. Work through the production checklist in [SECURITY.md](SECURITY.md#5-hardening-checklist-for-production).

## Managing users

**Administration → Users** lists every account with role, status, last sign-in and creation
date. Filter by role, status, or search name, email and phone.

### Creating a staff account

**Add user** → details → set an initial password → tick the roles → **Create user**.

A user may hold several roles; their permissions are the union. Common combinations:

| Person | Roles |
|---|---|
| Investigator | Human Rights Officer |
| Team leader who also investigates | Human Rights Officer + Supervisor |
| Communications staff | Awareness Officer |
| Registry clerk with read access | Human Rights Officer (then remove case-update permissions from that role, or create a new role) |

Set the status to **Active** for staff — this marks the email verified so they can sign in
immediately. Leave it **Pending** if you want them to verify by email first.

### Statuses

| Status | Effect |
|---|---|
| **Active** | Normal access. Also clears any brute-force lock. |
| **Pending** | Cannot sign in until the email address is verified. |
| **Suspended** | Cannot sign in. Use for staff who have left or during an investigation. |
| **Locked** | Temporarily blocked after repeated failed sign-ins; clears itself, or set to Active to clear immediately. |

### Unlocking an account

A user locked out after five failed attempts clears automatically after fifteen minutes.
To clear it now, set their status to **Active**.

### Deactivating an account

**Deactivate** soft-deletes the account: they can no longer sign in, but their cases, notes,
timeline entries and audit history all remain intact and attributed. This is deliberate — an
investigation record must outlive the employment.

You cannot deactivate or suspend your own account, and you cannot remove your own
administrator role. If you try, the role is silently retained and you are told why.

### Resetting a password

Open the user → enter a new password → save. Send it to them out of band and ask them to change
it. The action is recorded as `user.password_reset`. Prefer letting users use
**Forgot password** themselves where possible.

## Roles and permissions

**Administration → Roles** shows every role as a card of checkboxes grouped by module. Tick or
untick, then **Save** that card.

The System Administrator role always holds every permission and its checkboxes are disabled —
this prevents an administrator from locking the whole organisation out of its own system.

The 26 permissions:

| Module | Permissions |
|---|---|
| Reports | `report.create`, `report.view_own`, `report.view_any`, `report.delete` |
| Cases | `case.view_assigned`, `case.view_any`, `case.update`, `case.note`, `case.evidence`, `case.interview`, `case.assign`, `case.approve`, `case.escalate`, `case.close` |
| Awareness | `awareness.manage`, `awareness.analytics`, `event.manage` |
| Dashboard | `dashboard.department`, `dashboard.admin` |
| Admin | `user.manage`, `role.manage`, `category.manage`, `settings.manage`, `audit.view`, `backup.manage` |
| Reporting | `report.export` |

Two permissions carry more weight than their names suggest:

- **`case.view_any`** removes the ownership restriction — the holder can open every case in the
  organisation, including complainant contact details. Grant it sparingly.
- **`case.close`** allows concluding a case. Keeping it away from officers is what makes the
  supervisory review meaningful.

Changes take effect on the affected user's very next request; they do not need to sign out.

## Report categories

**Administration → Categories** — add, edit, activate or deactivate. Each has a name, a
description shown as guidance on the reporting form, and a Bootstrap Icons class
(browse them at `icons.getbootstrap.com`).

Deleting a category that is already used by reports **deactivates** it instead — you are told
how many reports are affected. This keeps historical classification honest. An unused category
is deleted outright.

## System settings

**Administration → Settings**, grouped:

| Group | Keys |
|---|---|
| Organisation | `site_name`, `site_tagline`, `contact_email`, `contact_phone`, `hotline` |
| Security policy | `session_timeout_minutes`, `max_login_attempts`, `lockout_minutes`, `password_min_length`, `require_email_verification` |
| Evidence uploads | `max_upload_mb` |
| Case handling | `sla_days_default` |
| Email | `mail_from_name`, `mail_from_email` |

Database credentials, the encryption key and the HTTPS switch are **not** here — they live in
`config/config.php` (or `config/config.local.php`) so they never sit in the database.

Note that the security keys in Settings are the organisational policy record; the values the
code enforces per request come from the config file. Keep the two consistent, and treat the
config file as authoritative when they disagree.

## Audit logs

**Administration → Audit logs** is the accountability record. Filter by free text, action type
or date range. Failures — `login.failed`, `access.denied`, `account.locked`, `csrf.failed` —
are highlighted in red.

Entries are append-only; there is no interface path that edits or deletes one.

### What to review, and how often

**Weekly:**

- `login.failed` clustered on one email or IP → attempted intrusion. Consider a firewall block.
- `access.denied` repeated by one user → either a permission problem to fix, or someone probing.
- `evidence.viewed` by anyone who is not the assigned officer → confirm the reason.

**Monthly:**

- `user.roles_changed` and `role.permissions_changed` → confirm every change was authorised.
- `report.exported` → confirm each export had a legitimate purpose. Exports leave the system.
- `backup.created` → confirm the schedule is actually running.

### Investigating an incident

Filter by the user and the date range, then read the sequence. `audit_logs` gives the
security-relevant actions with before/after values; if you need the full navigation trail,
query `activity_logs` directly — it holds one row per request.

## Backup and restore

**Administration → Backup** lists existing snapshots and creates new ones with **Back up now**.
It runs `mysqldump --single-transaction`, so it does not lock tables and is safe to run while
the system is in use.

Restore is deliberately **not** in the web interface — it overwrites live data. The procedure
is in [BACKUP-RECOVERY.md](BACKUP-RECOVERY.md).

Two things this screen cannot do for you:

- **Get the backups off the server.** A backup on the machine it protects is not a backup.
  Schedule an off-server copy.
- **Back up the evidence files.** `mysqldump` covers the database only. `storage/uploads` needs
  its own copy, and a database restored without matching files leaves broken attachment links.

---

# Part 2 — Awareness Officer

## Campaigns, news and stories

**Awareness → Campaigns & news** lists everything with type, status, view count and author.

**New content** → choose the type:

| Type | Use for |
|---|---|
| **Campaign** | A programme with a date range — appears on the campaigns page and the homepage |
| **News** | Announcements and updates |
| **Article** | Explanatory or educational writing |
| **Success story** | Outcomes achieved, with the complainant's consent |
| **Newsletter** | Periodic roundups |

Write a **summary** — it is what appears on listing pages and in search results. Keep it to two
lines.

**Status** controls visibility: *Draft* is invisible to the public, *Published* is live
immediately, *Archived* hides it without deleting it.

Body text is plain text with blank lines between paragraphs. Basic formatting tags are
permitted; anything else is stripped before the page renders. This is a security control, not a
limitation to work around.

### Writing a success story

Get the complainant's explicit consent first, in writing. Then change or remove every
identifying detail unless they have specifically agreed to be named — the location, the
employer, the family situation. A story that identifies someone who did not agree to be
identified causes real harm and undermines every future complainant's trust.

## Educational resources

**Awareness → Resources.** Each resource has a title, a type (document, video, audio, image,
link or training), a description, and either an uploaded file or an external URL — or both.

Untick **Publish immediately** to prepare a resource without exposing it.

When linking to an external source, prefer the authoritative original — a UN or government
page over a mirror. Links open in a new tab and carry `rel="noopener noreferrer"`.

## Events

**Awareness → Events.** Title, description, venue, province, start and end date-time,
capacity, status. Only *Published* events with a future start date appear on the public events
page.

Set a **Cancelled** event to that status rather than deleting it — someone may have it in their
diary, and the record explains what happened.

## Campaign analytics

**Awareness → Campaign analytics** shows total views, published item count, resource opens, a
views-by-type chart, a most-viewed table, and per-type averages.

Read these as relative signals, not audience measurement. A view is a page load, not a person,
and there is no visitor tracking by design — the site sets no analytics cookies and stores no
visitor identifiers. If you need reliable reach figures, count them at the campaign level
(attendance, forms returned, calls received) rather than from page views.

What the numbers *are* good for: comparing which topics draw interest, spotting a resource
nobody can find, and confirming a campaign page went live when you expected.
