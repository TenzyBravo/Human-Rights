<?php /** @var array $errors @var array $old */ ?>
<h1 class="h3 fw-semibold mb-1">Reset your password</h1>
<p class="text-muted small mb-4">
  Enter the email address on your account and we will send you a link to choose a new password.
  The link expires after one hour.
</p>

<form method="post" action="<?= e(url('forgot-password')) ?>" novalidate>
  <?= csrf() ?>
  <div class="mb-3">
    <label class="form-label required" for="email">Email address</label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-envelope"></i></span>
      <input type="email" id="email" name="email" required autofocus maxlength="190"
             class="form-control<?= invalid($errors, 'email') ?>" value="<?= old($old, 'email') ?>">
    </div>
    <?= errorFor($errors, 'email') ?>
  </div>
  <button type="submit" class="btn btn-primary w-100 mb-3">
    <i class="bi bi-send me-2"></i>Send reset link
  </button>
  <p class="text-center small mb-0"><a href="<?= e(url('login')) ?>">Back to sign in</a></p>
</form>
