<?php /** @var array $grouped */
$labels = [
    'general'  => ['Organisation', 'bi-building', 'Names and contact details shown across the public site.'],
    'security' => ['Security policy', 'bi-shield-lock', 'Session, lockout and password rules enforced on every request.'],
    'uploads'  => ['Evidence uploads', 'bi-cloud-arrow-up', 'Limits applied when files are attached to reports.'],
    'cases'    => ['Case handling', 'bi-folder2-open', 'Defaults applied when a case is opened.'],
    'mail'     => ['Email', 'bi-envelope', 'Sender identity used for notifications.'],
];
?>
<div class="mb-4">
  <h1 class="h4 fw-semibold mb-1">System settings</h1>
  <p class="text-muted small mb-0">
    Changes take effect immediately and are recorded in the audit log.
  </p>
</div>

<form method="post" action="<?= e(url('admin/settings')) ?>">
  <?= csrf() ?>
  <div class="row g-4">
    <?php foreach ($grouped as $group => $items): ?>
      <?php [$heading, $icon, $blurb] = $labels[$group] ?? [ucfirst((string)$group), 'bi-sliders', '']; ?>
      <div class="col-lg-6">
        <div class="card p-4 h-100">
          <div class="d-flex gap-3 align-items-start mb-3">
            <div class="stat-icon tint-primary flex-shrink-0"><i class="bi <?= e($icon) ?>"></i></div>
            <div>
              <h2 class="h6 fw-semibold mb-0"><?= e($heading) ?></h2>
              <p class="text-muted fs-tiny mb-0"><?= e($blurb) ?></p>
            </div>
          </div>

          <?php foreach ($items as $key => $meta): ?>
            <div class="mb-3">
              <?php if ($meta['type'] === 'bool'): ?>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" role="switch"
                         id="<?= e($key) ?>" name="<?= e($key) ?>" value="1"
                         <?= $meta['value'] ? 'checked' : '' ?>>
                  <label class="form-check-label small" for="<?= e($key) ?>">
                    <?= e(statusLabel($key)) ?>
                  </label>
                </div>
              <?php else: ?>
                <label class="form-label" for="<?= e($key) ?>"><?= e(statusLabel($key)) ?></label>
                <input type="<?= $meta['type'] === 'int' ? 'number' : 'text' ?>"
                       id="<?= e($key) ?>" name="<?= e($key) ?>"
                       class="form-control form-control-sm"
                       <?= $meta['type'] === 'int' ? 'min="0"' : 'maxlength="500"' ?>
                       value="<?= e((string)$meta['value']) ?>">
                <div class="form-text mono fs-tiny"><?= e($key) ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card p-3 mt-4 d-flex flex-row justify-content-between align-items-center">
    <p class="text-muted small mb-0">
      <i class="bi bi-info-circle me-1"></i>
      Database credentials, encryption keys and the HTTPS switch live in
      <span class="mono">config/config.php</span> — never in the database.
    </p>
    <button class="btn btn-primary"><i class="bi bi-check2 me-2"></i>Save settings</button>
  </div>
</form>
