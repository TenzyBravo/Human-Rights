<div class="text-center">
  <div class="stat-icon tint-primary mx-auto mb-3" style="width:56px;height:56px;font-size:1.5rem">
    <i class="bi bi-envelope-check"></i>
  </div>
  <h1 class="h4 fw-semibold mb-2">Confirm your email address</h1>
  <p class="text-muted small mb-4">
    We sent a verification link to
    <strong><?= e(\App\Core\Auth::user()['email'] ?? '') ?></strong>.
    Open it to activate your account. Check your spam folder if it has not arrived.
  </p>

  <form method="post" action="<?= e(url('verify-resend')) ?>" class="mb-3">
    <?= csrf() ?>
    <button class="btn btn-primary"><i class="bi bi-arrow-repeat me-2"></i>Resend the link</button>
  </form>

  <form method="post" action="<?= e(url('logout')) ?>">
    <?= csrf() ?>
    <button class="btn btn-link btn-sm text-muted">Sign out</button>
  </form>
</div>
