<?php /** @var string $ref @var array|null $report */ ?>
<section class="section">
  <div class="container" style="max-width:44rem">
    <div class="text-center mb-4">
      <span class="eyebrow"><i class="bi bi-search"></i>Case tracking</span>
      <h1 class="section-title fw-semibold mt-3 mb-2">Track your report</h1>
      <p class="text-muted">Enter the reference number issued when you submitted your report.</p>
    </div>

    <div class="card p-4 mb-4">
      <form method="post" action="<?= e(url('track')) ?>" class="row g-2">
        <?= csrf() ?>
        <div class="col-sm">
          <label class="visually-hidden" for="ref">Reference number</label>
          <input type="text" id="ref" name="ref" class="form-control mono" required maxlength="30"
                 placeholder="HR-<?= e(date('Y')) ?>-000123" value="<?= e($ref) ?>">
        </div>
        <div class="col-sm-auto">
          <button class="btn btn-primary w-100"><i class="bi bi-search me-2"></i>Track</button>
        </div>
      </form>
    </div>

    <?php if ($report !== null): ?>
      <?php
      $stages = ['submitted', 'under_review', 'assigned', 'investigating', 'resolved', 'closed'];
      $currentIndex = array_search($report['status'], $stages, true);
      if ($currentIndex === false) {
          $currentIndex = in_array($report['status'], ['escalated', 'awaiting_information'], true) ? 3 : 0;
      }
      ?>
      <div class="card p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
          <div>
            <div class="text-muted fs-tiny text-uppercase">Reference</div>
            <div class="mono fw-semibold fs-5"><?= e($report['reference_no']) ?></div>
          </div>
          <span class="badge badge-soft bg-<?= e(statusColor((string)$report['status'])) ?> fs-6">
            <?= e(statusLabel((string)$report['status'])) ?>
          </span>
        </div>

        <ol class="wizard-steps mb-4">
          <?php foreach ($stages as $i => $stage): ?>
            <li class="<?= $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'current' : '') ?>">
              <?= e(statusLabel($stage)) ?>
            </li>
          <?php endforeach; ?>
        </ol>

        <?php if (in_array($report['status'], ['escalated', 'awaiting_information', 'rejected'], true)): ?>
          <div class="alert alert-<?= $report['status'] === 'rejected' ? 'danger' : 'warning' ?> small">
            <i class="bi bi-info-circle me-2"></i>
            <?= e(match ($report['status']) {
                'awaiting_information' => 'The case team has requested additional information from the complainant.',
                'escalated'            => 'This case has been escalated for senior review.',
                default                => 'This complaint was assessed as falling outside our mandate. Check your email for the reasons and any referral.',
            }) ?>
          </div>
        <?php endif; ?>

        <dl class="row small mb-0">
          <dt class="col-5 col-sm-4 text-muted fw-normal">Subject</dt>
          <dd class="col-7 col-sm-8"><?= e($report['title']) ?></dd>
          <dt class="col-5 col-sm-4 text-muted fw-normal">Category</dt>
          <dd class="col-7 col-sm-8"><?= e($report['category_name']) ?></dd>
          <dt class="col-5 col-sm-4 text-muted fw-normal">Submitted</dt>
          <dd class="col-7 col-sm-8"><?= e(dt($report['created_at'])) ?></dd>
          <?php if (!empty($report['case_number'])): ?>
            <dt class="col-5 col-sm-4 text-muted fw-normal">Case file</dt>
            <dd class="col-7 col-sm-8 mono"><?= e($report['case_number']) ?></dd>
          <?php endif; ?>
          <?php if (!empty($report['opened_at'])): ?>
            <dt class="col-5 col-sm-4 text-muted fw-normal">Case opened</dt>
            <dd class="col-7 col-sm-8"><?= e(d($report['opened_at'])) ?></dd>
          <?php endif; ?>
          <?php if (!empty($report['resolved_at'])): ?>
            <dt class="col-5 col-sm-4 text-muted fw-normal">Concluded</dt>
            <dd class="col-7 col-sm-8"><?= e(d($report['resolved_at'])) ?></dd>
          <?php endif; ?>
        </dl>

        <hr class="my-3 hr-soft">
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= e(url('report/receipt/' . $report['reference_no'])) ?>"
             class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener">
            <i class="bi bi-file-earmark-text me-1"></i>Acknowledgement receipt
          </a>
          <a href="<?= e(url('contact')) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-chat-dots me-1"></i>Ask about this case
          </a>
        </div>
        <p class="text-muted fs-tiny mt-3 mb-0">
          For your protection, tracking shows the status only. Case notes, evidence and
          officer details are never exposed publicly.
        </p>
      </div>
    <?php elseif ($ref !== ''): ?>
      <div class="card p-4 text-center">
        <div class="empty-state">
          <i class="bi bi-question-circle"></i>
          <p class="mb-1 fw-semibold">No report found for “<?= e($ref) ?>”</p>
          <p class="small mb-0">
            Check the format — it looks like <span class="mono">HR-<?= e(date('Y')) ?>-000123</span>.
            If you have lost your reference number and you filed with your name,
            <a href="<?= e(url('login')) ?>">sign in</a> to see your report history.
          </p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
