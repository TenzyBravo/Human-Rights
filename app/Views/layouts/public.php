<?php
/** @var string $content */
use App\Core\Auth;
use App\Models\Setting;

$siteName = (string)Setting::get('site_name', 'HRRAMS');
$pageTitle = isset($title) ? $title . ' · ' . $siteName : $siteName;
?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="<?= e(Setting::get('site_tagline', 'Human Rights Reporting & Awareness Management System')) ?>">
<title><?= e($pageTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body class="site-public">

<a href="#main" class="skip-link">Skip to main content</a>

<nav class="navbar navbar-expand-lg site-nav sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(url('/')) ?>">
      <span class="brand-mark"><i class="bi bi-shield-check"></i></span>
      <span class="brand-text"><?= e($siteName) ?></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"
            aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <li class="nav-item"><a class="nav-link<?= activeIf('campaigns') ?>" href="<?= e(url('campaigns')) ?>">Campaigns</a></li>
        <li class="nav-item"><a class="nav-link<?= activeIf('resources') ?>" href="<?= e(url('resources')) ?>">Resources</a></li>
        <li class="nav-item"><a class="nav-link<?= activeIf('news') ?>" href="<?= e(url('news')) ?>">News</a></li>
        <li class="nav-item"><a class="nav-link<?= activeIf('events') ?>" href="<?= e(url('events')) ?>">Events</a></li>
        <li class="nav-item"><a class="nav-link<?= activeIf('statistics') ?>" href="<?= e(url('statistics')) ?>">Statistics</a></li>
        <li class="nav-item"><a class="nav-link<?= activeIf('track') ?>" href="<?= e(url('track')) ?>">Track report</a></li>
        <li class="nav-item ms-lg-2">
          <a class="btn btn-primary btn-sm px-3" href="<?= e(url('report')) ?>">
            <i class="bi bi-megaphone me-1"></i>Report a violation
          </a>
        </li>
        <?php if (Auth::check()): ?>
          <li class="nav-item ms-lg-2">
            <a class="btn btn-outline-secondary btn-sm px-3" href="<?= e(url(Auth::homeRoute())) ?>">
              <i class="bi bi-speedometer2 me-1"></i>My area
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item ms-lg-2">
            <a class="btn btn-outline-secondary btn-sm px-3" href="<?= e(url('login')) ?>">Sign in</a>
          </li>
        <?php endif; ?>
        <li class="nav-item ms-lg-1">
          <button class="btn btn-icon" type="button" id="themeToggle" aria-label="Toggle dark mode">
            <i class="bi bi-moon-stars"></i>
          </button>
        </li>
      </ul>
    </div>
  </div>
</nav>

<main id="main">
  <?php \App\Core\View::partial('flash'); ?>
  <?= $content ?>
</main>

<footer class="site-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <span class="brand-mark"><i class="bi bi-shield-check"></i></span>
          <span class="fw-semibold fs-5"><?= e($siteName) ?></span>
        </div>
        <p class="text-muted small mb-3"><?= e(Setting::get('site_tagline', '')) ?></p>
        <p class="small mb-1"><i class="bi bi-telephone me-2"></i>Hotline <?= e(Setting::get('hotline', '933')) ?></p>
        <p class="small mb-0"><i class="bi bi-envelope me-2"></i><?= e(Setting::get('contact_email', '')) ?></p>
      </div>
      <div class="col-6 col-lg-2">
        <h6 class="footer-title">Report</h6>
        <ul class="footer-links">
          <li><a href="<?= e(url('report')) ?>">File a report</a></li>
          <li><a href="<?= e(url('track')) ?>">Track a report</a></li>
          <li><a href="<?= e(url('faqs')) ?>">FAQs</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h6 class="footer-title">Learn</h6>
        <ul class="footer-links">
          <li><a href="<?= e(url('campaigns')) ?>">Campaigns</a></li>
          <li><a href="<?= e(url('resources')) ?>">Resources</a></li>
          <li><a href="<?= e(url('stories')) ?>">Success stories</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h6 class="footer-title">About</h6>
        <ul class="footer-links">
          <li><a href="<?= e(url('about')) ?>">Who we are</a></li>
          <li><a href="<?= e(url('statistics')) ?>">Statistics</a></li>
          <li><a href="<?= e(url('contact')) ?>">Contact us</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h6 class="footer-title">Account</h6>
        <ul class="footer-links">
          <li><a href="<?= e(url('login')) ?>">Sign in</a></li>
          <li><a href="<?= e(url('register')) ?>">Register</a></li>
        </ul>
      </div>
    </div>
    <hr class="my-4">
    <div class="d-flex flex-wrap justify-content-between gap-2 small text-muted">
      <span>&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</span>
      <span>In an emergency, call the hotline. Reports may be filed anonymously.</span>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<?php if (!empty($scripts)) { echo $scripts; } ?>
</body>
</html>
