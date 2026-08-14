# Installation Guide

## 1. Requirements

| Component | Minimum | Notes |
|---|---|---|
| PHP | 8.1 | 8.3 recommended. Extensions: `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `json` |
| MySQL | 8.0 | MariaDB 10.6+ also works |
| Web server | Apache 2.4 | `mod_rewrite`, `mod_headers` enabled |
| Browser | Any modern browser | Chrome, Firefox, Safari, Edge |

Verify the PHP extensions:

```bash
php -m | grep -E 'pdo_mysql|mbstring|fileinfo|openssl'
```

---

## 2. Get the code

```bash
git clone https://github.com/TenzyBravo/Human-Rights.git hrrams
cd hrrams
```

---

## 3. Create the database

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p hrrams < database/seed.sql
```

`schema.sql` creates the `hrrams` database, 23 tables and one reporting view.
`seed.sql` loads roles, permissions, the ten provinces and their districts, twelve report
categories, system settings, demo awareness content and five demo user accounts.

On MAMP, `mysql` lives at `/Applications/MAMP/Library/bin/mysql80/bin/mysql`. On XAMPP for
Windows it is `C:\xampp\mysql\bin\mysql.exe`. You can also import both files through
phpMyAdmin — schema first, then seed.

---

## 4. Configure

Never edit `config/config.php` for a specific machine. Copy it and edit the copy — the
application merges `config.local.php` over the defaults, and that file is gitignored:

```bash
cp config/config.php config/config.local.php
```

The values that matter:

```php
'db' => [
    'host' => '127.0.0.1',
    'port' => 8889,        // MAMP. Use 3306 for XAMPP / WAMP / LAMP.
    'name' => 'hrrams',
    'user' => 'root',
    'pass' => 'root',      // MAMP default. XAMPP default is an empty string.
],

'security' => [
    // Generate with: openssl rand -base64 32
    'app_key' => 'base64:PASTE_A_FRESH_32_BYTE_KEY_HERE',
],

'app' => [
    'debug' => true,       // false in production
    'env'   => 'development',
],
```

> **The encryption key.** `app_key` encrypts sensitive database columns. Generate it once per
> deployment and back it up somewhere safe. If you change it later, previously encrypted
> values become unreadable.

---

## 5. Make storage writable

```bash
chmod -R 775 storage
```

`storage/uploads` holds evidence files, `storage/logs` diagnostic and mail logs, and
`storage/backups` database dumps. All three are outside the web root and each carries a
`.htaccess` denying HTTP access.

---

## 6. Serve the application

**`public/` must be the document root.** Everything else — application code, configuration,
evidence, backups — sits above it and must never be reachable over HTTP.

### MAMP (macOS)

Either set the document root directly:

1. MAMP → Preferences → Server → Document Root → select the project's `public` folder.
2. Start the servers. Open `http://localhost:8888`.

Or keep the MAMP root and symlink the project in:

```bash
ln -s "$(pwd)/public" /Applications/MAMP/htdocs/hrrams
```

Then open `http://localhost:8888/hrrams`. The application detects its own base URL, so no
extra configuration is needed for a subdirectory install.

### XAMPP / WAMP (Windows)

Add a virtual host in `apache/conf/extra/httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName hrrams.local
    DocumentRoot "C:/xampp/htdocs/hrrams/public"
    <Directory "C:/xampp/htdocs/hrrams/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Add `127.0.0.1 hrrams.local` to `C:\Windows\System32\drivers\etc\hosts`, restart Apache, and
open `http://hrrams.local`.

### LAMP (Ubuntu / Debian)

```apache
<VirtualHost *:80>
    ServerName hrrams.example.org
    DocumentRoot /var/www/hrrams/public
    <Directory /var/www/hrrams/public>
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog  ${APACHE_LOG_DIR}/hrrams-error.log
    CustomLog ${APACHE_LOG_DIR}/hrrams-access.log combined
</VirtualHost>
```

```bash
sudo a2enmod rewrite headers
sudo chown -R www-data:www-data /var/www/hrrams/storage
sudo systemctl reload apache2
```

### PHP built-in server (development only)

```bash
php -S localhost:8000 -t public
```

---

## 7. Verify the install

1. Open the site — the public homepage loads with statistics tiles.
2. Sign in as `admin@hrrams.test` / `Admin@12345`. The admin dashboard renders with charts.
3. Sign out, then file a test report from **Report a violation**. You receive a reference
   number of the form `HR-YYYY-000001`.
4. Track it from **Track report** using that reference.
5. Sign in as the supervisor and confirm the new case appears under **Unassigned**.
6. Assign it to the officer, sign in as the officer, and confirm it appears on their dashboard.
7. Check `storage/logs/mail.log` — with the default `log` mail driver, every notification is
   written there instead of being sent.

---

## 8. Before you go live

- [ ] Delete or re-password every demo account (Administration → Users)
- [ ] Set `app.debug` to `false` and `app.env` to `production`
- [ ] Generate a fresh `security.app_key`
- [ ] Change the MySQL password, and create a dedicated non-root user for the app
- [ ] Install a TLS certificate; set `app.force_https` and `security.cookie_secure` to `true`
- [ ] Switch `mail.driver` from `log` to `smtp` and supply credentials
- [ ] Confirm `storage/` and `config/` return 403 when requested over HTTP
- [ ] Take a first backup and test restoring it

The full list is in [DEPLOYMENT.md](DEPLOYMENT.md).

---

## Troubleshooting

**"Database connection failed"**
The port is usually the culprit — MAMP uses 8889, everything else 3306. Confirm MySQL is
running (`nc -z 127.0.0.1 8889`) and that the credentials in `config/config.local.php`
match. Details are in `storage/logs/db.log`.

**Every URL except the homepage returns 404**
`mod_rewrite` is not enabled, or `AllowOverride All` is missing from the `<Directory>` block,
so `public/.htaccess` is being ignored.

**Styling is missing and links point at the wrong path**
The document root is the project folder rather than `public/`. Fix the document root, or set
`app.base_url` explicitly in `config.local.php`.

**"Upload directory is not writable"**
Run `chmod -R 775 storage` and make sure the directory is owned by the web server user
(`www-data` on Debian/Ubuntu, `_www` on macOS).

**Uploads over a few megabytes fail silently**
PHP's own limits cap them before the application sees the file. Raise `upload_max_filesize`
and `post_max_size` in `php.ini`, then restart Apache. `public/.htaccess` sets 20M/60M where
`php_flag` directives are permitted.

**Session expires immediately, or you are logged out on every page**
Check that PHP can write to its session directory, and that the system clock is correct —
the idle timeout compares timestamps.
