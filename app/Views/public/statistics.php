<?php /** @var array $stats @var array $byCategory @var array $byProvince @var array $byStatus @var array $trend */ ?>
<section class="section">
  <div class="container">
    <div class="text-center mb-4">
      <span class="eyebrow">Transparency</span>
      <h1 class="section-title fw-semibold mt-3 mb-2">Human rights statistics</h1>
      <p class="text-muted mx-auto" style="max-width:42rem">
        Aggregate figures only. No personal details, locations or case contents are ever published here.
      </p>
    </div>

    <div class="row g-3 mb-4">
      <?php
      $tiles = [
          ['Reports received', $stats['reports_received'], 'bi-file-earmark-text', 'tint-primary'],
          ['Cases concluded',  $stats['cases_resolved'],   'bi-check2-circle',     'tint-secondary'],
          ['Active campaigns', $stats['campaigns'],        'bi-megaphone',         'tint-warning'],
          ['Provinces covered',$stats['provinces'],        'bi-geo-alt',           'tint-dark'],
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
      <div class="col-lg-8">
        <div class="card p-3 p-lg-4">
          <h2 class="h6 fw-semibold mb-3">Reports submitted per month (last 12 months)</h2>
          <div class="chart-box">
            <canvas data-chart="line" data-chart-data="<?= ejson($trend) ?>"
                    aria-label="Monthly report volume" role="img"></canvas>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card p-3 p-lg-4 h-100">
          <h2 class="h6 fw-semibold mb-3">By status</h2>
          <div class="chart-box">
            <canvas data-chart="doughnut" data-chart-data="<?= ejson($byStatus) ?>"
                    aria-label="Reports by status" role="img"></canvas>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card p-3 p-lg-4 h-100">
          <h2 class="h6 fw-semibold mb-3">By category</h2>
          <div class="chart-box">
            <canvas data-chart="hbar" data-chart-data="<?= ejson($byCategory) ?>"
                    aria-label="Reports by category" role="img"></canvas>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card p-3 p-lg-4 h-100">
          <h2 class="h6 fw-semibold mb-3">Geographic distribution</h2>
          <div class="chart-box">
            <canvas data-chart="bar" data-chart-data="<?= ejson($byProvince) ?>"
                    aria-label="Reports by province" role="img"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- accessible text equivalent of the charts -->
    <details class="card p-3 p-lg-4 mt-4">
      <summary class="fw-semibold small cursor-pointer">View this data as a table</summary>
      <div class="table-responsive mt-3">
        <table class="table table-sm">
          <caption class="visually-hidden">Reports by category</caption>
          <thead><tr><th scope="col">Category</th><th scope="col" class="text-end">Reports</th></tr></thead>
          <tbody>
            <?php foreach ($byCategory as $row): ?>
              <tr>
                <td><?= e($row['label']) ?></td>
                <td class="text-end"><?= e(number_format((int)$row['value'])) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if ($byCategory === []): ?>
              <tr><td colspan="2" class="text-muted">No reports recorded yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </details>
  </div>
</section>

<?php
$scripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>'
         . '<script src="' . e(asset('js/charts.js')) . '"></script>';
