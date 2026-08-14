<?php
/** @var array $stats @var array $campaigns @var array $news @var array $resources @var array $events @var array $categories */
?>
<section class="hero">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="eyebrow"><i class="bi bi-shield-check"></i>Confidential &amp; secure</span>
        <h1 class="fw-semibold mt-3 mb-3">
          Report a human rights violation.<br>
          <span class="text-primary">Have it taken seriously.</span>
        </h1>
        <p class="hero-lead mb-4">
          File a complaint online in minutes — with your name or completely anonymously.
          Every report receives a reference number, an assigned officer and a tracked
          investigation you can follow from start to resolution.
        </p>
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= e(url('report')) ?>" class="btn btn-primary btn-lg">
            <i class="bi bi-megaphone me-2"></i>Report a violation
          </a>
          <a href="<?= e(url('track')) ?>" class="btn btn-outline-secondary btn-lg">
            <i class="bi bi-search me-2"></i>Track a report
          </a>
        </div>
        <p class="text-muted small mt-3 mb-0">
          <i class="bi bi-telephone me-1"></i>
          In an emergency, call the hotline
          <strong><?= e(\App\Models\Setting::get('hotline', '933')) ?></strong> immediately.
        </p>
      </div>

      <div class="col-lg-6">
        <div class="hero-card">
          <h2 class="h6 text-uppercase text-muted fs-tiny mb-3">Platform at a glance</h2>
          <div class="row g-3">
            <div class="col-6">
              <div class="stat-card card border-0 h-100">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <div class="stat-value"><?= e(number_format($stats['reports_received'])) ?></div>
                    <div class="stat-label">Reports received</div>
                  </div>
                  <div class="stat-icon tint-primary"><i class="bi bi-file-earmark-text"></i></div>
                </div>
              </div>
            </div>
            <div class="col-6">
              <div class="stat-card card border-0 h-100">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <div class="stat-value"><?= e(number_format($stats['cases_resolved'])) ?></div>
                    <div class="stat-label">Cases concluded</div>
                  </div>
                  <div class="stat-icon tint-secondary"><i class="bi bi-check2-circle"></i></div>
                </div>
              </div>
            </div>
            <div class="col-6">
              <div class="stat-card card border-0 h-100">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <div class="stat-value"><?= e(number_format($stats['campaigns'])) ?></div>
                    <div class="stat-label">Active campaigns</div>
                  </div>
                  <div class="stat-icon tint-warning"><i class="bi bi-megaphone"></i></div>
                </div>
              </div>
            </div>
            <div class="col-6">
              <div class="stat-card card border-0 h-100">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <div class="stat-value"><?= e(number_format($stats['provinces'])) ?></div>
                    <div class="stat-label">Provinces covered</div>
                  </div>
                  <div class="stat-icon tint-dark"><i class="bi bi-geo-alt"></i></div>
                </div>
              </div>
            </div>
          </div>
          <a href="<?= e(url('statistics')) ?>" class="btn btn-link btn-sm px-0 mt-2">
            View full statistics <i class="bi bi-arrow-right ms-1"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- what can be reported -->
<section class="section">
  <div class="container">
    <div class="text-center mb-4">
      <span class="eyebrow">Know your rights</span>
      <h2 class="section-title fw-semibold mt-3 mb-2">What can you report?</h2>
      <p class="text-muted mx-auto" style="max-width:44rem">
        Any act or omission that denies a person their fundamental rights and freedoms.
        Pick the closest category — the officer reviewing your case will refine it if needed.
      </p>
    </div>
    <div class="row g-3">
      <?php foreach ($categories as $category): ?>
        <div class="col-sm-6 col-lg-4 col-xl-3">
          <a class="category-pill h-100" href="<?= e(url('report?category=' . (int)$category['id'])) ?>">
            <i class="bi <?= e($category['icon'] ?: 'bi-flag') ?> text-primary"></i>
            <span><?= e($category['name']) ?></span>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- how it works -->
<section class="section pt-0">
  <div class="container">
    <div class="card p-4 p-lg-5">
      <div class="row g-4 align-items-center">
        <div class="col-lg-4">
          <span class="eyebrow">The process</span>
          <h2 class="section-title fw-semibold mt-3 mb-2">From report to resolution</h2>
          <p class="text-muted small mb-0">
            You stay informed at every stage. Notifications are sent by email, and you can
            check progress at any time using your reference number.
          </p>
        </div>
        <div class="col-lg-8">
          <div class="row g-3">
            <?php
            $steps = [
                ['1', 'bi-pencil-square', 'Submit', 'Complete the guided form and attach any evidence you have.'],
                ['2', 'bi-clipboard-check', 'Review', 'A supervisor reviews the complaint and opens a case file.'],
                ['3', 'bi-person-badge', 'Investigate', 'An officer is assigned, gathers findings and interviews parties.'],
                ['4', 'bi-check2-circle', 'Resolve', 'The outcome is recorded and communicated to you.'],
            ];
            foreach ($steps as [$number, $icon, $heading, $text]): ?>
              <div class="col-sm-6">
                <div class="d-flex gap-3">
                  <div class="stat-icon tint-primary flex-shrink-0"><i class="bi <?= e($icon) ?>"></i></div>
                  <div>
                    <div class="fw-semibold small">Step <?= e($number) ?> · <?= e($heading) ?></div>
                    <p class="text-muted small mb-0"><?= e($text) ?></p>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- campaigns -->
