<?php
/** Authenticated application shell: sidebar + topbar. */
use App\Core\Auth;
use App\Core\Notify;
use App\Models\Setting;

$siteName = (string)Setting::get('site_name', 'HRRAMS');
$unread   = Auth::check() ? Notify::unreadCount((int)Auth::id()) : 0;
?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($title ?? 'Dashboard') . ' · ' . $siteName) ?></title>
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= e(asset('img/favicon.svg')) ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body class="site-app">

<a href="#main" class="skip-link">Skip to main content</a>

<div class="app-shell">
  <?php \App\Core\View::partial('sidebar'); ?>

  <div class="app-main">
    <header class="app-topbar">
      <button class="btn btn-icon d-lg-none" id="sidebarToggle" aria-label="Toggle navigation">
        <i class="bi bi-list"></i>
      </button>

      <?php \App\Core\View::partial('breadcrumbs', ['title' => $title ?? '']); ?>

      <div class="ms-auto d-flex align-items-center gap-2">
        <form class="topbar-search d-none d-md-block" action="<?= e(url(Auth::can('case.view_any') || Auth::can('case.view_assigned') ? 'cases' : 'my/reports')) ?>" method="get" role="search">
          <i class="bi bi-search"></i>
          <input type="search" name="q" class="form-control form-control-sm"
                 placeholder="Search cases, references…" aria-label="Search" value="<?= e($_GET['q'] ?? '') ?>">
        </form>

        <div class="dropdown">
          <button class="btn btn-icon position-relative" data-bs-toggle="dropdown" aria-label="Notifications">
            <i class="bi bi-bell"></i>
            <?php if ($unread > 0): ?>
              <span class="badge rounded-pill bg-danger notif-dot"><?= e((string)min($unread, 99)) ?></span>
            <?php endif; ?>
          </button>
          <div class="dropdown-menu dropdown-menu-end notif-menu shadow">
            <div class="d-flex justify-content-between align-items-center px-3 pt-2 pb-1">
              <strong class="small">Notifications</strong>
              <?php if ($unread > 0): ?>
                <form method="post" action="<?= e(url('notifications/read')) ?>" class="m-0">
                  <?= csrf() ?>
                  <button class="btn btn-link btn-sm p-0 text-decoration-none">Mark all read</button>
                </form>
              <?php endif; ?>
            </div>
            <div class="dropdown-divider"></div>
            <?php $items = Auth::check() ? Notify::unread((int)Auth::id(), 6) : []; ?>
            <?php if ($items === []): ?>
              <p class="text-muted small px-3 py-2 mb-0">You are all caught up.</p>
            <?php else: foreach ($items as $n): ?>
              <a class="dropdown-item notif-item" href="<?= e(url($n['link'] ?: 'notifications')) ?>">
                <div class="fw-semibold small"><?= e($n['title']) ?></div>
                <div class="text-muted small"><?= e(excerpt($n['body'], 70)) ?></div>
                <div class="text-muted" style="font-size:.72rem"><?= e(ago($n['created_at'])) ?></div>
              </a>
            <?php endforeach; endif; ?>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item small text-center" href="<?= e(url('notifications')) ?>">View all</a>
          </div>
        </div>

        <button class="btn btn-icon" id="themeToggle" aria-label="Toggle dark mode">
          <i class="bi bi-moon-stars"></i>
        </button>

        <div class="dropdown">
          <button class="btn user-chip" data-bs-toggle="dropdown" aria-label="Account menu">
            <span class="avatar"><?= e(initials(Auth::name())) ?></span>
            <span class="d-none d-sm-inline text-start">
              <span class="d-block small fw-semibold leading-1"><?= e(Auth::name()) ?></span>
              <span class="d-block text-muted" style="font-size:.72rem">
                <?= e(ucwords(str_replace('_', ' ', implode(', ', Auth::roles())))) ?>
              </span>
            </span>
            <i class="bi bi-chevron-down small"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow">
            <li><a class="dropdown-item" href="<?= e(url('my/profile')) ?>"><i class="bi bi-person me-2"></i>My profile</a></li>
            <li><a class="dropdown-item" href="<?= e(url('my/reports')) ?>"><i class="bi bi-file-earmark-text me-2"></i>My reports</a></li>
            <li><a class="dropdown-item" href="<?= e(url('/')) ?>"><i class="bi bi-globe me-2"></i>Public site</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <form method="post" action="<?= e(url('logout')) ?>" class="px-1">
                <?= csrf() ?>
                <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button>
              </form>
            </li>
          </ul>
        </div>
      </div>
    </header>

    <main id="main" class="app-content">
      <?php \App\Core\View::partial('flash'); ?>
      <?= $content ?>
    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<script src="<?= e(asset('js/charts.js')) ?>"></script>
<?php if (!empty($scripts)) { echo $scripts; } ?>
</body>
</html>
