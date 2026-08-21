<?php
/** Split-screen layout for sign in / register / password reset. */
use App\Models\Setting;
$siteName = (string)Setting::get('site_name', 'HRRAMS');
?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($title ?? 'Sign in') . ' · ' . $siteName) ?></title>
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= e(asset('img/favicon.svg')) ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body class="site-auth">
<div class="auth-wrap">

  <aside class="auth-brand">
    <div class="auth-brand-inner">
      <a href="<?= e(url('/')) ?>" class="d-inline-flex align-items-center gap-2 text-decoration-none text-white mb-4">
        <span class="brand-mark brand-mark-light"><i class="bi bi-shield-check"></i></span>
        <span class="fs-4 fw-semibold"><?= e($siteName) ?></span>
      </a>
      <h1 class="display-6 fw-semibold mb-3">Every right deserves a voice.</h1>
      <p class="lead opacity-75 mb-4">
        A secure platform for reporting human rights violations, tracking investigations
        and building awareness across every province.
      </p>
      <ul class="auth-points">
        <li><i class="bi bi-lock"></i><span>Encrypted, confidential submissions</span></li>
        <li><i class="bi bi-eye-slash"></i><span>Anonymous reporting supported</span></li>
        <li><i class="bi bi-clock-history"></i><span>Transparent case tracking</span></li>
        <li><i class="bi bi-people"></i><span>Community awareness programmes</span></li>
      </ul>
      <a href="<?= e(url('report')) ?>" class="btn btn-light btn-sm mt-4 px-3">
        <i class="bi bi-megaphone me-1"></i>Report without an account
      </a>
    </div>
  </aside>

  <section class="auth-panel">
    <div class="auth-panel-inner">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="<?= e(url('/')) ?>" class="text-decoration-none small">
          <i class="bi bi-arrow-left me-1"></i>Back to site
        </a>
        <button class="btn btn-icon" id="themeToggle" aria-label="Toggle dark mode">
          <i class="bi bi-moon-stars"></i>
        </button>
      </div>
      <?php \App\Core\View::partial('flash'); ?>
      <?= $content ?>
    </div>
  </section>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
