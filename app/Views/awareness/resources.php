<?php /** @var array $resources */
$icons = ['document' => 'bi-file-earmark-pdf', 'video' => 'bi-play-btn', 'audio' => 'bi-mic',
          'image' => 'bi-image', 'link' => 'bi-link-45deg', 'training' => 'bi-mortarboard'];
?>
<section class="section">
  <div class="container">
    <div class="text-center mb-5">
      <span class="eyebrow">Learn</span>
      <h1 class="section-title fw-semibold mt-3 mb-2">Educational resources</h1>
      <p class="text-muted mx-auto" style="max-width:42rem">
        Guides, source documents and training material — free to read, download and share.
      </p>
    </div>

    <?php if ($resources === []): ?>
      <div class="card">
        <div class="empty-state"><i class="bi bi-journal"></i>No resources have been published yet.</div>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($resources as $resource): ?>
          <div class="col-md-6 col-lg-4">
            <a class="card card-hover h-100 p-4 text-reset" href="<?= e(url('resources/' . $resource['slug'])) ?>">
              <div class="stat-icon tint-primary mb-3">
                <i class="bi <?= e($icons[$resource['resource_type']] ?? 'bi-file-earmark') ?>"></i>
              </div>
              <span class="badge badge-soft bg-secondary mb-2 align-self-start">
                <?= e(statusLabel((string)$resource['resource_type'])) ?>
              </span>
              <h2 class="h6 fw-semibold"><?= e($resource['title']) ?></h2>
              <p class="text-muted small flex-grow-1 mb-2"><?= e(excerpt($resource['description'], 130)) ?></p>
              <span class="text-muted fs-tiny">
                <i class="bi bi-download me-1"></i><?= e(number_format((int)$resource['downloads'])) ?> opened
              </span>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
