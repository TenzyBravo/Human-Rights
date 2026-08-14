<?php
/** @var string $period @var string $from @var string $to @var array $summary
 *  @var array $byCategory @var array $byProvince @var array $byStatus @var array $trend
 *  @var array $officers @var array $categories @var array $provinces */
$qs = 'period=' . urlencode($period) . '&from=' . urlencode($from) . '&to=' . urlencode($to);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h4 fw-semibold mb-1">Reports &amp; analytics</h1>
    <p class="text-muted small mb-0">
      Period: <strong><?= e(d($from)) ?></strong> to <strong><?= e(d($to)) ?></strong>
    </p>
  </div>
  <div class="dropdown">
    <button class="btn btn-primary" data-bs-toggle="dropdown">
      <i class="bi bi-download me-2"></i>Export
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow">
      <li><a class="dropdown-item" href="<?= e(url('analytics/export?' . $qs . '&format=csv')) ?>">
        <i class="bi bi-filetype-csv me-2"></i>CSV</a></li>
      <li><a class="dropdown-item" href="<?= e(url('analytics/export?' . $qs . '&format=xls')) ?>">
        <i class="bi bi-file-earmark-excel me-2"></i>Excel</a></li>
      <li><a class="dropdown-item" href="<?= e(url('analytics/export?' . $qs . '&format=pdf')) ?>" target="_blank" rel="noopener">
        <i class="bi bi-file-earmark-pdf me-2"></i>PDF (print view)</a></li>
    </ul>
  </div>
</div>

<form class="card p-3 mb-4" method="get" action="<?= e(url('analytics')) ?>">
  <div class="row g-2 align-items-end">
    <div class="col-6 col-lg-3">
      <label class="form-label" for="period">Reporting period</label>
      <select id="period" name="period" class="form-select form-select-sm">
        <?php foreach (['daily' => 'Daily (today)', 'weekly' => 'Weekly (this week)',
                        'monthly' => 'Monthly (this month)', 'annual' => 'Annual (this year)',
                        'custom' => 'Custom range'] as $value => $label): ?>
          <option value="<?= e($value) ?>"<?= $period === $value ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-lg-2">
      <label class="form-label" for="from">From</label>
      <input type="date" id="from" name="from" class="form-control form-control-sm" value="<?= e($from) ?>">
    </div>
    <div class="col-6 col-lg-2">
      <label class="form-label" for="to">To</label>
      <input type="date" id="to" name="to" class="form-control form-control-sm" value="<?= e($to) ?>">
    </div>
    <div class="col-6 col-lg-2">
      <label class="form-label" for="category">Category</label>
      <select id="category" name="category" class="form-select form-select-sm">
        <option value="">All</option>
        <?php foreach ($categories as $category): ?>
          <option value="<?= e((string)$category['id']) ?>"
            <?= (int)($_GET['category'] ?? 0) === (int)$category['id'] ? ' selected' : '' ?>>
            <?= e(excerpt($category['name'], 22)) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-lg-2">
      <label class="form-label" for="province">Province</label>
      <select id="province" name="province" class="form-select form-select-sm">
        <option value="">All</option>
        <?php foreach ($provinces as $province): ?>
          <option value="<?= e((string)$province['id']) ?>"
            <?= (int)($_GET['province'] ?? 0) === (int)$province['id'] ? ' selected' : '' ?>>
            <?= e($province['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-lg-1">
      <button class="btn btn-primary btn-sm w-100"><i class="bi bi-arrow-repeat"></i></button>
    </div>
  </div>
</form>

<div class="row g-3 mb-4">
  <?php
  $tiles = [
      ['Reports in period', $summary['total'],          'bi-file-earmark-text',  'tint-primary'],
      ['Concluded',         $summary['completed'],      'bi-check2-circle',      'tint-secondary'],
      ['High / critical',   $summary['high_priority'],  'bi-exclamation-octagon','tint-danger'],
      ['Anonymous',         $summary['anonymous'],      'bi-eye-slash',          'tint-dark'],
      ['Rejected',          $summary['rejected'],       'bi-x-circle',           'tint-warning'],
      ['Avg. resolution',   $summary['avg_resolution'] . ' d', 'bi-hourglass-split', 'tint-primary'],
  ];
  foreach ($tiles as [$label, $value, $icon, $tint]): ?>
    <div class="col-6 col-lg-2">
      <div class="card stat-card h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="stat-value"><?= e(is_numeric($value) ? number_format((float)$value) : (string)$value) ?></div>
            <div class="stat-label"><?= e($label) ?></div>
          </div>
          <div class="stat-icon <?= e($tint) ?>"><i class="bi <?= e($icon) ?>"></i></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card p-3 p-lg-4">
      <h2 class="h6 fw-semibold mb-3">Twelve-month trend</h2>
      <div class="chart-box">
        <canvas data-chart="line" data-chart-data="<?= ejson($trend) ?>"
                aria-label="Monthly report volume" role="img"></canvas>
      </div>
    </div>
    <div class="card p-3 p-lg-4 mt-4">
      <h2 class="h6 fw-semibold mb-3">Category breakdown for the period</h2>
      <div class="chart-box">
        <canvas data-chart="hbar" data-chart-data="<?= ejson($byCategory) ?>"
                aria-label="Reports by category" role="img"></canvas>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card p-3 p-lg-4">
      <h2 class="h6 fw-semibold mb-3">Status mix</h2>
      <div class="chart-box chart-box-sm">
        <canvas data-chart="doughnut" data-chart-data="<?= ejson($byStatus) ?>"
                aria-label="Reports by status" role="img"></canvas>
      </div>
    </div>
    <div class="card table-card mt-4">
      <div class="p-3 border-bottom">
        <h2 class="h6 fw-semibold mb-0">Geographic distribution</h2>
      </div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th scope="col">Province</th><th scope="col" class="text-end">Reports</th></tr></thead>
          <tbody>
            <?php if ($byProvince === []): ?>
              <tr><td colspan="2" class="text-muted small">No data for this period.</td></tr>
            <?php else: foreach ($byProvince as $row): ?>
              <tr>
                <td class="small"><?= e($row['label'] ?: 'Unspecified') ?></td>
                <td class="text-end small"><?= e(number_format((int)$row['value'])) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card table-card">
      <div class="p-3 border-bottom">
        <h2 class="h6 fw-semibold mb-0">Officer report</h2>
      </div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr>
              <th scope="col">Officer</th><th scope="col" class="text-end">Total cases</th>
              <th scope="col" class="text-end">Open</th><th scope="col" class="text-end">Concluded</th>
              <th scope="col" class="text-end">Overdue</th><th scope="col" class="text-end">Avg. days to resolve</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($officers === []): ?>
              <tr><td colspan="6" class="text-muted small">No officers created yet.</td></tr>
            <?php else: foreach ($officers as $row): ?>
              <tr>
                <td class="small fw-semibold"><?= e($row['officer']) ?></td>
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
</div>
