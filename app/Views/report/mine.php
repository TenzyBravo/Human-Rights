<?php /** @var array $reports */ ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h4 fw-semibold mb-1">My reports</h1>
    <p class="text-muted small mb-0">Every report you have filed under this account.</p>
  </div>
  <a href="<?= e(url('report')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>New report</a>
</div>

<div class="card table-card">
  <?php if ($reports === []): ?>
    <div class="empty-state">
      <i class="bi bi-inbox"></i>
      <p class="fw-semibold mb-1">You have not filed any reports yet</p>
      <p class="small mb-3">Reports filed anonymously do not appear here.</p>
      <a href="<?= e(url('report')) ?>" class="btn btn-primary btn-sm">File your first report</a>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead>
          <tr>
            <th scope="col">Reference</th>
            <th scope="col">Subject</th>
            <th scope="col">Category</th>
            <th scope="col">Urgency</th>
            <th scope="col">Status</th>
            <th scope="col">Submitted</th>
            <th scope="col"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reports as $report): ?>
            <tr>
              <td class="mono small"><?= e($report['reference_no']) ?></td>
              <td>
                <a class="fw-semibold small text-reset" href="<?= e(url('my/reports/' . $report['id'])) ?>">
                  <?= e(excerpt($report['title'], 60)) ?>
                </a>
                <?php if ($report['case_number']): ?>
                  <div class="text-muted fs-tiny mono"><?= e($report['case_number']) ?></div>
                <?php endif; ?>
              </td>
              <td class="small"><?= e($report['category_name']) ?></td>
              <td>
                <span class="badge badge-soft bg-<?= e(urgencyColor((string)$report['urgency'])) ?>">
                  <?= e(ucfirst((string)$report['urgency'])) ?>
                </span>
              </td>
              <td>
                <span class="badge badge-soft bg-<?= e(statusColor((string)$report['status'])) ?>">
                  <?= e(statusLabel((string)$report['status'])) ?>
                </span>
              </td>
              <td class="small text-muted"><?= e(d($report['created_at'])) ?></td>
              <td class="text-end">
                <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('my/reports/' . $report['id'])) ?>">
                  View
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
