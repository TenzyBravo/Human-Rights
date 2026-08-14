<?php /** @var array $items @var string $heading @var string $lead */ ?>
<section class="section">
  <div class="container">
    <div class="page-banner mb-5">
      <img src="<?= e(asset('img/HR2.jpg')) ?>" alt="" aria-hidden="true">
      <div class="page-banner-body">
        <span class="eyebrow">Awareness</span>
        <h1 class="section-title fw-semibold mt-3"><?= e($heading) ?></h1>
        <p><?= e($lead) ?></p>
      </div>
    </div>

    <?php if ($items === []): ?>
      <div class="card">
        <div class="empty-state">
          <i class="bi bi-megaphone"></i>
          Nothing has been published here yet. Please check back soon.
        </div>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($items as $item): ?>
          <div class="col-md-6 col-lg-4">
            <article class="card card-hover h-100">
              <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <span class="badge badge-soft bg-primary"><?= e(statusLabel((string)$item['type'])) ?></span>
                  <span class="text-muted fs-tiny">
                    <?= e(d($item['published_at'] ?: $item['created_at'])) ?>
                  </span>
                </div>
                <h2 class="h6 fw-semibold">
                  <a class="text-reset" href="<?= e(url('campaigns/' . $item['slug'])) ?>">
                    <?= e($item['title']) ?>
                  </a>
                </h2>
                <p class="text-muted small flex-grow-1"><?= e(excerpt($item['summary'], 140)) ?></p>
                <div class="d-flex justify-content-between align-items-center">
                  <a class="btn btn-link btn-sm px-0" href="<?= e(url('campaigns/' . $item['slug'])) ?>">
                    Read more <i class="bi bi-arrow-right ms-1"></i>
                  </a>
                  <?php if ($item['starts_on']): ?>
                    <span class="text-muted fs-tiny">
                      <i class="bi bi-calendar3 me-1"></i><?= e(d($item['starts_on'])) ?>
                    </span>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
