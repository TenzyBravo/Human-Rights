<?php /** @var array $report @var array $attachments */ ?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
  <div>
    <a href="<?= e(url('reports')) ?>" class="small text-muted"><i class="bi bi-arrow-left me-1"></i>All reports</a>
    <h1 class="h4 fw-semibold mt-2 mb-1"><?= e($report['title']) ?></h1>
    <p class="text-muted small mb-0">
      <span class="mono"><?= e($report['reference_no']) ?></span> · Received <?= e(dt($report['created_at'])) ?>
    </p>
  </div>
  <span class="badge badge-soft bg-<?= e(statusColor((string)$report['status'])) ?> fs-6 align-self-center">
    <?= e(statusLabel((string)$report['status'])) ?>
  </span>
</div>

<div class="alert alert-warning small">
  <i class="bi bi-exclamation-triangle me-2"></i>
  No case file has been opened for this report yet. It will appear in the case list once a
  supervisor reviews it.
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card p-4">
      <h2 class="h6 fw-semibold mb-3">Complaint detail</h2>
      <p class="small" style="white-space:pre-line"><?= e($report['description']) ?></p>
      <hr class="hr-soft">
      <dl class="row small mb-0">
        <dt class="col-4 text-muted fw-normal">Category</dt>
        <dd class="col-8"><?= e($report['category_name']) ?></dd>
        <dt class="col-4 text-muted fw-normal">Urgency</dt>
        <dd class="col-8">
          <span class="badge badge-soft bg-<?= e(urgencyColor((string)$report['urgency'])) ?>">
            <?= e(ucfirst((string)$report['urgency'])) ?>
          </span>
        </dd>
        <dt class="col-4 text-muted fw-normal">Incident</dt>
        <dd class="col-8">
          <?= $report['incident_date'] ? e(d($report['incident_date'])) : 'Date not specified' ?>
          — <?= e($report['location'] ?: 'location not specified') ?>
        </dd>
        <dt class="col-4 text-muted fw-normal">Province</dt>
        <dd class="col-8"><?= e($report['province_name'] ?: '—') ?></dd>
        <dt class="col-4 text-muted fw-normal">Victims</dt>
        <dd class="col-8"><?= e((string)$report['victim_count']) ?></dd>
        <?php if ($report['organization_involved']): ?>
          <dt class="col-4 text-muted fw-normal">Organisation</dt>
          <dd class="col-8"><?= e($report['organization_involved']) ?></dd>
        <?php endif; ?>
      </dl>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card p-4">
      <h2 class="h6 fw-semibold mb-3">Complainant</h2>
      <?php if ((int)$report['is_anonymous'] === 1): ?>
        <p class="text-muted small mb-0"><i class="bi bi-eye-slash me-1"></i>Filed anonymously.</p>
      <?php else: ?>
        <dl class="row small mb-0">
          <dt class="col-4 text-muted fw-normal">Name</dt>
          <dd class="col-8"><?= e($report['reporter_name'] ?: '—') ?></dd>
          <dt class="col-4 text-muted fw-normal">Email</dt>
          <dd class="col-8 text-break"><?= e($report['reporter_email'] ?: '—') ?></dd>
          <dt class="col-4 text-muted fw-normal">Phone</dt>
          <dd class="col-8"><?= e($report['reporter_phone'] ?: '—') ?></dd>
        </dl>
      <?php endif; ?>
    </div>

    <div class="card p-4 mt-4">
      <h2 class="h6 fw-semibold mb-3">Attached evidence</h2>
      <?php if ($attachments === []): ?>
        <p class="text-muted small mb-0">No files attached.</p>
      <?php else: ?>
        <ul class="list-unstyled mb-0 small">
          <?php foreach ($attachments as $file): ?>
            <li class="d-flex align-items-center gap-2 py-2 border-bottom">
              <i class="bi bi-paperclip text-primary"></i>
              <a class="flex-grow-1 text-reset" href="<?= e(url('attachments/' . $file['id'])) ?>"
                 target="_blank" rel="noopener"><?= e(excerpt($file['original_name'], 24)) ?></a>
              <span class="text-muted fs-tiny"><?= e(bytes((int)$file['size_bytes'])) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>
