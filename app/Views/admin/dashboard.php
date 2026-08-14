<?php
/** @var array $reportStats @var array $caseStats @var array $userStats @var array $byCategory
 *  @var array $byProvince @var array $byStatus @var array $trend @var array $performance
 *  @var array $recent @var array $activity @var array $campaigns */
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h4 fw-semibold mb-1">Administrator dashboard</h1>
    <p class="text-muted small mb-0">System-wide health, volumes and activity.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= e(url('admin/audit')) ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-shield-lock me-1"></i>Audit log
    </a>
    <a href="<?= e(url('admin/backup')) ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-database-down me-1"></i>Backup
    </a>
    <a href="<?= e(url('analytics')) ?>" class="btn btn-primary btn-sm">
      <i class="bi bi-bar-chart-line me-1"></i>Reports
    </a>
  </div>
</div>

<div class="row g-3 mb-4">
  <?php
  $kpis = [
      ['Total reports',   $reportStats['total'],        'bi-file-earmark-text', 'tint-primary'],
      ['Active cases',    $caseStats['active'],         'bi-folder2-open',      'tint-primary'],
      ['Overdue',         $caseStats['overdue'],        'bi-alarm',             'tint-danger'],
      ['Critical urgency',$reportStats['critical'],     'bi-exclamation-octagon','tint-danger'],
      ['Registered users',$userStats['total'],          'bi-people',            'tint-secondary'],
      ['Officers',        $userStats['officers'],       'bi-person-badge',      'tint-dark'],
  ];
  foreach ($kpis as [$label, $value, $icon, $tint]): ?>
    <div class="col-6 col-lg-2">
      <div class="card stat-card h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="stat-value"><?= e(number_format((int)$value)) ?></div>
            <div class="stat-label"><?= e($label) ?></div>
          </div>
          <div class="stat-icon <?= e($tint) ?>"><i class="bi <?= e($icon) ?>"></i></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <div class="col-xl-8">
    <div class="card p-3 p-lg-4">
      <h2 class="h6 fw-semibold mb-3">Report volume trend</h2>
      <div class="chart-box">
        <canvas data-chart="line" data-chart-data="<?= ejson($trend) ?>"
                aria-label="Monthly report volume" role="img"></canvas>
      </div>
    </div>

    <div class="row g-4 mt-0">
      <div class="col-md-6">
        <div class="card p-3 p-lg-4 h-100">
          <h2 class="h6 fw-semibold mb-3">Reports by province</h2>
          <div class="chart-box chart-box-sm">
            <canvas data-chart="bar" data-chart-data="<?= ejson($byProvince) ?>"
                    aria-label="Reports by province" role="img"></canvas>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card p-3 p-lg-4 h-100">
          <h2 class="h6 fw-semibold mb-3">Reports by category</h2>
          <div class="chart-box chart-box-sm">
            <canvas data-chart="hbar" data-chart-data="<?= ejson($byCategory) ?>"
                    aria-label="Reports by category" role="img"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="card p-3 p-lg-4 mt-4">
      <h2 class="h6 fw-semibold mb-1">Density by province and category</h2>
      <p class="text-muted fs-tiny mb-3">Darker cells indicate a higher concentration of reports.</p>
      <div class="table-responsive" data-heatmap="<?= ejson(
          \App\Core\Database::instance()->all(
              'SELECT p.name AS province, rc.name AS category, COUNT(r.id) AS value
                 FROM reports r
                 JOIN report_categories rc ON rc.id = r.category_id
                 JOIN provinces p          ON p.id = r.province_id
                WHERE r.deleted_at IS NULL GROUP BY p.name, rc.name'
          )
      ) ?>"></div>
    </div>

    <div class="card table-card mt-4">
      <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-semibold mb-0">Officer workload</h2>
        <a href="<?= e(url('officers')) ?>" class="btn btn-link btn-sm p-0">Full report</a>
      </div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr>
              <th scope="col">Officer</th><th scope="col" class="text-end">Total</th>
              <th scope="col" class="text-end">Open</th><th scope="col" class="text-end">Concluded</th>
              <th scope="col" class="text-end">Overdue</th><th scope="col" class="text-end">Avg. days</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($performance === []): ?>
              <tr><td colspan="6" class="text-muted small">No officers created yet.</td></tr>
            <?php else: foreach ($performance as $row): ?>
              <tr>
                <td class="small"><?= e($row['officer']) ?></td>
                <td class="text-end small"><?= e((string)(int)$row['total']) ?></td>
                <td class="text-end small"><?= e((string)(int)$row['active']) ?></td>
                <td class="text-end small"><?= e((string)(int)$row['completed']) ?></td>
                <td class="text-end small<?= (int)$row['overdue'] > 0 ? ' text-danger fw-semibold' : '' ?>">
                  <?= e((string)(int)$row['overdue']) ?>
                </td>
                <td class="text-end small"><?= $row['avg_days'] !== null ? e((string)$row['avg_days']) : '—' ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card p-3 p-lg-4">
      <h2 class="h6 fw-semibold mb-3">Case status mix</h2>
      <div class="chart-box chart-box-sm">
        <canvas data-chart="doughnut" data-chart-data="<?= ejson($byStatus) ?>"
                aria-label="Reports by status" role="img"></canvas>
      </div>
    </div>

    <div class="card p-3 p-lg-4 mt-4">
      <h2 class="h6 fw-semibold mb-3">User accounts</h2>
      <dl class="row small mb-0">
        <dt class="col-7 text-muted fw-normal">Active</dt>
        <dd class="col-5 text-end fw-semibold"><?= e(number_format($userStats['active'])) ?></dd>
        <dt class="col-7 text-muted fw-normal">Pending verification</dt>
        <dd class="col-5 text-end fw-semibold"><?= e(number_format($userStats['pending'])) ?></dd>
        <dt class="col-7 text-muted fw-normal">Suspended</dt>
        <dd class="col-5 text-end fw-semibold"><?= e(number_format($userStats['suspended'])) ?></dd>
        <dt class="col-7 text-muted fw-normal">New in last 30 days</dt>
        <dd class="col-5 text-end fw-semibold"><?= e(number_format($userStats['new_30d'])) ?></dd>
      </dl>
      <a href="<?= e(url('admin/users')) ?>" class="btn btn-outline-primary btn-sm w-100 mt-3">Manage users</a>
    </div>

    <div class="card p-3 p-lg-4 mt-4">
      <h2 class="h6 fw-semibold mb-3">Awareness content</h2>
      <?php if ($campaigns === []): ?>
        <p class="text-muted small mb-0">No content created yet.</p>
      <?php else: foreach ($campaigns as $row): ?>
        <div class="d-flex justify-content-between py-2 border-bottom small">
          <span><?= e(statusLabel((string)$row['type'])) ?></span>
          <span class="text-muted">
            <?= e((string)(int)$row['published']) ?> live · <?= e(number_format((int)$row['views'])) ?> views
          </span>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <div class="card p-3 p-lg-4 mt-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 fw-semibold mb-0">Recent activity</h2>
        <a href="<?= e(url('admin/audit')) ?>" class="btn btn-link btn-sm p-0">Audit log</a>
      </div>
      <?php foreach ($activity as $entry): ?>
        <div class="py-2 border-bottom">
          <div class="small"><?= e(excerpt($entry['description'] ?: $entry['action'], 60)) ?></div>
          <div class="text-muted fs-tiny">
            <span class="mono"><?= e($entry['action']) ?></span> ·
            <?= e($entry['actor'] ?: 'System') ?> · <?= e(ago($entry['created_at'])) ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="card p-3 p-lg-4 mt-4">
      <h2 class="h6 fw-semibold mb-3">Latest reports</h2>
      <?php foreach ($recent as $row): ?>
        <a class="d-block py-2 border-bottom text-reset" href="<?= e(url('reports/' . $row['id'])) ?>">
          <div class="small fw-semibold"><?= e(excerpt($row['title'], 36)) ?></div>
          <div class="text-muted fs-tiny mono"><?= e($row['reference_no']) ?> · <?= e(ago($row['created_at'])) ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
