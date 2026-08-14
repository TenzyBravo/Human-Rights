<?php /** @var array $item @var array $related */ ?>
<section class="section">
  <div class="container" style="max-width:52rem">
    <a href="<?= e(url($item['type'] === 'news' ? 'news' : ($item['type'] === 'success_story' ? 'stories' : 'campaigns'))) ?>"
       class="small text-muted"><i class="bi bi-arrow-left me-1"></i>Back</a>

    <article class="mt-3">
      <span class="badge badge-soft bg-primary mb-2"><?= e(statusLabel((string)$item['type'])) ?></span>
      <h1 class="section-title fw-semibold mb-3"><?= e($item['title']) ?></h1>

      <div class="d-flex flex-wrap gap-3 text-muted small mb-4 pb-3 border-bottom">
        <span><i class="bi bi-calendar3 me-1"></i><?= e(d($item['published_at'] ?: $item['created_at'])) ?></span>
        <?php if ($item['author']): ?>
          <span><i class="bi bi-person me-1"></i><?= e($item['author']) ?></span>
        <?php endif; ?>
        <?php if ($item['starts_on']): ?>
          <span>
            <i class="bi bi-clock me-1"></i>Runs <?= e(d($item['starts_on'])) ?>
            <?= $item['ends_on'] ? ' – ' . e(d($item['ends_on'])) : '' ?>
          </span>
        <?php endif; ?>
        <span><i class="bi bi-eye me-1"></i><?= e(number_format((int)$item['views'])) ?> views</span>
      </div>

      <?php if ($item['summary']): ?>
        <p class="lead fs-6 text-muted"><?= e($item['summary']) ?></p>
      <?php endif; ?>

      <div class="mt-3" style="line-height:1.8">
        <?php if ($item['body']): ?>
          <?= safeHtml(nl2br(e($item['body']))) ?>
        <?php else: ?>
          <p class="text-muted small">No further detail has been published for this item.</p>
        <?php endif; ?>
      </div>
    </article>

    <div class="card p-4 mt-5">
      <div class="row g-3 align-items-center">
        <div class="col-md-8">
          <h2 class="h6 fw-semibold mb-1">Has your right been violated?</h2>
          <p class="text-muted small mb-0">
            You can file a report in a few minutes, anonymously if you prefer.
          </p>
        </div>
        <div class="col-md-4 text-md-end">
          <a href="<?= e(url('report')) ?>" class="btn btn-primary">
            <i class="bi bi-megaphone me-2"></i>Report now
          </a>
        </div>
      </div>
    </div>

    <?php if ($related !== []): ?>
      <h2 class="h6 fw-semibold mt-5 mb-3">More like this</h2>
      <div class="row g-3">
        <?php foreach ($related as $other): ?>
          <div class="col-md-4">
            <a class="card card-hover h-100 p-3 text-reset" href="<?= e(url('campaigns/' . $other['slug'])) ?>">
              <div class="fw-semibold small mb-1"><?= e(excerpt($other['title'], 54)) ?></div>
              <div class="text-muted fs-tiny"><?= e(d($other['published_at'] ?: $other['created_at'])) ?></div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
