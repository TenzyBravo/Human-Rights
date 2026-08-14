# Deployment Guide

For putting HRRAMS into production on an Ubuntu/Debian LAMP server. Adapt the paths for other
distributions; the principles do not change.

---

## 1. Server preparation

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y apache2 mysql-server \
    php php-mysql php-mbstring php-xml php-curl php-zip \
    certbot python3-certbot-apache unattended-upgrades
sudo a2enmod rewrite headers ssl expires deflate
```

Secure MySQL:

```bash
sudo mysql_secure_installation
```

Firewall — allow only what is needed:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Apache Full'
sudo ufw enable
```

MySQL must not be reachable from outside. Confirm `bind-address = 127.0.0.1` in
`/etc/mysql/mysql.conf.d/mysqld.cnf`.

---

## 2. Deploy the code

```bash
sudo mkdir -p /var/www/hrrams
sudo chown "$USER":www-data /var/www/hrrams
git clone https://github.com/TenzyBravo/Human-Rights.git /var/www/hrrams
cd /var/www/hrrams
```

Ownership and permissions — the web server needs to write only to `storage`:

```bash
sudo chown -R root:www-data /var/www/hrrams
sudo find /var/www/hrrams -type d -exec chmod 750 {} \;
sudo find /var/www/hrrams -type f -exec chmod 640 {} \;
sudo chown -R www-data:www-data /var/www/hrrams/storage
sudo chmod -R 770 /var/www/hrrams/storage
```

---

## 3. Database

Create the database and a **dedicated non-root user** with only the privileges the application
actually uses:

```sql
CREATE DATABASE hrrams CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'hrrams_app'@'localhost' IDENTIFIED BY 'a-long-random-password';
GRANT SELECT, INSERT, UPDATE, DELETE ON hrrams.* TO 'hrrams_app'@'localhost';
FLUSH PRIVILEGES;
```

The app never issues DDL at runtime, so it does not need `CREATE`, `ALTER` or `DROP`. Load the
schema as root, then let the app connect as `hrrams_app`:

```bash
sudo mysql < database/schema.sql
sudo mysql hrrams < database/seed.sql
```

> If you add `mysqldump`-based backups from the web interface, that requires `SELECT`,
> `LOCK TABLES`, `SHOW VIEW`, `TRIGGER` and `EVENT`. Prefer running backups from cron as a
> separate database user rather than widening the application's grants.

---

## 4. Production configuration

```bash
sudo -u root cp config/config.php config/config.local.php
sudo chmod 640 config/config.local.php
sudo chown root:www-data config/config.local.php
```

Edit `config/config.local.php`:

```php
return [
    'app' => [
        'env'         => 'production',
        'debug'       => false,
        'force_https' => true,
        'base_url'    => 'https://hrrams.example.org',
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'hrrams',
        'user' => 'hrrams_app',
        'pass' => 'a-long-random-password',
    ],
    'security' => [
        'app_key'       => 'base64:<output of: openssl rand -base64 32>',
        'cookie_secure' => true,
    ],
    'mail' => [
        'driver' => 'smtp',
        'from_email' => 'no-reply@hrrams.example.org',
        'smtp' => [
            'host' => 'smtp.example.org', 'port' => 587,
            'user' => 'no-reply@hrrams.example.org', 'pass' => '…',
            'secure' => 'tls',
        ],
    ],
];
```

**Back up `app_key` somewhere separate and durable.** Lose it and every AES-encrypted column
becomes unreadable.

> `Mailer` ships with `log` and PHP `mail()` transports. The `smtp` setting is honoured for
> configuration, but authenticated SMTP delivery is not implemented in `Mailer::send()` — if
> your host requires SMTP auth, either configure a local relay (Postfix as a satellite system,
> so `mail()` works) or add PHPMailer and swap the transport inside `Mailer::send()`. Verify
> delivery during commissioning rather than assuming it.

---

## 5. Apache virtual host

`/etc/apache2/sites-available/hrrams.conf`:

```apache
<VirtualHost *:80>
    ServerName hrrams.example.org
    Redirect permanent / https://hrrams.example.org/
</VirtualHost>

<VirtualHost *:443>
    ServerName hrrams.example.org
    DocumentRoot /var/www/hrrams/public

    SSLEngine on
    SSLProtocol -all +TLSv1.2 +TLSv1.3
    SSLHonorCipherOrder off

    <Directory /var/www/hrrams/public>
        AllowOverride All
        Require all granted
        Options -Indexes -MultiViews
    </Directory>

    # Belt and braces — these paths are also protected by their own .htaccess
    <DirectoryMatch "/var/www/hrrams/(app|config|database|storage)">
        Require all denied
    </DirectoryMatch>

    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header unset X-Powered-By
    ServerSignature Off

    ErrorLog  ${APACHE_LOG_DIR}/hrrams-error.log
    CustomLog ${APACHE_LOG_DIR}/hrrams-access.log combined
</VirtualHost>
```

```bash
sudo a2ensite hrrams
sudo a2dissite 000-default
sudo apache2ctl configtest && sudo systemctl reload apache2
```

Add `ServerTokens Prod` to `/etc/apache2/conf-available/security.conf`.

---

## 6. TLS certificate

