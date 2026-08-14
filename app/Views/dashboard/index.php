<?php
/** @var bool $isOfficerOnly @var array $caseStats @var array $reportStats @var array $myCases
 *  @var array $unassigned @var array $overdue @var array $interviews @var array $recentReports
 *  @var array $notifications @var array $performance @var array $activity */
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h4 fw-semibold mb-1">
      Good <?= e((int)date('H') < 12 ? 'morning' : ((int)date('H') < 17 ? 'afternoon' : 'evening')) ?>,
      <?= e(explode(' ', \App\Core\Auth::name())[0]) ?>
    </h1>
    <p class="text-muted small mb-0"><?= e(date('l, d F Y')) ?> · <?= e($title) ?></p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= e(url('cases')) ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-folder2-open me-1"></i>My cases
    </a>
    <?php if (can('report.export')): ?>
      <a href="<?= e(url('analytics')) ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-bar-chart-line me-1"></i>Analytics
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- KPI row -->
<div class="row g-3 mb-4">
  <?php
  $kpis = [
      ['Active cases',    $caseStats['active'],   'bi-folder2-open',    'tint-primary',   'cases?status=open'],
      ['Overdue',         $caseStats['overdue'],  'bi-alarm',           'tint-danger',    'cases?overdue=1'],
      ['Escalated',       $caseStats['escalated'],'bi-arrow-up-circle', 'tint-warning',   'cases?status=escalated'],
      ['Concluded',       $caseStats['resolved'] + $caseStats['closed'], 'bi-check2-circle', 'tint-secondary', 'cases?status=closed'],
      ['Avg. resolution', $caseStats['avg_resolution_days'] . ' d', 'bi-hourglass-split', 'tint-dark', null],
  ];
  if (!$isOfficerOnly) {
      array_splice($kpis, 4, 0, [['Unassigned', $caseStats['unassigned'], 'bi-inbox', 'tint-dark', 'cases?unassigned=1&status=open']]);
  }
  foreach ($kpis as [$label, $value, $icon, $tint, $link]): ?>
    <div class="col-6 col-lg">
      <?php $tag = $link ? 'a' : 'div'; ?>
      <<?= $tag ?> class="card stat-card h-100 text-reset<?= $link ? ' card-hover' : '' ?>"
        <?= $link ? 'href="' . e(url($link)) . '"' : '' ?>>
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="stat-value"><?= e(is_numeric($value) ? number_format((float)$value) : (string)$value) ?></div>
            <div class="stat-label"><?= e($label) ?></div>
          </div>
          <div class="stat-icon <?= e($tint) ?>"><i class="bi <?= e($icon) ?>"></i></div>
        </div>
      </<?= $tag ?>>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <!-- charts -->
  <div class="col-xl-8">
    <div class="card p-3 p-lg-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 fw-semibold mb-0">Report volume, last 12 months</h2>
        <span class="text-muted fs-tiny"><?= e(number_format($reportStats['last_30_days'])) ?> in the last 30 days</span>
      </div>
      <div class="chart-box">
        <canvas data-chart="line" data-chart-key="trend"
                data-chart-src="<?= e(url('api/dashboard/charts')) ?>"
                aria-label="Monthly report volume" role="img"></canvas>
      </div>
    </div>

    <div class="row g-4 mt-0">
      <div class="col-md-6">
        <div class="card p-3 p-lg-4 h-100">
          <h2 class="h6 fw-semibold mb-3">By category</h2>
          <div class="chart-box chart-box-sm">
            <canvas data-chart="hbar" data-chart-key="byCategory"
                    data-chart-src="<?= e(url('api/dashboard/charts')) ?>"
                    aria-label="Reports by category" role="img"></canvas>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card p-3 p-lg-4 h-100">
          <h2 class="h6 fw-semibold mb-3">By status</h2>
          <div class="chart-box chart-box-sm">
            <canvas data-chart="doughnut" data-chart-key="byStatus"
                    data-chart-src="<?= e(url('api/dashboard/charts')) ?>"
                    aria-label="Reports by status" role="img"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- my cases -->
    <div class="card table-card mt-4">
      <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-semibold mb-0"><?= $isOfficerOnly ? 'My open cases' : 'Open cases' ?></h2>
        <a href="<?= e(url('cases')) ?>" class="btn btn-link btn-sm p-0">View all</a>
      </div>
      <?php if ($myCases === []): ?>
        <div class="empty-state"><i class="bi bi-check2-all"></i>Nothing open — good work.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th scope="col">Case</th><th scope="col">Subject</th>
                <th scope="col">Priority</th><th scope="col">Status</th><th scope="col">Age</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($myCases as $row): ?>
                <tr class="cursor-pointer" onclick="window.location='<?= e(url('cases/' . $row['id'])) ?>'">
                  <td class="mono small fw-semibold"><?= e($row['case_number']) ?></td>
                  <td class="small"><?= e(excerpt($row['title'], 44)) ?></td>
                  <td>
                    <span class="badge badge-soft bg-<?= e(urgencyColor((string)$row['priority'])) ?>">
                      <?= e(ucfirst((string)$row['priority'])) ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge badge-soft bg-<?= e(statusColor((string)$row['status'])) ?>">
                      <?= e(statusLabel((string)$row['status'])) ?>
                    </span>
                  </td>
                  <td class="small text-muted"><?= e((string)$row['age_days']) ?> d</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($performance !== []): ?>
      <div class="card table-card mt-4">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-semibold mb-0">Team workload</h2>
          <a href="<?= e(url('officers')) ?>" class="btn btn-link btn-sm p-0">Full report</a>
        </div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr>
                <th scope="col">Officer</th><th scope="col" class="text-end">Open</th>
                <th scope="col" class="text-end">Concluded</th><th scope="col" class="text-end">Overdue</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (array_slice($performance, 0, 6) as $row): ?>
                <tr>
                  <td class="small"><?= e($row['officer']) ?></td>
                  <td class="text-end small"><?= e((string)(int)$row['active']) ?></td>
                  <td class="text-end small"><?= e((string)(int)$row['completed']) ?></td>
                  <td class="text-end small<?= (int)$row['overdue'] > 0 ? ' text-danger fw-semibold' : '' ?>">
                    <?= e((string)(int)$row['overdue']) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- side column -->
  <div class="col-xl-4">
    <?php if ($overdue !== []): ?>
      <div class="card p-3 p-lg-4 border-danger border-opacity-25">
        <h2 class="h6 fw-semibold mb-3 text-danger">
          <i class="bi bi-alarm me-1"></i>Needs attention now
        </h2>
        <?php foreach ($overdue as $row): ?>
          <a class="d-flex gap-2 align-items-start text-reset py-2 border-bottom" href="<?= e(url('cases/' . $row['id'])) ?>">
            <i class="bi bi-exclamation-circle text-danger mt-1"></i>
            <span class="small flex-grow-1">
              <span class="d-block fw-semibold"><?= e(excerpt($row['title'], 40)) ?></span>
              <span class="text-muted fs-tiny">
                <?= e($row['case_number']) ?> · <?= e((string)$row['age_days']) ?> days open
              </span>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($unassigned !== []): ?>
      <div class="card p-3 p-lg-4 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h6 fw-semibold mb-0">Awaiting assignment</h2>
          <a href="<?= e(url('cases?unassigned=1&status=open')) ?>" class="btn btn-link btn-sm p-0">All</a>
        </div>
        <?php foreach ($unassigned as $row): ?>
          <a class="d-flex gap-2 align-items-start text-reset py-2 border-bottom" href="<?= e(url('cases/' . $row['id'])) ?>">
            <span class="badge badge-soft bg-<?= e(urgencyColor((string)$row['priority'])) ?> mt-1">
              <?= e(strtoupper(substr((string)$row['priority'], 0, 1))) ?>
            </span>
            <span class="small flex-grow-1">
              <span class="d-block fw-semibold"><?= e(excerpt($row['title'], 38)) ?></span>
              <span class="text-muted fs-tiny"><?= e($row['category_name']) ?></span>
            </span>
            <i class="bi bi-chevron-right text-muted"></i>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- calendar / interviews -->
    <div class="card p-3 p-lg-4 mt-4">
      <h2 class="h6 fw-semibold mb-3"><i class="bi bi-calendar-week me-1"></i>Upcoming interviews</h2>
      <?php if ($interviews === []): ?>
        <p class="text-muted small mb-0">No interviews scheduled.</p>
      <?php else: foreach ($interviews as $iv): ?>
        <a class="d-flex gap-3 align-items-start text-reset py-2 border-bottom" href="<?= e(url('cases/' . $iv['case_id'])) ?>">
          <span class="stat-icon tint-primary flex-shrink-0" style="width:38px;height:38px">
            <span class="fw-semibold" style="font-size:.66rem">
              <?= e(date('d M', strtotime((string)$iv['scheduled_at']))) ?>
            </span>
          </span>
          <span class="small flex-grow-1">
            <span class="d-block fw-semibold"><?= e(excerpt($iv['title'], 34)) ?></span>
            <span class="text-muted fs-tiny">
              <?= e(date('H:i', strtotime((string)$iv['scheduled_at']))) ?> · <?= e($iv['case_number']) ?>
            </span>
          </span>
        </a>
      <?php endforeach; endif; ?>
    </div>

    <!-- notifications -->
    <div class="card p-3 p-lg-4 mt-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 fw-semibold mb-0"><i class="bi bi-bell me-1"></i>Notifications</h2>
        <a href="<?= e(url('notifications')) ?>" class="btn btn-link btn-sm p-0">All</a>
      </div>
      <?php if ($notifications === []): ?>
        <p class="text-muted small mb-0">You are all caught up.</p>
      <?php else: foreach ($notifications as $n): ?>
        <div class="py-2 border-bottom">
          <div class="fw-semibold small"><?= e($n['title']) ?></div>
          <div class="text-muted fs-tiny"><?= e(ago($n['created_at'])) ?></div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <!-- recent reports -->
    <div class="card p-3 p-lg-4 mt-4">
      <h2 class="h6 fw-semibold mb-3">Latest reports received</h2>
      <?php if ($recentReports === []): ?>
        <p class="text-muted small mb-0">No reports yet.</p>
      <?php else: foreach ($recentReports as $row): ?>
        <div class="py-2 border-bottom">
          <div class="d-flex justify-content-between gap-2">
            <span class="small fw-semibold"><?= e(excerpt($row['title'], 34)) ?></span>
            <span class="badge badge-soft bg-<?= e(urgencyColor((string)$row['urgency'])) ?>">
              <?= e(ucfirst((string)$row['urgency'])) ?>
            </span>
          </div>
          <div class="text-muted fs-tiny mono"><?= e($row['reference_no']) ?> · <?= e(ago($row['created_at'])) ?></div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <?php if ($activity !== []): ?>
      <div class="card p-3 p-lg-4 mt-4">
        <h2 class="h6 fw-semibold mb-3">Recent system activity</h2>
        <?php foreach ($activity as $entry): ?>
          <div class="py-2 border-bottom">
            <div class="small"><?= e($entry['description'] ?: $entry['action']) ?></div>
            <div class="text-muted fs-tiny">
              <?= e($entry['actor'] ?: 'System') ?> · <?= e(ago($entry['created_at'])) ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