<?php if ($campaigns !== []): ?>
<section class="section pt-0">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <div>
        <span class="eyebrow">Awareness</span>
        <h2 class="section-title fw-semibold mt-3 mb-0">Current campaigns</h2>
      </div>
      <a href="<?= e(url('campaigns')) ?>" class="btn btn-outline-secondary btn-sm">View all</a>
    </div>
    <div class="row g-4">
      <?php foreach ($campaigns as $item): ?>
        <div class="col-md-6 col-lg-4">
          <article class="card card-hover h-100">
            <div class="card-body">
              <span class="badge badge-soft bg-primary mb-2">Campaign</span>
              <h3 class="h6 fw-semibold">
                <a class="text-reset" href="<?= e(url('campaigns/' . $item['slug'])) ?>"><?= e($item['title']) ?></a>
              </h3>
              <p class="text-muted small mb-3"><?= e(excerpt($item['summary'], 120)) ?></p>
              <?php if ($item['starts_on']): ?>
                <p class="fs-tiny text-muted mb-0">
                  <i class="bi bi-calendar3 me-1"></i>
                  <?= e(d($item['starts_on'])) ?><?= $item['ends_on'] ? ' – ' . e(d($item['ends_on'])) : '' ?>
                </p>
              <?php endif; ?>
            </div>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- resources + news -->
<section class="section pt-0">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-7">
        <h2 class="h5 fw-semibold mb-3"><i class="bi bi-journal-text text-primary me-2"></i>Educational resources</h2>
        <div class="list-group">
          <?php if ($resources === []): ?>
            <div class="empty-state"><i class="bi bi-journal"></i>No resources published yet.</div>
          <?php else: foreach ($resources as $resource): ?>
            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3"
               href="<?= e(url('resources/' . $resource['slug'])) ?>">
              <i class="bi <?= e(match ($resource['resource_type']) {
                    'video' => 'bi-play-btn', 'audio' => 'bi-mic',
                    'training' => 'bi-mortarboard', 'link' => 'bi-link-45deg',
                    default => 'bi-file-earmark-pdf' }) ?> fs-5 text-primary"></i>
              <span class="flex-grow-1">
                <span class="d-block fw-semibold small"><?= e($resource['title']) ?></span>
                <span class="d-block text-muted fs-tiny"><?= e(excerpt($resource['description'], 90)) ?></span>
              </span>
              <i class="bi bi-chevron-right text-muted"></i>
            </a>
          <?php endforeach; endif; ?>
        </div>
        <a href="<?= e(url('resources')) ?>" class="btn btn-link btn-sm px-0 mt-2">
          Browse the library <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>

      <div class="col-lg-5">
        <h2 class="h5 fw-semibold mb-3"><i class="bi bi-newspaper text-primary me-2"></i>Latest news</h2>
        <?php if ($news === []): ?>
          <div class="empty-state"><i class="bi bi-newspaper"></i>No news yet.</div>
        <?php else: foreach ($news as $item): ?>
          <article class="card mb-2">
            <div class="card-body py-3">
              <div class="fs-tiny text-muted mb-1"><?= e(d($item['published_at'] ?: $item['created_at'])) ?></div>
              <a class="fw-semibold small text-reset" href="<?= e(url('campaigns/' . $item['slug'])) ?>">
                <?= e($item['title']) ?>
              </a>
            </div>
          </article>
        <?php endforeach; endif; ?>

        <?php if ($events !== []): ?>
          <h2 class="h6 fw-semibold mt-4 mb-2">
            <i class="bi bi-calendar-event text-primary me-2"></i>Upcoming events
          </h2>
          <?php foreach ($events as $event): ?>
            <div class="d-flex gap-3 align-items-start mb-3">
              <div class="stat-icon tint-secondary flex-shrink-0">
                <span class="fw-semibold" style="font-size:.72rem"><?= e(date('d M', strtotime($event['starts_at']))) ?></span>
              </div>
              <div>
                <div class="fw-semibold small"><?= e($event['title']) ?></div>
                <div class="text-muted fs-tiny">
                  <?= e($event['venue'] ?: 'Venue to be confirmed') ?>
                  <?= $event['province_name'] ? ' · ' . e($event['province_name']) : '' ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          <a href="<?= e(url('events')) ?>" class="btn btn-link btn-sm px-0">All events <i class="bi bi-arrow-right ms-1"></i></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- contact strip -->
<section class="section pt-0">
  <div class="container">
    <div class="card p-4 p-lg-5 text-center">
      <h2 class="section-title fw-semibold mb-2">Need help filing a report?</h2>
      <p class="text-muted mb-4 mx-auto" style="max-width:38rem">
        Our officers can assist you by phone, by email, or in person at any provincial desk.
        Reports may always be submitted anonymously.
      </p>
      <div class="d-flex flex-wrap gap-2 justify-content-center">
        <a href="<?= e(url('contact')) ?>" class="btn btn-primary"><i class="bi bi-envelope me-2"></i>Contact us</a>
        <a href="<?= e(url('faqs')) ?>" class="btn btn-outline-secondary"><i class="bi bi-question-circle me-2"></i>Read the FAQs</a>
      </div>
    </div>
  </div>
</section>
