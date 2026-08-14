<?php
/** Derives a breadcrumb trail from the current path. */
$segments = array_values(array_filter(explode('/', \App\Core\Request::path())));
$trail    = [];
$path     = '';
foreach ($segments as $i => $segment) {
    $path .= '/' . $segment;
    $label = ctype_digit($segment)
        ? ($title !== '' ? $title : '#' . $segment)
        : ucwords(str_replace('-', ' ', $segment));
    $trail[] = ['label' => $label, 'url' => $path, 'last' => $i === count($segments) - 1];
}
?>
<nav aria-label="Breadcrumb" class="d-none d-md-block">
  <ol class="breadcrumb mb-0 small">
    <li class="breadcrumb-item"><a href="<?= e(url(\App\Core\Auth::homeRoute())) ?>"><i class="bi bi-house-door"></i></a></li>
    <?php foreach ($trail as $crumb): ?>
      <?php if ($crumb['last']): ?>
        <li class="breadcrumb-item active" aria-current="page"><?= e($crumb['label']) ?></li>
      <?php else: ?>
        <li class="breadcrumb-item"><a href="<?= e(url($crumb['url'])) ?>"><?= e($crumb['label']) ?></a></li>
      <?php endif; ?>
    <?php endforeach; ?>
  </ol>
</nav>
