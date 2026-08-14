<?php /** @var array $report @var array $attachments @var array $timeline */ ?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
  <div>
    <a href="<?= e(url('my/reports')) ?>" class="small text-muted"><i class="bi bi-arrow-left me-1"></i>All my reports</a>
    <h1 class="h4 fw-semibold mt-2 mb-1"><?= e($report['title']) ?></h1>
    <p class="text-muted small mb-0">
      <span class="mono"><?= e($report['reference_no']) ?></span>
      <?php if ($report['case_number']): ?>
        · Case <span class="mono"><?= e($report['case_number']) ?></span>
      <?php endif; ?>
      · Filed <?= e(dt($report['created_at'])) ?>
    </p>
  </div>
  <div class="d-flex gap-2">
    <span class="badge badge-soft bg-<?= e(statusColor((string)$report['status'])) ?> fs-6 align-self-center">
      <?= e(statusLabel((string)$report['status'])) ?>
    </span>
    <a class="btn btn-outline-secondary btn-sm align-self-center"
       href="<?= e(url('report/receipt/' . $report['reference_no'])) ?>" target="_blank" rel="noopener">
      <i class="bi bi-printer me-1"></i>Receipt
    </a>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card p-4">
      <h2 class="h6 fw-semibold mb-3">What you reported</h2>
      <p class="small" style="white-space:pre-line"><?= e($report['description']) ?></p>

      <hr class="hr-soft">
      <dl class="row small mb-0">
        <dt class="col-5 col-sm-4 text-muted fw-normal">Category</dt>
        <dd class="col-7 col-sm-8"><?= e($report['category_name']) ?></dd>
        <dt class="col-5 col-sm-4 text-muted fw-normal">Urgency</dt>
        <dd class="col-7 col-sm-8">
          <span class="badge badge-soft bg-<?= e(urgencyColor((string)$report['urgency'])) ?>">
            <?= e(ucfirst((string)$report['urgency'])) ?>
          </span>
        </dd>
        <dt class="col-5 col-sm-4 text-muted fw-normal">Incident date</dt>
        <dd class="col-7 col-sm-8">
          <?= $report['incident_date'] ? e(d($report['incident_date'])) : 'Not specified' ?>
          <?= $report['incident_time'] ? ' at ' . e(substr((string)$report['incident_time'], 0, 5)) : '' ?>
        </dd>
        <dt class="col-5 col-sm-4 text-muted fw-normal">Location</dt>
        <dd class="col-7 col-sm-8">
          <?= e($report['location'] ?: 'Not specified') ?>
          <?php if ($report['province_name']): ?>
            <div class="text-muted fs-tiny">
              <?= e($report['district_name'] ? $report['district_name'] . ', ' : '') ?><?= e($report['province_name']) ?>
            </div>
          <?php endif; ?>
        </dd>
        <?php if ($report['organization_involved']): ?>
          <dt class="col-5 col-sm-4 text-muted fw-normal">Organisation</dt>
          <dd class="col-7 col-sm-8"><?= e($report['organization_involved']) ?></dd>
        <?php endif; ?>
        <?php if ($report['persons_involved']): ?>
          <dt class="col-5 col-sm-4 text-muted fw-normal">Persons involved</dt>
          <dd class="col-7 col-sm-8" style="white-space:pre-line"><?= e($report['persons_involved']) ?></dd>
        <?php endif; ?>
        <dt class="col-5 col-sm-4 text-muted fw-normal">Number of victims</dt>
        <dd class="col-7 col-sm-8"><?= e((string)$report['victim_count']) ?></dd>
      </dl>
    </div>

    <div class="card p-4 mt-4">
      <h2 class="h6 fw-semibold mb-3">Case updates</h2>
      <?php if ($timeline === []): ?>
        <div class="empty-state">
          <i class="bi bi-clock-history"></i>
          No public updates yet. You will be notified as soon as there is progress.
        </div>
      <?php else: ?>
        <div class="timeline">
          <?php foreach ($timeline as $entry): ?>
            <div class="timeline-item type-<?= e($entry['type']) ?>">
              <div class="d-flex flex-wrap justify-content-between gap-2">
                <span class="fw-semibold small"><?= e($entry['title'] ?: statusLabel((string)$entry['type'])) ?></span>
                <span class="text-muted fs-tiny"><?= e(dt($entry['created_at'])) ?></span>
              </div>
              <?php if ($entry['body']): ?>
                <p class="text-muted small mb-0" style="white-space:pre-line"><?= e($entry['body']) ?></p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card p-4">
      <h2 class="h6 fw-semibold mb-3">Evidence you attached</h2>
      <?php if ($attachments === []): ?>
        <p class="text-muted small mb-0">No files were attached to this report.</p>
      <?php else: ?>
        <ul class="list-unstyled mb-0 small">
          <?php foreach ($attachments as $file): ?>
            <li class="d-flex align-items-center gap-2 py-2 border-bottom">
              <i class="bi <?= e(match ($file['kind']) {
                    'image' => 'bi-file-earmark-image', 'video' => 'bi-file-earmark-play',
                    'audio' => 'bi-file-earmark-music', 'document' => 'bi-file-earmark-pdf',
                    default => 'bi-file-earmark' }) ?> text-primary"></i>
              <a class="flex-grow-1 text-reset" href="<?= e(url('attachments/' . $file['id'])) ?>" target="_blank" rel="noopener">
                <?= e(excerpt($file['original_name'], 28)) ?>
              </a>
              <span class="text-muted fs-tiny"><?= e(bytes((int)$file['size_bytes'])) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="card p-4 mt-4">
      <h2 class="h6 fw-semibold mb-2">Need to add something?</h2>
      <p class="text-muted small mb-3">
        If you have new evidence or information relevant to this report, contact us quoting
        your reference number and it will be added to the case file.
      </p>
      <a href="<?= e(url('contact')) ?>" class="btn btn-outline-primary btn-sm w-100">
        <i class="bi bi-chat-dots me-1"></i>Contact the case team
      </a>
    </div>
  </div>
</div>
