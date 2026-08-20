<?php
/**
 * HRRAMS configuration.
 * Copy to config.local.php to override without touching version control.
 */
declare(strict_types=1);

$root = dirname(__DIR__);

$config = [
    'app' => [
        'name'        => 'HRRAMS',
        'env'         => 'development',      // development | production
        'debug'       => true,               // MUST be false in production
        'timezone'    => 'Africa/Lusaka',
        'root'        => $root,
        'storage'     => $root . '/storage',
        'uploads'     => $root . '/storage/uploads',
        'logs'        => $root . '/storage/logs',
        'backups'     => $root . '/storage/backups',
        'views'       => $root . '/app/Views',
        // Leave null to auto-detect from the request.
        'base_url'    => null,
        'force_https' => false,              // true once a certificate is installed
    ],

    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 8889,                   // MAMP default; XAMPP/LAMP use 3306
        'name'    => 'hrrams',
        'user'    => 'root',
        'pass'    => 'root',
        'charset' => 'utf8mb4',
    ],

    'security' => [
        // 32-byte key for AES-256 encryption of sensitive columns.
        // Regenerate for every deployment:  openssl rand -base64 32
        'app_key'            => 'CHANGE_ME_base64:0000000000000000000000000000000000000000000=',
        'session_name'       => 'HRRAMSSESSID',
        'session_timeout'    => 1800,        // seconds of inactivity
        'session_regenerate' => 300,         // rotate id every N seconds
        'max_login_attempts' => 5,
        'lockout_minutes'    => 15,
        'password_min'       => 10,
        'csrf_token_name'    => '_token',
        'cookie_secure'      => false,       // true under HTTPS
        'cookie_samesite'    => 'Lax',
    ],

    'uploads' => [
        'max_bytes'     => 20 * 1024 * 1024, // 20 MB per file
        'max_files'     => 10,
        'allowed_mimes' => [
            'image/jpeg'      => 'image',
            'image/png'       => 'image',
            'image/webp'      => 'image',
            'image/gif'       => 'image',
            'video/mp4'       => 'video',
            'video/quicktime' => 'video',
            'audio/mpeg'      => 'audio',
            'audio/wav'       => 'audio',
            'audio/mp4'       => 'audio',
            'application/pdf' => 'document',
            'application/msword' => 'document',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'document',
        ],
    ],

    'mail' => [
        // log  — write to storage/logs/mail.log, send nothing (development default)
        // smtp — authenticated SMTP (use this in production)
        // mail — PHP mail(); many hosts block it and deliverability is poor
        'driver'     => 'log',
        'from_email' => 'no-reply@hrrams.test',
        'from_name'  => 'HRRAMS Notifications',
        'smtp'       => [
            'host'        => 'localhost',
            'port'        => 587,
            'user'        => '',
            'pass'        => '',
            'secure'      => 'tls',   // 'tls' = STARTTLS on 587, 'ssl' = implicit on 465
            'verify_peer' => true,    // only turn off to diagnose a certificate problem
            'helo'        => null,    // defaults to the application's own hostname
        ],
        // Provider settings, for reference when filling in config.local.php:
        //   Brevo     smtp-relay.brevo.com    587  tls   user = login, pass = SMTP key
        //   Mailgun   smtp.mailgun.org        587  tls   user = postmaster@your-domain
        //   SendGrid  smtp.sendgrid.net       587  tls   user = literally "apikey"
        //   Resend    smtp.resend.com         587  tls   user = literally "resend"
        //   Gmail     smtp.gmail.com          587  tls   user = address, pass = app password
        //   cPanel    mail.your-domain.com    465  ssl   user = the full mailbox address
    ],

    'pagination' => ['per_page' => 15],
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $override = require $local;
    if (is_array($override)) {
        $config = array_replace_recursive($config, $override);
    }
}

return $config;
