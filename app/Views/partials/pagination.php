<?php
/** @var array{page:int,pages:int,total:int,perPage:int} $page */
if (($page['pages'] ?? 1) <= 1) {
    return;
}
$current = (int)$page['page'];
$last    = (int)$page['pages'];
$window  = 2;
$from    = max(1, $current - $window);
$to      = min($last, $current + $window);
?>
<nav class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3" aria-label="Pagination">
  <p class="text-muted small mb-0">
    Showing <?= e((string)((($current - 1) * (int)$page['perPage']) + 1)) ?>–<?=
      e((string)min($current * (int)$page['perPage'], (int)$page['total'])) ?>
    of <?= e((string)$page['total']) ?>
  </p>
  <ul class="pagination pagination-sm mb-0">
    <li class="page-item<?= $current <= 1 ? ' disabled' : '' ?>">
      <a class="page-link" href="<?= e(queryWith(['page' => $current - 1])) ?>" aria-label="Previous">
        <i class="bi bi-chevron-left"></i>
      </a>
    </li>
    <?php if ($from > 1): ?>
      <li class="page-item"><a class="page-link" href="<?= e(queryWith(['page' => 1])) ?>">1</a></li>
      <?php if ($from > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
    <?php endif; ?>
    <?php for ($i = $from; $i <= $to; $i++): ?>
      <li class="page-item<?= $i === $current ? ' active' : '' ?>">
        <a class="page-link" href="<?= e(queryWith(['page' => $i])) ?>"><?= e((string)$i) ?></a>
      </li>
    <?php endfor; ?>
    <?php if ($to < $last): ?>
      <?php if ($to < $last - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
      <li class="page-item"><a class="page-link" href="<?= e(queryWith(['page' => $last])) ?>"><?= e((string)$last) ?></a></li>
    <?php endif; ?>
    <li class="page-item<?= $current >= $last ? ' disabled' : '' ?>">
      <a class="page-link" href="<?= e(queryWith(['page' => $current + 1])) ?>" aria-label="Next">
        <i class="bi bi-chevron-right"></i>
      </a>
    </li>
  </ul>
</nav>
