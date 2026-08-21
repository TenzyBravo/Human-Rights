# Hosting HRRAMS on Hostinger

Hostinger uses its own control panel, **hPanel**, not cPanel — the menu names
differ, and one difference changes the installation itself. Follow this guide
rather than [HOSTING-CPANEL.md](HOSTING-CPANEL.md) if you are on Hostinger.

Budget **45–90 minutes** for a first deployment.

---

## The one thing that makes Hostinger different

**You cannot change the document root on Hostinger's shared plans.** Their own
documentation is explicit: *"The home directory cannot be changed on our Web,
WordPress, and Cloud hosting packages due to control panel restrictions."* Every
site is served from `public_html`.

That matters here because HRRAMS deliberately keeps its code, configuration and
evidence uploads *outside* the web root. On a host where you can move the
document root you simply point it at `public/` and you are done. On Hostinger you
cannot, so the layout is:

```
/home/u123456789/
├── hrrams/                ← application: NOT web-accessible
│   ├── app/
│   ├── config/            ← database password, encryption key
│   ├── database/
│   ├── storage/           ← evidence files, logs
│   └── tools/
└── domains/yourdomain.com/
    └── public_html/       ← web root: only these three things
        ├── index.php
        ├── .htaccess
        ├── hrrams-path.php   ← tells index.php where the app lives
        └── assets/
```

`public/index.php` looks for `hrrams-path.php` beside it, and that file returns
the path to the application folder. No Apache modules involved, so it works
whatever Hostinger has enabled.

This keeps complainant data out of the web root, which is the point.

> **If you upgrade to a Hostinger VPS later**, you get full control of the
> document root and should switch to the simpler layout — see
> [DEPLOYMENT.md](DEPLOYMENT.md).

---

## Before you start

