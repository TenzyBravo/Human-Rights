<?php
/** @var array $rows @var array $page @var array $filters @var bool $onlyMine
 *  @var array $categories @var array $provinces @var array $stats */
use App\Models\CaseFile;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h4 fw-semibold mb-1"><?= e($title) ?></h1>
    <p class="text-muted small mb-0">
      <?= $onlyMine
          ? 'Cases currently assigned to you, highest priority first.'
          : 'All case files across the organisation.' ?>
    </p>
  </div>
  <div class="d-flex gap-2">
    <?php if (can('report.export')): ?>
      <a href="<?= e(url('analytics')) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-bar-chart-line me-1"></i>Analytics
      </a>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3 mb-4">
  <?php
  $tiles = [
      ['Active',   $stats['active'],     'bi-folder2-open', 'tint-primary',   ['status' => 'open']],
      ['Overdue',  $stats['overdue'],    'bi-alarm',        'tint-danger',    ['overdue' => 1]],
      ['Escalated',$stats['escalated'],  'bi-arrow-up-circle','tint-warning', ['status' => 'escalated']],
      ['Concluded',$stats['resolved'] + $stats['closed'], 'bi-check2-circle', 'tint-secondary', ['status' => 'closed']],
  ];
  if (!$onlyMine) {
      $tiles[] = ['Unassigned', $stats['unassigned'], 'bi-inbox', 'tint-dark', ['unassigned' => 1]];
  }
  foreach ($tiles as [$label, $value, $icon, $tint, $query]): ?>
    <div class="col-6 col-lg">
      <a class="card stat-card h-100 text-reset card-hover" href="<?= e(url('cases' . queryWith($query + ['page' => null]))) ?>">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="stat-value"><?= e(number_format((int)$value)) ?></div>
            <div class="stat-label"><?= e($label) ?></div>
          </div>
          <div class="stat-icon <?= e($tint) ?>"><i class="bi <?= e($icon) ?>"></i></div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<!-- filters -->
<form class="card p-3 mb-3" method="get" action="<?= e(url('cases')) ?>">
  <div class="row g-2 align-items-end">
    <div class="col-lg-3">
      <label class="form-label" for="q">Search</label>
      <input type="search" id="q" name="q" class="form-control form-control-sm"
             placeholder="Case number, reference, title" value="<?= e($filters['q'] ?? '') ?>">
    </div>
    <div class="col-6 col-lg-2">
      <label class="form-label" for="status">Status</label>
      <select id="status" name="status" class="form-select form-select-sm" data-autosubmit>
        <option value="open"<?= ($filters['status'] ?? '') === 'open' ? ' selected' : '' ?>>All open</option>
        <option value="">Any status</option>
        <?php foreach (CaseFile::STATUSES as $status): ?>
          <option value="<?= e($status) ?>"<?= ($filters['status'] ?? '') === $status ? ' selected' : '' ?>>
            <?= e(statusLabel($status)) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-lg-2">
      <label class="form-label" for="priority">Priority</label>
      <select id="priority" name="priority" class="form-select form-select-sm" data-autosubmit>
        <option value="">Any</option>
        <?php foreach (['critical', 'high', 'medium', 'low'] as $priority): ?>
          <option value="<?= e($priority) ?>"<?= ($filters['priority'] ?? '') === $priority ? ' selected' : '' ?>>
            <?= e(ucfirst($priority)) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-lg-2">
      <label class="form-label" for="category">Category</label>
      <select id="category" name="category" class="form-select form-select-sm" data-autosubmit>
        <option value="">Any</option>
        <?php foreach ($categories as $category): ?>
          <option value="<?= e((string)$category['id']) ?>"
            <?= (int)($filters['category'] ?? 0) === (int)$category['id'] ? ' selected' : '' ?>>
            <?= e(excerpt($category['name'], 26)) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-lg-2">
      <label class="form-label" for="province">Province</label>
      <select id="province" name="province" class="form-select form-select-sm" data-autosubmit>
        <option value="">Any</option>
        <?php foreach ($provinces as $province): ?>
          <option value="<?= e((string)$province['id']) ?>"
            <?= (int)($filters['province'] ?? 0) === (int)$province['id'] ? ' selected' : '' ?>>
            <?= e($province['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-lg-1 d-flex gap-2">
      <button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i></button>
      <a href="<?= e(url('cases')) ?>" class="btn btn-outline-secondary btn-sm" title="Reset filters">
        <i class="bi bi-x-lg"></i>
      </a>
    </div>
    <?php if (!empty($filters['unassigned'])): ?>
      <input type="hidden" name="unassigned" value="1">
    <?php endif; ?>
    <?php if (!empty($filters['overdue'])): ?>
      <input type="hidden" name="overdue" value="1">
    <?php endif; ?>
  </div>
</form>

<div class="card table-card">
  <?php if ($rows === []): ?>
    <div class="empty-state">
      <i class="bi bi-folder"></i>
      <p class="fw-semibold mb-1">No cases match these filters</p>
      <p class="small mb-0">Try widening the search or <a href="<?= e(url('cases')) ?>">reset the filters</a>.</p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead>
          <tr>
            <th scope="col">Case</th>
            <th scope="col">Subject</th>
            <th scope="col">Category</th>
            <th scope="col">Priority</th>
            <th scope="col">Status</th>
            <?php if (!$onlyMine): ?><th scope="col">Officer</th><?php endif; ?>
            <th scope="col">Age</th>
            <th scope="col"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <?php $overdue = $row['due_at'] !== null && $row['resolved_at'] === null
                             && strtotime((string)$row['due_at']) < time(); ?>
            <tr>
              <td>
                <a class="mono small fw-semibold text-reset" href="<?= e(url('cases/' . $row['id'])) ?>">
                  <?= e($row['case_number']) ?>
                </a>
                <div class="text-muted fs-tiny mono"><?= e($row['reference_no']) ?></div>
              </td>
              <td>
                <a class="small text-reset" href="<?= e(url('cases/' . $row['id'])) ?>">
                  <?= e(excerpt($row['title'], 52)) ?>
                </a>
                <?php if ($row['province_name']): ?>
                  <div class="text-muted fs-tiny"><i class="bi bi-geo-alt me-1"></i><?= e($row['province_name']) ?></div>
                <?php endif; ?>
              </td>
              <td class="small"><?= e(excerpt($row['category_name'], 24)) ?></td>
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
              <?php if (!$onlyMine): ?>
                <td class="small">
                  <?php if ($row['officer_name']): ?>
                    <span class="d-inline-flex align-items-center gap-2">
                      <span class="avatar avatar-sm"><?= e(initials((string)$row['officer_name'])) ?></span>
                      <?= e($row['officer_name']) ?>
                    </span>
                  <?php else: ?>
                    <span class="badge badge-soft bg-warning">Unassigned</span>
                  <?php endif; ?>
                </td>
              <?php endif; ?>
              <td class="small<?= $overdue ? ' text-danger fw-semibold' : ' text-muted' ?>">
                <?= e((string)$row['age_days']) ?> d
                <?php if ($overdue): ?><i class="bi bi-alarm ms-1" title="Past due date"></i><?php endif; ?>
              </td>
              <td class="text-end">
                <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('cases/' . $row['id'])) ?>">Open</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php \App\Core\View::partial('pagination', ['page' => $page]); ?>
