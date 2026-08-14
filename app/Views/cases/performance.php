<?php /** @var array $rows @var array $stats */ ?>
<div class="mb-4">
  <h1 class="h4 fw-semibold mb-1">Officer performance</h1>
  <p class="text-muted small mb-0">Workload distribution and resolution times across the investigation team.</p>
</div>

<div class="row g-3 mb-4">
  <?php
  $tiles = [
      ['Cases in progress',   $stats['active'],              'bi-folder2-open',   'tint-primary'],
      ['Concluded',           $stats['resolved'] + $stats['closed'], 'bi-check2-circle', 'tint-secondary'],
      ['Past deadline',       $stats['overdue'],             'bi-alarm',          'tint-danger'],
      ['Avg. resolution',     $stats['avg_resolution_days'] . ' days', 'bi-hourglass-split', 'tint-warning'],
  ];
  foreach ($tiles as [$label, $value, $icon, $tint]): ?>
    <div class="col-6 col-lg-3">
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

<div class="card table-card">
  <div class="p-3 border-bottom">
    <h2 class="h6 fw-semibold mb-0">By officer</h2>
    <p class="text-muted fs-tiny mb-0">
      Use this to balance workload — the officer with the fewest open cases is offered first when assigning.
    </p>
  </div>
  <?php if ($rows === []): ?>
    <div class="empty-state"><i class="bi bi-person-badge"></i>No officers have been created yet.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead>
          <tr>
            <th scope="col">Officer</th>
            <th scope="col" class="text-end">Total</th>
            <th scope="col" class="text-end">Open</th>
            <th scope="col" class="text-end">Concluded</th>
            <th scope="col" class="text-end">Overdue</th>
            <th scope="col" class="text-end">Avg. days</th>
            <th scope="col" style="min-width:140px">Completion</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <?php
            $total = (int)$row['total'];
            $done  = (int)$row['completed'];
            $pct   = $total > 0 ? (int)round(($done / $total) * 100) : 0;
            ?>
            <tr>
              <td>
                <span class="d-inline-flex align-items-center gap-2">
                  <span class="avatar avatar-sm"><?= e(initials((string)$row['officer'])) ?></span>
                  <span class="small fw-semibold"><?= e($row['officer']) ?></span>
                </span>
              </td>
              <td class="text-end small"><?= e((string)$total) ?></td>
              <td class="text-end small"><?= e((string)(int)$row['active']) ?></td>
              <td class="text-end small"><?= e((string)$done) ?></td>
              <td class="text-end small<?= (int)$row['overdue'] > 0 ? ' text-danger fw-semibold' : '' ?>">
                <?= e((string)(int)$row['overdue']) ?>
              </td>
              <td class="text-end small"><?= $row['avg_days'] !== null ? e((string)$row['avg_days']) : '—' ?></td>
              <td>
                <div class="progress" style="height:6px" role="progressbar"
                     aria-valuenow="<?= e((string)$pct) ?>" aria-valuemin="0" aria-valuemax="100"
                     aria-label="Completion rate">
                  <div class="progress-bar bg-<?= $pct >= 70 ? 'success' : ($pct >= 40 ? 'warning' : 'danger') ?>"
                       style="width:<?= e((string)$pct) ?>%"></div>
                </div>
                <span class="text-muted fs-tiny"><?= e((string)$pct) ?>%</span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
