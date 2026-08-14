<?php /** @var array $summary @var array $top @var array $resources */
$chart = array_map(
    static fn(array $row) => ['label' => statusLabel((string)$row['type']), 'value' => (int)$row['views']],
    $summary
);
$totalViews = array_sum(array_map(static fn($r) => (int)$r['views'], $summary));
$totalItems = array_sum(array_map(static fn($r) => (int)$r['items'], $summary));
$published  = array_sum(array_map(static fn($r) => (int)$r['published'], $summary));
$downloads  = array_sum(array_map(static fn($r) => (int)$r['downloads'], $resources));
?>
<div class="mb-4">
  <h1 class="h4 fw-semibold mb-1">Campaign analytics</h1>
  <p class="text-muted small mb-0">Reach and engagement across published awareness content.</p>
</div>

<div class="row g-3 mb-4">
  <?php
  $tiles = [
      ['Total views',      $totalViews,  'bi-eye',            'tint-primary'],
      ['Published items',  $published,   'bi-check2-circle',  'tint-secondary'],
      ['All content',      $totalItems,  'bi-collection',     'tint-dark'],
      ['Resource opens',   $downloads,   'bi-download',       'tint-warning'],
  ];
  foreach ($tiles as [$label, $value, $icon, $tint]): ?>
    <div class="col-6 col-lg-3">
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
  <div class="col-lg-6">
    <div class="card p-3 p-lg-4 h-100">
      <h2 class="h6 fw-semibold mb-3">Views by content type</h2>
      <div class="chart-box chart-box-sm">
        <canvas data-chart="doughnut" data-chart-data="<?= ejson($chart) ?>"
                aria-label="Views by content type" role="img"></canvas>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card table-card h-100">
      <div class="p-3 border-bottom">
        <h2 class="h6 fw-semibold mb-0">Most viewed content</h2>
      </div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th scope="col">Title</th><th scope="col">Type</th><th scope="col" class="text-end">Views</th></tr></thead>
          <tbody>
            <?php if ($top === []): ?>
              <tr><td colspan="3" class="text-muted small">Nothing published yet.</td></tr>
            <?php else: foreach ($top as $row): ?>
              <tr>
                <td class="small"><?= e(excerpt($row['title'], 44)) ?></td>
                <td><span class="badge badge-soft bg-secondary"><?= e(statusLabel((string)$row['type'])) ?></span></td>
                <td class="text-end small fw-semibold"><?= e(number_format((int)$row['views'])) ?></td>
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
        <h2 class="h6 fw-semibold mb-0">Content breakdown</h2>
      </div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr>
              <th scope="col">Type</th><th scope="col" class="text-end">Items</th>
              <th scope="col" class="text-end">Published</th><th scope="col" class="text-end">Drafts</th>
              <th scope="col" class="text-end">Views</th><th scope="col" class="text-end">Avg. views</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($summary === []): ?>
              <tr><td colspan="6" class="text-muted small">No content created yet.</td></tr>
            <?php else: foreach ($summary as $row): ?>
              <?php $items = max(1, (int)$row['items']); ?>
              <tr>
                <td class="small fw-semibold"><?= e(statusLabel((string)$row['type'])) ?></td>
                <td class="text-end small"><?= e((string)(int)$row['items']) ?></td>
                <td class="text-end small"><?= e((string)(int)$row['published']) ?></td>
                <td class="text-end small"><?= e((string)(int)$row['drafts']) ?></td>
                <td class="text-end small"><?= e(number_format((int)$row['views'])) ?></td>
                <td class="text-end small"><?= e((string)round((int)$row['views'] / $items, 1)) ?></td>
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
        <h2 class="h6 fw-semibold mb-0">Resource engagement</h2>
      </div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr>
              <th scope="col">Resource</th><th scope="col">Type</th>
              <th scope="col">Status</th><th scope="col" class="text-end">Opens</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($resources === []): ?>
              <tr><td colspan="4" class="text-muted small">No resources published yet.</td></tr>
            <?php else: foreach ($resources as $resource): ?>
              <tr>
                <td class="small"><?= e(excerpt($resource['title'], 56)) ?></td>
                <td><span class="badge badge-soft bg-secondary"><?= e(statusLabel((string)$resource['resource_type'])) ?></span></td>
                <td>
                  <span class="badge badge-soft bg-<?= (int)$resource['is_published'] === 1 ? 'success' : 'warning' ?>">
                    <?= (int)$resource['is_published'] === 1 ? 'Published' : 'Draft' ?>
                  </span>
                </td>
                <td class="text-end small fw-semibold"><?= e(number_format((int)$resource['downloads'])) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