```bash
sudo certbot --apache -d hrrams.example.org
sudo systemctl status certbot.timer   # confirm auto-renewal is active
```

Then uncomment the HSTS line in `public/.htaccess` and the HTTPS redirect block at the top of
that file.

---

## 7. PHP hardening

In `/etc/php/8.3/apache2/php.ini`:

```ini
display_errors = Off
log_errors = On
expose_php = Off
upload_max_filesize = 20M
post_max_size = 60M
max_file_uploads = 12
max_execution_time = 60
memory_limit = 256M

session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Lax
session.use_strict_mode = 1

; Disable functions the application does not use.
; NOTE: 'exec' is required by the in-app backup feature. If you back up from cron
; instead (recommended), add exec to this list as well.
disable_functions = passthru,shell_exec,system,proc_open,popen
```

```bash
sudo systemctl restart apache2
```

---

## 8. Scheduled tasks

`sudo crontab -e`:

```cron
# Nightly database backup, 02:15
15 2 * * * /usr/bin/mysqldump --defaults-extra-file=/root/.hrrams-backup.cnf \
    --single-transaction hrrams | gzip > /var/backups/hrrams/db-$(date +\%F).sql.gz

# Weekly evidence file backup, Sunday 03:00
0 3 * * 0 /usr/bin/tar czf /var/backups/hrrams/uploads-$(date +\%F).tar.gz \
    -C /var/www/hrrams/storage uploads

# Off-server copy, 04:00 — replace with your own destination
0 4 * * * /usr/bin/rsync -az --delete /var/backups/hrrams/ backup@offsite:/hrrams/

# Retain 30 days locally
30 4 * * * /usr/bin/find /var/backups/hrrams -type f -mtime +30 -delete

# Prune request logs quarterly (keep 90 days of activity_logs; keep audit_logs)
0 5 1 */3 * /usr/bin/mysql --defaults-extra-file=/root/.hrrams-backup.cnf hrrams \
    -e "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);"

# Clear spent password reset tokens, daily
45 5 * * * /usr/bin/mysql --defaults-extra-file=/root/.hrrams-backup.cnf hrrams \
    -e "DELETE FROM password_resets WHERE expires_at < NOW() OR used_at IS NOT NULL;"
```

`/root/.hrrams-backup.cnf`, mode `600`:

```ini
[client]
user=hrrams_backup
password=another-long-random-password
```

```bash
sudo mkdir -p /var/backups/hrrams && sudo chmod 700 /var/backups/hrrams
sudo chmod 600 /root/.hrrams-backup.cnf
```

---

## 9. Go-live checklist

**Security**

- [ ] Every demo account deleted or re-passworded
- [ ] `app.debug = false`, `app.env = 'production'`
- [ ] Fresh `app_key`, backed up off-server
- [ ] Application connects as `hrrams_app`, not root
- [ ] TLS installed; HTTP redirects to HTTPS; HSTS active
- [ ] `cookie_secure = true`, `force_https = true`
- [ ] `https://host/config/config.php` returns 403
- [ ] `https://host/storage/uploads/` returns 403
- [ ] `https://host/app/Core/App.php` returns 403
- [ ] `https://host/database/schema.sql` returns 403
- [ ] MySQL bound to localhost; firewall permits only 22, 80, 443
- [ ] Server headers do not disclose PHP or Apache versions

**Function**

- [ ] Homepage loads with statistics
- [ ] A report submits end to end and returns a reference number
- [ ] The acknowledgement receipt renders and prints
- [ ] Tracking finds that reference
- [ ] The case appears as Unassigned for the supervisor
- [ ] Assignment notifies the officer — check the inbox, not just the log
- [ ] Evidence uploads, and downloads only for authorised users
- [ ] A second officer is refused access to a case that is not theirs (403 + audit entry)
- [ ] Status changes notify the complainant
- [ ] CSV, Excel and PDF exports all produce correct data
- [ ] Password reset email arrives and the link works once
- [ ] Account locks after five failed sign-ins and clears after fifteen minutes

**Operations**

- [ ] Nightly backup runs and produces a non-empty file
- [ ] Off-server copy confirmed present at the destination
- [ ] A restore has been rehearsed on a staging copy
- [ ] Apache and PHP error logs reviewed and clean
- [ ] Log-review responsibility assigned to a named person, weekly
- [ ] Someone other than the deployer can perform a restore from the documentation alone

**Data protection**

- [ ] Retention period agreed with legal counsel and documented
- [ ] Staff briefed that complainant contact details are audit-logged on every view
- [ ] The consent wording on the reporting form reviewed and approved

---

## 10. Updating a live installation

```bash
cd /var/www/hrrams
sudo -u root mysqldump --defaults-extra-file=/root/.hrrams-backup.cnf \
    --single-transaction hrrams > /var/backups/hrrams/pre-update-$(date +%F-%H%M).sql
sudo git pull
# apply any migration shipped with the release, then:
sudo chown -R www-data:www-data storage
sudo systemctl reload apache2
```

Then re-run the **Function** checklist above. Roll back by restoring the pre-update dump and
`git checkout` of the previous tag.

Announce planned maintenance windows on the public homepage — people rely on this platform to
report things that are happening to them now.
