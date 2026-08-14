<?php /** @var array $item */
$attachmentId = null;
if (is_string($item['file_path']) && str_starts_with($item['file_path'], 'attachment:')) {
    $attachmentId = (int)substr($item['file_path'], 11);
}
?>
<section class="section">
  <div class="container" style="max-width:46rem">
    <a href="<?= e(url('resources')) ?>" class="small text-muted"><i class="bi bi-arrow-left me-1"></i>All resources</a>

    <div class="card p-4 p-lg-5 mt-3">
      <span class="badge badge-soft bg-secondary align-self-start mb-3">
        <?= e(statusLabel((string)$item['resource_type'])) ?>
      </span>
      <h1 class="h3 fw-semibold mb-3"><?= e($item['title']) ?></h1>

      <?php if ($item['description']): ?>
        <div class="text-muted" style="line-height:1.8"><?= nl2br(e($item['description'])) ?></div>
      <?php endif; ?>

      <hr class="my-4 hr-soft">

      <div class="d-flex flex-wrap gap-2">
        <?php if ($attachmentId !== null): ?>
          <a class="btn btn-primary" href="<?= e(url('attachments/' . $attachmentId)) ?>" target="_blank" rel="noopener">
            <i class="bi bi-download me-2"></i>Open the file
          </a>
        <?php endif; ?>
        <?php if ($item['external_url']): ?>
          <a class="btn btn-outline-primary" href="<?= e($item['external_url']) ?>"
             target="_blank" rel="noopener noreferrer">
            <i class="bi bi-box-arrow-up-right me-2"></i>Open the source
          </a>
        <?php endif; ?>
        <?php if ($attachmentId === null && !$item['external_url']): ?>
          <p class="text-muted small mb-0">
            <i class="bi bi-info-circle me-1"></i>
            No file has been attached to this resource yet.
            <a href="<?= e(url('contact')) ?>">Request a copy</a>.
          </p>
        <?php endif; ?>
      </div>

      <p class="text-muted fs-tiny mt-3 mb-0">
        Published <?= e(d($item['created_at'])) ?> ·
        <?= e(number_format((int)$item['downloads'])) ?> times opened
      </p>
    </div>
  </div>
</section>
