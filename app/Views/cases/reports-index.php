<?php
/** @var array $rows @var array $page @var array $filters @var array $categories @var array $provinces */
use App\Models\Report;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h4 fw-semibold mb-1">All reports</h1>
    <p class="text-muted small mb-0">Every complaint received, including those not yet allocated to a case file.</p>
  </div>
  <?php if (can('report.export')): ?>
    <a href="<?= e(url('analytics/export?format=csv' . ($filters['from'] ?? '' ? '&from=' . urlencode((string)$filters['from']) : ''))) ?>"
       class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-download me-1"></i>Export CSV
    </a>
  <?php endif; ?>
</div>

<form class="card p-3 mb-3" method="get" action="<?= e(url('reports')) ?>">
  <div class="row g-2 align-items-end">
    <div class="col-lg-3">
      <label class="form-label" for="q">Search</label>
      <input type="search" id="q" name="q" class="form-control form-control-sm"
             placeholder="Reference, title or location" value="<?= e($filters['q'] ?? '') ?>">
    </div>
    <div class="col-6 col-lg-2">
      <label class="form-label" for="status">Status</label>
      <select id="status" name="status" class="form-select form-select-sm" data-autosubmit>
        <option value="">Any</option>
        <?php foreach (Report::STATUSES as $status): ?>
          <option value="<?= e($status) ?>"<?= ($filters['status'] ?? '') === $status ? ' selected' : '' ?>>
            <?= e(statusLabel($status)) ?>
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
            <?= e(excerpt($category['name'], 24)) ?>
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
    <div class="col-6 col-lg-1">
      <label class="form-label" for="from">From</label>
      <input type="date" id="from" name="from" class="form-control form-control-sm" value="<?= e($filters['from'] ?? '') ?>">
    </div>
    <div class="col-6 col-lg-1">
      <label class="form-label" for="to">To</label>
      <input type="date" id="to" name="to" class="form-control form-control-sm" value="<?= e($filters['to'] ?? '') ?>">
    </div>
    <div class="col-lg-1 d-flex gap-1">
      <button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i></button>
      <a href="<?= e(url('reports')) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
    </div>
  </div>
</form>

<div class="card table-card">
  <?php if ($rows === []): ?>
    <div class="empty-state"><i class="bi bi-file-earmark"></i>No reports match these filters.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead>
          <tr>
            <th scope="col">Reference</th>
            <th scope="col">Subject</th>
            <th scope="col">Category</th>
            <th scope="col">Province</th>
            <th scope="col">Urgency</th>
            <th scope="col">Status</th>
            <th scope="col">Received</th>
            <th scope="col"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td class="mono small"><?= e($row['reference_no']) ?></td>
              <td class="small"><?= e(excerpt($row['title'], 52)) ?></td>
              <td class="small"><?= e(excerpt($row['category_name'], 22)) ?></td>
              <td class="small"><?= e($row['province_name'] ?: '—') ?></td>
              <td>
                <span class="badge badge-soft bg-<?= e(urgencyColor((string)$row['urgency'])) ?>">
                  <?= e(ucfirst((string)$row['urgency'])) ?>
                </span>
              </td>
              <td>
                <span class="badge badge-soft bg-<?= e(statusColor((string)$row['status'])) ?>">
                  <?= e(statusLabel((string)$row['status'])) ?>
                </span>
              </td>
              <td class="small text-muted"><?= e(d($row['created_at'])) ?></td>
              <td class="text-end">
                <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('reports/' . $row['id'])) ?>">Open</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php \App\Core\View::partial('pagination', ['page' => $page]); ?>
