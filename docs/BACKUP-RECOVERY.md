# Backup & Recovery Guide

## What has to be backed up

A complete recovery needs **four** things. Missing any one leaves you with a system that looks
restored but is not.

| # | What | Where | Frequency | If you lose it |
|---|---|---|---|---|
| 1 | Database | MySQL `hrrams` | Nightly | Every report, case, timeline entry, user and audit record is gone |
| 2 | Evidence files | `storage/uploads/` | Weekly, or daily if volume is high | Cases survive but every attachment link is broken — the evidence itself is lost |
| 3 | Configuration | `config/config.local.php` | On every change | Credentials must be recreated, and **`app_key` cannot be** |
| 4 | Encryption key | `security.app_key` | Once, stored separately | Every AES-encrypted column becomes permanently unreadable |

Items 3 and 4 are small and static, and are the ones most often forgotten. Store `app_key` in a
password manager or sealed envelope **separate** from the database backup — keeping the key
beside the data it protects defeats the point.

---

## Taking a backup

### From the interface

**Administration → Backup → Back up now.** Runs `mysqldump --single-transaction --routines
--events`, so tables are not locked and the system can stay in use. The file lands in
`storage/backups/` with mode 0600 and is recorded as `backup.created` in the audit log.

Database only — it does **not** include `storage/uploads`.

### From the command line

```bash
mysqldump --defaults-extra-file=/root/.hrrams-backup.cnf \
    --single-transaction --routines --events hrrams \
    | gzip > /var/backups/hrrams/db-$(date +%F-%H%M).sql.gz
```

Evidence files:

```bash
tar czf /var/backups/hrrams/uploads-$(date +%F).tar.gz \
    -C /var/www/hrrams/storage uploads
```

Configuration:

```bash
cp /var/www/hrrams/config/config.local.php \
   /var/backups/hrrams/config-$(date +%F).php
chmod 600 /var/backups/hrrams/config-*.php
```

Never put credentials on the `mysqldump` command line — anyone running `ps` can read them.
Use `--defaults-extra-file` (mode 600) as above.

