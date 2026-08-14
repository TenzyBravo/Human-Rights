<?php /** @var int $code @var string $message */ ?>
<section class="section">
  <div class="container">
    <div class="card p-4 p-lg-5 text-center mx-auto" style="max-width:34rem">
      <div class="stat-icon tint-danger mx-auto mb-3" style="width:56px;height:56px;font-size:1.5rem">
        <i class="bi <?= e(match ($code) {
            403 => 'bi-shield-lock', 404 => 'bi-compass', 419 => 'bi-hourglass', default => 'bi-exclamation-triangle'
        }) ?>"></i>
      </div>
      <h1 class="display-6 fw-semibold mb-2"><?= e((string)$code) ?></h1>
      <p class="text-muted mb-4"><?= e($message) ?></p>
      <div class="d-flex gap-2 justify-content-center">
        <a href="<?= e(url(\App\Core\Auth::check() ? \App\Core\Auth::homeRoute() : '/')) ?>" class="btn btn-primary">
          <i class="bi bi-house-door me-2"></i>Go home
        </a>
        <a href="<?= e(url('contact')) ?>" class="btn btn-outline-secondary">Report a problem</a>
      </div>
    </div>
  </div>
</section>
