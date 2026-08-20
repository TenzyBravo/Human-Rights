# Hosting HRRAMS on shared cPanel hosting

A start-to-finish deployment for a normal cPanel plan — the environment this
application was built for. Apache, PHP 8 and MySQL 8 are already there; nothing
needs compiling and there is no container to build.

Budget roughly **45–90 minutes** for a first deployment.

---

## Before you start: a word about where this is hosted

This system stores the identities of people reporting human rights violations,
their contact details, and evidence they have supplied. That makes the choice of
host a safety decision, not only a technical one.

Two things worth deciding deliberately:

**Jurisdiction.** Data sits under the laws of the country the server is in, and
of the company that owns it. If complainants may be reporting abuses by state
actors, a host that can be compelled by those actors is a poor choice. European
hosting (Hetzner, Infomaniak, Hostinger's EU regions) sits under GDPR, which
gives stronger protection than most alternatives.

**Shared hosting means shared machines.** Your files are isolated by user
permissions, but you do not control the kernel, and the provider's staff can read
your files and database. For a pilot or a small deployment that is a reasonable
trade for the cost. If this becomes the live national complaints channel, move to
a VPS you control — [DEPLOYMENT.md](DEPLOYMENT.md) covers that path.

Whatever you choose, tell complainants the truth about it in your privacy notice.

### Host requirements

| Requirement | Why |
|---|---|
| PHP **8.1+** (8.3 preferred) | The code uses `match`, enums in typed properties, `str_starts_with` |
| Extensions `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `json` | Database, UTF-8, upload MIME sniffing, encryption |
| MySQL 8.0+ or MariaDB 10.6+ | `JSON` columns in `audit_logs`, window-free but modern SQL |
| Ability to **set the document root**, or to place files above `public_html` | Keeps application code, config and evidence out of the web root |
| Free TLS (AutoSSL / Let's Encrypt) | Non-negotiable — this app handles complainant identities |
| Outbound SMTP on port 587 **or** 465 | Verification links and password resets |

Any mainstream plan from Hostinger, Namecheap, A2, SiteGround or a competent
local provider meets this. If a host cannot do TLS or will not let you move the
document root, pick a different host.

---

## 1. Build the upload archive

On your own machine, in the project directory:

```bash
tools/build-release.sh
```

This produces `dist/hrrams-YYYYMMDD-HHMM.zip` (about 1.2 MB) and checks itself:
no local credentials, no runtime files, no tooling scratch, and every file the
application needs actually present. If a check fails it refuses to hand you a
broken archive.

`config/config.local.php` is deliberately **not** in the archive — that file
holds your database password, encryption key and SMTP credentials. You will
create it directly on the server in step 5.

---

## 2. Create the database

**cPanel → MySQL® Databases**

1. **Create a database** — e.g. `hrrams`. cPanel prefixes it with your account
   name, giving something like `myacct_hrrams`. Note the full name.
2. **Create a user** — e.g. `hrrams_app`, with a long random password from the
   generator. Note it.
3. **Add the user to the database** and grant:
   `SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES`

> `CREATE`/`ALTER`/`DROP`/`REFERENCES` are needed only for the one-time import in
> step 3. Once the schema is loaded you can revoke them and leave just
> `SELECT, INSERT, UPDATE, DELETE` — the application never issues DDL at runtime.
> Doing so limits what a SQL-injection flaw could ever achieve.

---

## 3. Import the schema

**cPanel → phpMyAdmin →** select your database **→ Import**

Import in this order, waiting for each to finish:

1. `database/schema.sql` — creates 23 tables and one reporting view
2. `database/seed.sql` — roles, the 26 permissions, ten provinces and their
   districts, twelve report categories, settings and demo content

> **`schema.sql` begins with `CREATE DATABASE IF NOT EXISTS hrrams; USE hrrams;`.**
> On cPanel your database is named with an account prefix, so those two lines
> will either fail or, worse, create a second database you are not using. Before
> importing, open `schema.sql` in a text editor and delete the first two
> statements — phpMyAdmin already knows which database you selected. Do the same
> for the `USE hrrams;` line at the top of `seed.sql`.

Confirm afterwards: the table list should show 23 tables, and
`SELECT COUNT(*) FROM permissions;` should return 26.

---

## 4. Upload and place the files

**cPanel → File Manager**

You have two layouts. Use A if your host allows it; it is simpler and safer.

### Layout A — document root points at `public/` (preferred)

1. Upload the zip to your home directory (**not** into `public_html`).
2. Extract it, then rename the extracted folder to `hrrams`, so you have
   `/home/youracct/hrrams/`.
3. **cPanel → Domains** (on some plans: *Addon Domains* or *Subdomains*) → edit
   the domain → set **Document Root** to:

   ```
   /home/youracct/hrrams/public
   ```

4. Save. Nothing else is required — `app/`, `config/`, `storage/` and
   `database/` are now all above the web root and unreachable over HTTP.

### Layout B — the host will not move the document root

1. Upload and extract to `/home/youracct/hrrams/` exactly as above.
2. Copy **the contents of** `hrrams/public/` into `public_html/` — that means
   `index.php`, `.htaccess`, `.user.ini` and the `assets/` folder, not the
   `public` folder itself.
3. Edit `public_html/.htaccess` and add this as the **first** line, so the front
   controller can find the application:

   ```apache
   SetEnv HRRAMS_APP_ROOT /home/youracct/hrrams
   ```

4. Delete `hrrams/public/` to avoid confusion about which copy is live.

`public/index.php` reads `HRRAMS_APP_ROOT` and falls back to its parent
directory, so no code changes are needed either way. If the variable is wrong you
get a plain, explicit error rather than a blank page.

> If your host disables `mod_env` and ignores `SetEnv`, the archive's root
> `.htaccess` (which rewrites everything into `public/`) is a third option:
> extract the whole project into `public_html` and rely on that. It works, but the
> application files then sit inside the web root protected only by `.htaccess`
> rules — acceptable for a pilot, not for live complainant data. Prefer A.

### Permissions

In File Manager, or over SSH:

```bash
cd ~/hrrams
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 755 storage
chmod 600 config/config.local.php     # after you create it in step 5
```

On shared hosting PHP runs as *your* user, so 755 is enough for writable
directories — do not use 777. If a host support article tells you to chmod 777,
it is wrong, and on a system holding evidence files it is dangerous.

---

## 5. Configure the application

Create `config/config.local.php` (File Manager → **+ File**, then Edit). This
file is read *over* `config.php`, so include only what changes:

```php
<?php
return [
    'app' => [
        'env'         => 'production',
        'debug'       => false,
        'force_https' => true,
        'base_url'    => 'https://rights.example.org',   // no trailing slash
    ],

    'db' => [
        'host' => 'localhost',        // cPanel MySQL is local; not 127.0.0.1 on every host
        'port' => 3306,
        'name' => 'myacct_hrrams',
        'user' => 'myacct_hrrams_app',
        'pass' => 'the-long-password-you-generated',
    ],

    'security' => [
        // openssl rand -base64 32     (run locally; paste the result)
        'app_key'       => 'base64:REPLACE_WITH_A_FRESH_32_BYTE_KEY',
        'cookie_secure' => true,
    ],

    'mail' => [
        'driver'     => 'smtp',
        'from_email' => 'no-reply@rights.example.org',
        'from_name'  => 'Human Rights Commission',
        'smtp' => [
            'host'   => 'smtp-relay.brevo.com',
            'port'   => 587,
            'user'   => 'your-brevo-login',
            'pass'   => 'your-brevo-smtp-key',
            'secure' => 'tls',
        ],
    ],
];
```

Three things that will bite you if you skip them:

**`app_key` must be new and must never change afterwards.** It encrypts sensitive
columns with AES-256-GCM. Generate it once, paste it here, and store a copy
somewhere separate from the database backup — a password manager, not the same
server. Change it later and previously encrypted values become unreadable, with
no error to warn you.

**`db.host` is `localhost` on most cPanel hosts**, not `127.0.0.1` — some
configurations only expose the MySQL socket, which PHP reaches via `localhost`.
If the connection fails, try the other.

**Never leave `debug` on.** With it on, a stack trace containing your database
credentials is shown to whoever triggers an error.

### Select the PHP version

**cPanel → MultiPHP Manager** → set the domain to **PHP 8.3** (or 8.2/8.1).

Then **cPanel → Select PHP Version → Extensions** and confirm `pdo_mysql`,
`mbstring`, `fileinfo`, `openssl` and `json` are ticked. They usually are.

---

## 6. Install TLS

**cPanel → SSL/TLS Status** → tick the domain → **Run AutoSSL**.

Wait for the certificate to issue (a minute or two), then load
`https://your-domain` and confirm the padlock. Only once that works should
`force_https` and `cookie_secure` be `true` — which you already set in step 5, so
if you set them before the certificate existed you will be redirect-looping.
Fix that by temporarily setting `force_https` to `false`, issuing the
certificate, then turning it back on.

Finally, open `public/.htaccess` and uncomment the HSTS header near the top so
browsers refuse to fall back to HTTP.

---

## 7. Configure email

Verification links and password resets are core flows; if email does not work,
new users cannot activate accounts and nobody can recover a lost password.

Sign up for a transactional provider (Brevo's free tier is 300 emails/day, which
is ample) and put the SMTP credentials in `config.local.php` as shown above.

Then set up sender authentication in your DNS — **cPanel → Zone Editor**:

- **SPF** — add the provider's include, e.g.
  `v=spf1 include:spf.brevo.com ~all`
- **DKIM** — add the CNAME or TXT records the provider gives you
- **DMARC** — start permissive: `v=DMARC1; p=none; rua=mailto:you@your-domain`

Without SPF and DKIM your verification emails will land in spam, and users will
conclude the system is broken.

> **If port 587 is blocked** — some shared hosts block outbound SMTP to reduce
> spam. Switch to implicit TLS on 465 by setting `'port' => 465` and
> `'secure' => 'ssl'`. If both are blocked, ask support to open one; if they
> refuse, that host cannot run this application.

---

## 8. Verify the installation

### Run the readiness check

```bash
php tools/preflight.php --mail you@your-address
```

Run it from **cPanel → Terminal** if your plan has it, or over SSH. If you have
neither, create a one-off cron job (**cPanel → Cron Jobs**, five minutes from
now) with the command above and your address in the "Email" box — the output is
emailed to you. Delete the cron entry afterwards.

It checks the PHP version and extensions, that `debug` is off, that `app_key` is
no longer the placeholder, the database connection and schema, that no demo
accounts remain, that `storage/` is writable, and that mail is configured — then,
with `--mail`, actually sends a message. It exits non-zero while anything is
still wrong.

Expect failures on a fresh install: the demo accounts are still there. Fix them
in the next step and re-run until it says **READY**.

### Secure the accounts

1. Sign in as `admin@hrrams.test` / `Admin@12345`.
2. **Administration → Users → Add user** — create your own administrator account
   with a real address and a strong password.
3. Sign out, sign back in as *yourself*, and confirm you have admin access.
4. Delete or re-password **all five** demo accounts:
   `admin@`, `supervisor@`, `officer@`, `awareness@`, `citizen@hrrams.test`.
5. **Administration → Settings** — set the organisation name, contact email,
   phone and hotline. These appear across the public site and in every email.

Leaving `Admin@12345` reachable on a live site hands over every complainant
record in the system. Do this before you tell anyone the URL.

### Confirm the private directories are private

Load each of these. Every one must return **403** or **404** — never file
contents:

```
https://your-domain/../config/config.local.php
https://your-domain/storage/uploads/
https://your-domain/app/Core/App.php
https://your-domain/database/schema.sql
https://your-domain/.user.ini
```

If any of them returns content, stop and fix the layout before going further —
`config.local.php` alone exposes your database password and encryption key.

### Walk the system

- [ ] Homepage loads with the statistics tiles
- [ ] Register a test account — the verification email **arrives** (check spam)
- [ ] The verification link activates the account
- [ ] File a report; a reference like `HR-2026-000001` comes back
- [ ] The acknowledgement receipt renders and prints
- [ ] **Track report** finds that reference
- [ ] Sign in as your supervisor account; the case shows under **Unassigned**
- [ ] Assign it to an officer — the officer receives the email
- [ ] As that officer, add a note and upload a file; the file downloads back
- [ ] A second officer is refused that case (403)
- [ ] Request a password reset; the email arrives and the link works once
- [ ] CSV, Excel and PDF exports all download with correct data

---

## 9. Backups

**The in-app backup will probably not work here.** Shared hosts almost always
disable `exec()`, so **Administration → Backup** will report that `mysqldump`
could not be found. That is expected, not a fault. Use one of these instead:

**cPanel's own backup** — *Backup* or *Backup Wizard* → **Download a MySQL
Database Backup**. Fine for occasional manual snapshots.

**A cron job** (**cPanel → Cron Jobs**), which is what you actually want:

```bash
# Nightly database dump at 02:15, keeping 30 days
15 2 * * * mkdir -p ~/backups && mysqldump --single-transaction -u myacct_hrrams_app -p'THEPASSWORD' myacct_hrrams | gzip > ~/backups/db-$(date +\%F).sql.gz && find ~/backups -name 'db-*.sql.gz' -mtime +30 -delete
```

```bash
# Weekly evidence-file archive, Sunday 03:00
0 3 * * 0 tar czf ~/backups/uploads-$(date +\%F).tar.gz -C ~/hrrams/storage uploads
```

Two cautions. The password is visible in the cron command on a shared machine —
prefer a `~/.my.cnf` with mode 600 holding the credentials and drop the `-p` flag
if your host supports it. And a backup that only exists on the server it protects
is not a backup: download the files, or add a job that pushes them to storage you
control off-site.

`~/backups` sits above `public_html`, so it is not web-accessible. Verify that.

Restore procedure and verification steps:
[BACKUP-RECOVERY.md](BACKUP-RECOVERY.md).

---

## 10. Ongoing

| Task | Frequency |
|---|---|
| Review **Administration → Audit logs** for `login.failed` clusters and `access.denied` | Weekly |
| Confirm the backup cron produced a non-empty file | Weekly |
| Download a backup copy off-server | Weekly |
| Rehearse a restore into a spare database | Quarterly |
| Re-run `php tools/preflight.php` | After any config change |
| Check PHP version is still supported in MultiPHP Manager | Twice a year |

Assign the log review to a named person. An audit trail nobody reads is a record
for after the fact, not a control.

---

## Troubleshooting

**Every page returns 500.**
Check `storage/logs/php-error.log` and cPanel's *Errors* page. The two usual
causes on shared hosting are already handled in this codebase — `php_flag`
directives (guarded so FastCGI ignores them) and a hard-coded `RewriteBase` — so
suspect the database credentials or a PHP version below 8.1 next.

**Homepage works, every other URL is 404.**
`mod_rewrite` is not applying. Confirm `public/.htaccess` uploaded (File Manager
hides dotfiles until you enable *Show Hidden Files* in Settings), and ask support
whether `AllowOverride` is enabled for your account.

**Every route serves the wrong site, or the host's default page.**
The document root is not pointing where you think. Re-check step 4. If you used
Layout B, confirm `SetEnv HRRAMS_APP_ROOT` names the directory containing `app/`.

**Styling missing, links point at the wrong path.**
`base_url` is wrong, or the document root is the project folder instead of
`public/`. Set `app.base_url` explicitly.

**"Database connection failed".**
Use the cPanel-prefixed database *and* user names (`myacct_hrrams`, not
`hrrams`). Try `localhost` and `127.0.0.1` for `db.host`. Confirm in **MySQL
Databases** that the user is actually attached to the database with privileges.

**Uploads fail silently, or fail above ~2 MB.**
`public/.user.ini` sets the limits for PHP-FPM. It can take five minutes to take
effect; restart PHP from cPanel to apply immediately. Confirm with **Select PHP
Version → Options** that `upload_max_filesize` and `post_max_size` are not capped
lower by the host.

**"Upload directory is not writable".**
`chmod -R 755 storage`. Confirm `storage/uploads` exists — some extraction tools
skip empty directories, and the release archive keeps them only via their
`.gitignore` files.

**Emails never arrive.**
Read `storage/logs/mail.log`; the SMTP transcript is written there with
credentials redacted. A `535` means bad credentials, `550` a sender the provider
has not verified, and a connection timeout means the port is blocked — try 465
with `'secure' => 'ssl'`. Then check SPF and DKIM: mail that sends fine but
lands in spam is nearly always missing DNS records.

**Sessions drop, or you are logged out on every page.**
Confirm the server clock is right (`date` in Terminal) — the idle timeout
compares timestamps. Check that PHP can write its session directory; on some
hosts you must set `session.save_path` to a folder in your home directory.

**Backup page says mysqldump was not found.**
Expected on shared hosting, where `exec()` is disabled. Use the cron job in
step 9.
