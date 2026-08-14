<?php
/** Clean layout for receipts and printable/PDF reports. */
use App\Models\Setting;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($title ?? 'Document') . ' · ' . Setting::get('site_name', 'HRRAMS')) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
<style>
  body { background:#fff; font-family:Inter, Arial, sans-serif; color:#212529 }
  .doc { max-width: 860px; margin: 0 auto; padding: 28px }
  .doc-head { border-bottom:3px solid #0d6efd; padding-bottom:14px; margin-bottom:22px }
  table { width:100% }
  @media print { .no-print { display:none !important } .doc { padding:0 } }
</style>
</head>
<body>
<div class="doc">
  <div class="d-flex justify-content-end gap-2 mb-3 no-print">
    <button class="btn btn-primary btn-sm" onclick="window.print()">
      <i class="bi bi-printer me-1"></i>Print / Save as PDF
    </button>
    <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/')) ?>">Close</a>
  </div>
  <header class="doc-head d-flex justify-content-between align-items-start">
    <div>
      <h1 class="h4 mb-1"><?= e(Setting::get('site_name', 'HRRAMS')) ?></h1>
      <p class="text-muted small mb-0"><?= e(Setting::get('site_tagline', '')) ?></p>
    </div>
    <div class="text-end small text-muted">
      <div>Generated <?= e(date('d M Y H:i')) ?></div>
      <div><?= e(Setting::get('contact_email', '')) ?></div>
    </div>
  </header>
  <?= $content ?>
</div>
</body>
</html>