| You need | Where |
|---|---|
| Hostinger account with an active hosting plan | hpanel.hostinger.com |
| A domain or subdomain pointed at Hostinger | Hostinger, or your registrar's DNS |
| An SMTP account | [brevo.com](https://www.brevo.com) — free tier is 300 emails/day |
| Your encryption key | Generate one; see step 5 |

---

## Step 1 — Create the database

**hPanel → Websites → Dashboard →** (sidebar) **Databases → Management**

1. Under *Create a New MySQL Database*:
   - Database name: `hrrams`
   - Username: `hrrams_app`
   - Password: use the generator
2. Click **Create**.

Hostinger prefixes both with your account number, giving something like
`u123456789_hrrams` and `u123456789_hrrams_app`. **Write down all three values** —
database name, username, password. You need them in step 5.

The user is attached to the database with full privileges automatically; there is
no separate "add user to database" step as on cPanel.

---

## Step 2 — Import the schema

**hPanel → Databases → phpMyAdmin →** click **Enter phpMyAdmin** next to your
database.

1. In the left sidebar, click your `u123456789_hrrams` database so it is
   selected. *(This matters — the import goes wherever you are pointed.)*
2. **Import** tab → **Choose File** → `database/schema-cpanel.sql` → **Import**
3. Repeat with `database/seed-cpanel.sql`

**Use the `-cpanel` files, not `schema.sql` / `seed.sql`.** The originals start
with `CREATE DATABASE hrrams; USE hrrams;`, which fails on a prefixed database.
The `-cpanel` versions have those lines removed and are otherwise identical.
They work on Hostinger for exactly the same reason.

**Verify:** the sidebar should list **23 tables**. In the **SQL** tab, this must
return `26`:

```sql
SELECT COUNT(*) FROM permissions;
```

---

## Step 3 — Upload the application

**hPanel → Websites → Dashboard →** (sidebar) **Files → File Manager**

1. Navigate to your **home directory** — the level that contains `domains`, *not*
   inside `public_html`.
2. **Upload Files** → choose `hrrams-deploy.zip` → wait for it to finish.
3. Right-click the uploaded zip → **Extract**.
4. Rename the extracted folder (e.g. `hrrams-20260820-2122`) to **`hrrams`**.
5. Delete the zip.

You should now have `/home/u123456789/hrrams/` containing `app`, `config`,
`database`, `docs`, `public`, `storage`, `tools`.

> Turn on hidden files now: File Manager **Settings** (gear icon) → **Show hidden
> files**. You will need to see `.htaccess`, and not seeing it causes a very
> confusing "only the homepage works" symptom later.

### Find your exact home path

**hPanel → Websites → Dashboard → FTP Accounts** shows the full path, e.g.
`/home/u123456789`. Note it — step 4 needs it exactly.

---

## Step 4 — Put the web files in `public_html`

1. In File Manager, open `hrrams/public/`.
2. Select **everything inside it** — `index.php`, `.htaccess`, `.user.ini`,
   `hrrams-path.example.php`, and the `assets` folder — and **Copy** them to:

   ```
   /home/u123456789/domains/yourdomain.com/public_html
   ```

   (Or `/home/u123456789/public_html` on some older accounts — whichever path
   FTP Accounts showed you.)

   Copy the **contents**, not the `public` folder itself.

3. In `public_html`, rename `hrrams-path.example.php` to **`hrrams-path.php`**.
4. Edit `hrrams-path.php` and set your real path — the folder containing `app/`:

   ```php
   <?php
   return '/home/u123456789/hrrams';
   ```

   No trailing slash. This is the single line that connects the two halves.

5. Delete `hrrams/public/` so there is no confusion about which copy is live.

If you get this path wrong the site shows a plain message telling you exactly
what to fix — not a blank page.

---

## Step 5 — Create the configuration file

In File Manager, open `hrrams/config/`, create a **New File** called
`config.local.php`, and edit it:

```php
<?php
return [
    'app' => [
        'env'         => 'production',
        'debug'       => false,
        'force_https' => false,                        // switch on in step 7
        'base_url'    => 'https://yourdomain.com',     // ← no trailing slash
    ],

    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'u123456789_hrrams',       // ← from step 1
        'user' => 'u123456789_hrrams_app',   // ← from step 1
        'pass' => 'the-generated-password',  // ← from step 1
    ],

    'security' => [
        'app_key'       => 'base64:PASTE_YOUR_OWN_KEY_HERE',
        'cookie_secure' => false,                      // switch on in step 7
    ],

    'mail' => [
        'driver'     => 'smtp',
        'from_email' => 'no-reply@yourdomain.com',
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

Then set its permissions to **600** (right-click → *Permissions*).

**Generate your own `app_key`.** On your own machine:

```bash
openssl rand -base64 32
```

Paste the result after `base64:`. Store a copy somewhere separate from the server
— a password manager. It encrypts sensitive database columns; if you lose it or
change it later, those values become permanently unreadable, silently.

Two Hostinger-specific notes:

- **`db.host` is `localhost`.** Hostinger's MySQL is on the same machine.
- **Leave `force_https` off until the certificate exists** (step 7), or you will
  get a redirect loop.

---

## Step 6 — PHP version and limits

**hPanel → Websites → Dashboard →** (sidebar) **Advanced → PHP Configuration**

On the **PHP version** tab, select **8.3** (or 8.2 / 8.1) and save.

On the **PHP options** tab, set:

| Setting | Value |
|---|---|
| `uploadMaxFilesize` | 20M (or higher) |
| `postMaxSize` | 64M |
| `maxExecutionTime` | 60 |
| `memoryLimit` | 256M |
| `allowUrlFopen` | on |

Also confirm these extensions are enabled (usually on by default):
`pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `json`.

> The `public/.user.ini` file in the archive sets the same limits, but hPanel's
> values win on Hostinger. Set them here.

### Make storage writable

In File Manager, right-click `hrrams/storage` → **Permissions** → **755**, tick
**Apply to subdirectories** → confirm.

Do not use 777. PHP runs as your own user here, so 755 is enough — and this
directory holds evidence files.

**Now load your domain.** You should see the homepage with statistics tiles. If
not, jump to [Troubleshooting](#troubleshooting).

---

## Step 7 — TLS, then lock it in

**hPanel → Websites → Dashboard →** (sidebar) **Security → SSL**

Hostinger installs a free Let's Encrypt certificate automatically for domains
pointed at them; if it has not, click **Install SSL**. Wait for status *Active*.

Load `https://yourdomain.com` and confirm the padlock.

**Only then** edit `hrrams/config/config.local.php` and set both:

```php
'force_https'   => true,
'cookie_secure' => true,
```

Finally, edit `public_html/.htaccess` and uncomment the HSTS line near the top.

---

## Step 8 — Make email actually arrive

Sending works as soon as step 5 is saved, but without DNS records your messages
land in spam — and users conclude the system is broken.

**hPanel → Domains → DNS / Nameservers** (or your registrar, if DNS is not with
Hostinger). Add the records Brevo gives you:

| Type | Purpose |
|---|---|
| TXT | **SPF**, e.g. `v=spf1 include:spf.brevo.com ~all` |
| TXT / CNAME | **DKIM**, exactly as Brevo specifies |
| TXT | **DMARC**, start with `v=DMARC1; p=none; rua=mailto:you@yourdomain.com` |

> **If email times out**, switch to implicit TLS: `'port' => 465` and
> `'secure' => 'ssl'` in your config.
>
> **Using Hostinger's own email instead of Brevo?** Set `host` to
> `smtp.hostinger.com`, `port` 465, `secure` `'ssl'`, and use the full mailbox
> address as the username.

---

## Step 9 — Verify and secure

### Run the readiness check

If your plan includes SSH (**hPanel → Advanced → SSH Access**; Business plans and
above):

```bash
ssh u123456789@your-server-ip -p 65002
cd ~/hrrams && php tools/preflight.php --mail you@youraddress.com
```

**No SSH?** Use **hPanel → Advanced → Cron Jobs** instead. Create a job set to
run once, a few minutes out:

```
php /home/u123456789/hrrams/tools/preflight.php --mail you@youraddress.com
```

Put your address in the cron notification field so the output is emailed to you,
then delete the job after it runs.

It checks the PHP version and extensions, that debug is off, that `app_key` is
not a placeholder, the database and schema, storage writability, and mail
configuration — then sends a real test message. Expect **NOT READY** the first
time: the demo accounts are still there.

### Replace the demo accounts

1. Sign in at `https://yourdomain.com/login` as
   `admin@hrrams.test` / `Admin@12345`
2. **Administration → Users → Add user** — create your own administrator with a
   real address, a strong password, role **System Administrator**, status
   **Active**
3. Sign out, sign back in as **yourself**, confirm you have admin access
4. Delete or re-password **all five** demo accounts: `admin@`, `supervisor@`,
   `officer@`, `awareness@`, `citizen@hrrams.test`
5. **Administration → Settings** — organisation name, contact email, phone,
   hotline

Do this **before** you give anyone the URL. `Admin@12345` on a live site hands
over every complainant record in the database.

Re-run preflight until it reports **READY**.

### Confirm the private files really are private

This is the check that matters most in this layout. Load each URL — every one
must return **403** or **404**, never content:

```
https://yourdomain.com/hrrams-path.php
https://yourdomain.com/.user.ini
https://yourdomain.com/config/config.local.php
https://yourdomain.com/storage/uploads/
https://yourdomain.com/app/Core/App.php
```

The last three should 404 simply because those folders are not under
`public_html` — that is the layout working. `hrrams-path.php` returns a path
string rather than printing it, so even if served it reveals nothing, and
`.htaccess` denies dotfiles and `.ini` files.

If any of them returns readable content, stop and re-check step 4.

### Walk the system

- [ ] Homepage loads with statistics tiles
- [ ] Register a test account — the verification email **arrives** (check spam)
- [ ] The link activates the account
- [ ] File a report → a reference like `HR-2026-000001` comes back
- [ ] **Track report** finds that reference
- [ ] As supervisor: the case appears under **Unassigned**; assign it → the
      officer receives the email
- [ ] As that officer: add a note, upload a file, download it back
- [ ] A second officer is refused that case (403)
- [ ] Password reset: email arrives, link works once
- [ ] CSV, Excel and PDF exports download correctly

---

## Step 10 — Backups

The in-app backup (**Administration → Backup**) will report that `mysqldump`
could not be found. Hostinger disables `exec()` on shared plans; that is expected,
not a fault.

Hostinger takes its own weekly (Premium) or daily (Business) backups — find them
under **Files → Backups**. Treat those as a safety net, not your backup strategy:
they are on the same provider, and restoring is all-or-nothing.

Add your own, via **hPanel → Advanced → Cron Jobs**:

```bash
# Daily 02:15 — database
mkdir -p ~/backups && mysqldump --single-transaction -u u123456789_hrrams_app -p'THEPASSWORD' u123456789_hrrams | gzip > ~/backups/db-$(date +\%F).sql.gz && find ~/backups -name 'db-*.sql.gz' -mtime +30 -delete
```

```bash
# Sunday 03:00 — evidence files
tar czf ~/backups/uploads-$(date +\%F).tar.gz -C /home/u123456789/hrrams/storage uploads
```

`~/backups` sits outside `public_html`, so it is not web-accessible — verify
that. And **download copies regularly**: a backup that only exists on the server
it protects is not a backup.

Restore procedure: [BACKUP-RECOVERY.md](BACKUP-RECOVERY.md).

---

## Troubleshooting

**"HRRAMS is not installed correctly."**
This is the app telling you `hrrams-path.php` is missing or points somewhere
wrong. Check the path against **FTP Accounts**, confirm no trailing slash, and
confirm the folder it names actually contains `app/Core/App.php`.

**Homepage works, every other URL 404s.**
`.htaccess` did not copy across (File Manager hides dotfiles until you enable
*Show hidden files*), or `mod_rewrite` is not applying. Confirm `.htaccess` is in
`public_html`, then contact Hostinger support.

**Blank white page.**
PHP fatal error with `display_errors` off — which is correct for production. Read
`hrrams/storage/logs/php-error.log`, and check hPanel → **Advanced → PHP
Configuration** that the version is 8.1 or newer.

**"Database connection failed".**
Use the **prefixed** names (`u123456789_hrrams`, not `hrrams`). Host is
`localhost`. Re-check the password in **Databases → Management** — regenerate it
there if unsure and update `config.local.php` to match.

**Uploads fail, or fail above ~2 MB.**
Set the limits in **Advanced → PHP Configuration → PHP options**; hPanel's values
override `.user.ini` here.

**Emails do not arrive.**
Read `hrrams/storage/logs/mail.log` — the SMTP transcript is written there with
credentials redacted. `535` means bad credentials, `550` an unverified sender,
and a timeout means the port is blocked (try 465 with `'secure' => 'ssl'`). Mail
that sends but lands in spam is nearly always missing SPF/DKIM — step 8.

**Sessions drop, or you are logged out constantly.**
Confirm `storage/` is writable and the server clock is correct. If it persists,
set `session.save_path` to a folder inside your home directory in PHP options.

---

## Sources

Hostinger's own documentation for the two constraints this guide works around:

- [What is the path to your website's root directory and how to change it](https://www.hostinger.com/support/1583494-what-is-the-path-to-your-website-s-root-home-directory-and-how-to-change-it-in-hostinger/) — confirms the document root cannot be changed on shared plans
- [How to change values of PHP parameters in hPanel](https://support.hostinger.com/en/articles/4622479-how-to-change-values-of-php-parameters-in-hpanel) — PHP Configuration → PHP options
