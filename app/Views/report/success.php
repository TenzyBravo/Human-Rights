<?php /** @var array $report */ ?>
<section class="section">
  <div class="container" style="max-width:44rem">
    <div class="card p-4 p-lg-5 text-center">
      <div class="stat-icon tint-secondary mx-auto mb-3" style="width:60px;height:60px;font-size:1.6rem">
        <i class="bi bi-check2-circle"></i>
      </div>
      <h1 class="h3 fw-semibold mb-2">Your report has been received</h1>
      <p class="text-muted mb-4">
        A case file has been opened and a supervisor will review it shortly.
        Please keep the reference number below.
      </p>

      <div class="card bg-body-tertiary border-0 p-3 mb-4">
        <div class="text-muted fs-tiny text-uppercase">Your reference number</div>
        <div class="mono fs-4 fw-semibold text-primary"><?= e($report['reference_no']) ?></div>
        <?php if (!empty($report['case_number'])): ?>
          <div class="text-muted fs-tiny mt-1">Case file <?= e($report['case_number']) ?></div>
        <?php endif; ?>
      </div>

      <dl class="row small text-start mb-4">
        <dt class="col-5 col-sm-4 text-muted fw-normal">Category</dt>
        <dd class="col-7 col-sm-8"><?= e($report['category_name']) ?></dd>
        <dt class="col-5 col-sm-4 text-muted fw-normal">Urgency</dt>
        <dd class="col-7 col-sm-8">
          <span class="badge badge-soft bg-<?= e(urgencyColor((string)$report['urgency'])) ?>">
            <?= e(ucfirst((string)$report['urgency'])) ?>
          </span>
        </dd>
        <dt class="col-5 col-sm-4 text-muted fw-normal">Status</dt>
        <dd class="col-7 col-sm-8">
          <span class="badge badge-soft bg-<?= e(statusColor((string)$report['status'])) ?>">
            <?= e(statusLabel((string)$report['status'])) ?>
          </span>
        </dd>
        <dt class="col-5 col-sm-4 text-muted fw-normal">Submitted</dt>
        <dd class="col-7 col-sm-8"><?= e(dt($report['created_at'])) ?></dd>
      </dl>

      <div class="d-flex flex-wrap gap-2 justify-content-center">
        <a href="<?= e(url('report/receipt/' . $report['reference_no'])) ?>" class="btn btn-primary" target="_blank" rel="noopener">
          <i class="bi bi-download me-2"></i>Download acknowledgement
        </a>
        <a href="<?= e(url('track?ref=' . urlencode((string)$report['reference_no']))) ?>" class="btn btn-outline-secondary">
          <i class="bi bi-search me-2"></i>Track this report
        </a>
      </div>

      <hr class="my-4 hr-soft">
      <p class="text-muted fs-tiny mb-0">
        <?php if ((int)$report['is_anonymous'] === 1): ?>
          This report was filed anonymously, so we cannot contact you or recover this
          reference number. Save it now.
        <?php else: ?>
          A confirmation has been sent to the email address you provided. You will be
          notified whenever the status changes.
        <?php endif; ?>
      </p>
    </div>
  </div>
</section>
