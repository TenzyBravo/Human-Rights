<?php
$messages = \App\Core\Flash::take();
if ($messages === []) {
    return;
}
?>
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1090">
  <?php foreach ($messages as $m): ?>
    <div class="toast align-items-center border-0 show flash-toast flash-<?= e($m['type']) ?>"
         role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="6000">
      <div class="d-flex">
        <div class="toast-body d-flex align-items-start gap-2">
          <i class="bi <?= e(match ($m['type']) {
              'success' => 'bi-check-circle-fill',
              'danger'  => 'bi-exclamation-octagon-fill',
              'warning' => 'bi-exclamation-triangle-fill',
              default   => 'bi-info-circle-fill',
          }) ?> fs-6"></i>
          <span><?= e($m['message']) ?></span>
        </div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  <?php endforeach; ?>
</div>