The cron schedule that automates all of this is in
[DEPLOYMENT.md §8](DEPLOYMENT.md#8-scheduled-tasks).

---

## Recommended strategy

**3-2-1:** three copies, on two kinds of media, one of them off-site.

| Layer | Retention |
|---|---|
| Nightly database dump, local | 30 days |
| Weekly evidence archive, local | 8 weeks |
| Off-site copy of both | 90 days |
| Monthly archive, cold storage | 7 years, or your agreed retention period |

For a human rights case system, favour the longer end. Complaints are sometimes revisited years
later, and the audit trail is the accountability record.

**Before every update or schema change,** take an explicit pre-change dump — see
[DEPLOYMENT.md §10](DEPLOYMENT.md#10-updating-a-live-installation).

---

## Verifying a backup

An unverified backup is a hope, not a plan. Check three things after each nightly run:

```bash
# 1. It exists and is not empty
ls -lh /var/backups/hrrams/db-$(date +%F)*.sql.gz

# 2. The gzip stream is intact
gunzip -t /var/backups/hrrams/db-$(date +%F)*.sql.gz && echo "archive OK"

# 3. The dump completed — mysqldump writes this as its last line
zcat /var/backups/hrrams/db-$(date +%F)*.sql.gz | tail -1
# Expect: -- Dump completed on YYYY-MM-DD  H:MM:SS
```

**Quarterly, restore into a throwaway database and count rows** — this is the only test that
actually proves recoverability:

```bash
mysql -e "CREATE DATABASE hrrams_verify"
zcat /var/backups/hrrams/db-latest.sql.gz | mysql hrrams_verify
mysql hrrams_verify -e "
  SELECT (SELECT COUNT(*) FROM users)   AS users,
         (SELECT COUNT(*) FROM reports) AS reports,
         (SELECT COUNT(*) FROM cases)   AS cases,
         (SELECT COUNT(*) FROM audit_logs) AS audit_rows;"
mysql -e "DROP DATABASE hrrams_verify"
```

Compare the counts with production. Record the date and the figures — that record is what an
auditor will ask for.

---

## Restoring

Restore overwrites live data, which is exactly why it is not exposed in the web interface.

### 1. Take the system offline

Put up a maintenance page or stop Apache, so nothing writes while you work:

```bash
sudo systemctl stop apache2
```

### 2. Back up the current state first

Even a broken database. If the restore goes wrong you will want to come back to it:

```bash
mysqldump --defaults-extra-file=/root/.hrrams-backup.cnf \
    --single-transaction hrrams | gzip > /var/backups/hrrams/pre-restore-$(date +%F-%H%M).sql.gz
```

### 3. Restore the database

```bash
# From a gzipped dump
zcat /var/backups/hrrams/db-2026-08-13.sql.gz | mysql hrrams

# From a plain dump taken by the interface
mysql -u root -p hrrams < storage/backups/hrrams-20260813-021500.sql
```

If the database was dropped entirely, recreate it first — dumps from the interface include the
`CREATE DATABASE` from `schema.sql`, but a plain `--databases`-less dump does not:

```bash
mysql -e "CREATE DATABASE hrrams CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

### 4. Restore the evidence files

```bash
tar xzf /var/backups/hrrams/uploads-2026-08-11.tar.gz -C /var/www/hrrams/storage
sudo chown -R www-data:www-data /var/www/hrrams/storage/uploads
sudo chmod -R 770 /var/www/hrrams/storage/uploads
```

### 5. Restore configuration and the key

Put `config/config.local.php` back, and confirm `security.app_key` is **the same value** that
was in use when the data was encrypted. A mismatched key does not error — it silently returns
`null` for every encrypted column, which is far worse than a visible failure.

### 6. Verify before reopening

```bash
sudo systemctl start apache2
```

```sql
-- Row counts against what you expected
SELECT (SELECT COUNT(*) FROM reports WHERE deleted_at IS NULL) AS reports,
       (SELECT COUNT(*) FROM cases   WHERE deleted_at IS NULL) AS cases,
       (SELECT COUNT(*) FROM users   WHERE deleted_at IS NULL) AS users;

-- Referential integrity: this must be 0
SELECT COUNT(*) AS reports_without_case
FROM reports r LEFT JOIN cases c ON c.report_id = r.id
WHERE c.id IS NULL AND r.deleted_at IS NULL;

-- Most recent activity — confirms how far the restore reached
SELECT MAX(created_at) AS latest_report FROM reports;
SELECT MAX(created_at) AS latest_audit  FROM audit_logs;
```

Then in the browser: sign in, open a case, and **open an attachment**. That last step is the
one that catches a database restored without its files.

### 7. Record what happened

Note the incident, the backup used, the data-loss window (the gap between the backup timestamp
and the failure), and anything that had to be re-entered. If reports were lost, the complainants
cannot know that — you may need to reach out.

---

## Recovery scenarios

**Accidental deletion through the interface.** Nothing is truly deleted — deletion is soft.
Clear the flag rather than restoring a backup:

```sql
UPDATE users   SET deleted_at = NULL WHERE id = 42;
UPDATE reports SET deleted_at = NULL WHERE reference_no = 'HR-2026-000117';
```

**A category "deleted" while in use** was only deactivated. Re-enable it in
**Administration → Categories**.

**Corrupt single table.** Restore just that table rather than the whole database:

```bash
zcat db-2026-08-13.sql.gz | sed -n '/DROP TABLE.*`cases`/,/UNLOCK TABLES/p' | mysql hrrams
```

Check foreign-key consistency afterwards — a table restored to an older point than its parents
can orphan rows.

**Total server loss.** Rebuild per [DEPLOYMENT.md](DEPLOYMENT.md) §1–5, then restore items
1–4 above. Expect several hours; the schema load and configuration are quick, the evidence
archive is what takes time.

**Ransomware or suspected compromise.** Do not restore onto the compromised host. Build a clean
server, restore from a backup taken *before* the earliest sign of intrusion, rotate every
credential including `app_key`-encrypted data handling, force a password reset for all users,
and review `audit_logs` and `login_history` for the intrusion window before reopening.

---

## Objectives to agree with your organisation

| Objective | Suggested target | What it depends on |
|---|---|---|
| **RPO** — acceptable data loss | ≤ 24 hours | Backup frequency. Nightly dumps mean up to a day of reports could be lost. Tighten with MySQL binary logging if that is unacceptable. |
| **RTO** — acceptable downtime | ≤ 4 hours | Whether a standby host exists, and how large the evidence archive is. |
| **Retention** | 7 years, or as advised | Legal requirement and the fact that cases are revisited. |

Write these down and get them signed off. An RPO nobody has agreed is an RPO nobody is
accountable for — and after an incident is the wrong time to discover that a day of complaints
was considered acceptable to lose.
