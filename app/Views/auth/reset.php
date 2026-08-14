<?php /** @var array $errors @var string $token */ ?>
<h1 class="h3 fw-semibold mb-1">Choose a new password</h1>
<p class="text-muted small mb-4">Pick something you have not used on this platform before.</p>

<form method="post" action="<?= e(url('reset-password')) ?>" novalidate>
  <?= csrf() ?>
  <input type="hidden" name="token" value="<?= e($token) ?>">

  <div class="mb-3">
    <label class="form-label required" for="password">New password</label>
    <div class="input-group">
      <input type="password" id="password" name="password" required autofocus autocomplete="new-password"
             class="form-control<?= invalid($errors, 'password') ?>" data-pw-meter="pwMeter">
      <button class="btn btn-outline-secondary" type="button" data-toggle-password="password"
              aria-label="Show password"><i class="bi bi-eye"></i></button>
    </div>
    <div class="pw-meter mt-2" id="pwMeter"><span></span></div>
    <div class="d-flex justify-content-between">
      <div class="form-text">At least 10 characters, mixed case, a number and a symbol.</div>
      <div class="form-text fw-semibold" id="pwMeter-label"></div>
    </div>
    <?= errorFor($errors, 'password') ?>
  </div>

  <div class="mb-4">
    <label class="form-label required" for="password_confirmation">Confirm new password</label>
    <input type="password" id="password_confirmation" name="password_confirmation" required
           autocomplete="new-password" class="form-control<?= invalid($errors, 'password_confirmation') ?>">
    <?= errorFor($errors, 'password_confirmation') ?>
  </div>

  <button type="submit" class="btn btn-primary w-100 mb-3">
    <i class="bi bi-shield-check me-2"></i>Change password
  </button>
  <p class="text-center small mb-0"><a href="<?= e(url('login')) ?>">Back to sign in</a></p>
</form>
