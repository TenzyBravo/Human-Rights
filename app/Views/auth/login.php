<?php /** @var array $errors @var array $old @var string $captcha */ ?>
<h1 class="h3 fw-semibold mb-1">Welcome back</h1>
<p class="text-muted small mb-4">Sign in to manage reports, cases and campaigns.</p>

<form method="post" action="<?= e(url('login')) ?>" novalidate>
  <?= csrf() ?>

  <div class="mb-3">
    <label class="form-label required" for="email">Email address</label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-envelope"></i></span>
      <input type="email" id="email" name="email" required autocomplete="username" autofocus
             maxlength="190" class="form-control<?= invalid($errors, 'email') ?>" value="<?= old($old, 'email') ?>">
    </div>
    <?= errorFor($errors, 'email') ?>
  </div>

  <div class="mb-3">
    <div class="d-flex justify-content-between">
      <label class="form-label required" for="password">Password</label>
      <a href="<?= e(url('forgot-password')) ?>" class="small">Forgot password?</a>
    </div>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-lock"></i></span>
      <input type="password" id="password" name="password" required autocomplete="current-password"
             class="form-control<?= invalid($errors, 'password') ?>">
      <button class="btn btn-outline-secondary" type="button" data-toggle-password="password"
              aria-label="Show password"><i class="bi bi-eye"></i></button>
    </div>
    <?= errorFor($errors, 'password') ?>
  </div>

  <?php if ((int)\App\Core\Session::get('_login_failures', 0) >= 2): ?>
    <div class="mb-3">
      <label class="form-label required" for="captcha">Security question: <?= e($captcha) ?></label>
      <input type="text" id="captcha" name="captcha" required inputmode="numeric" autocomplete="off"
             class="form-control<?= invalid($errors, 'captcha') ?>" style="max-width:9rem">
      <?= errorFor($errors, 'captcha') ?>
    </div>
  <?php endif; ?>

  <div class="form-check mb-4">
    <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
    <label class="form-check-label small" for="remember">Keep me signed in on this device</label>
  </div>

  <button type="submit" class="btn btn-primary w-100 mb-3">
    <i class="bi bi-box-arrow-in-right me-2"></i>Sign in
  </button>

  <p class="text-center small mb-0">
    No account yet? <a href="<?= e(url('register')) ?>" class="fw-semibold">Create one</a>
  </p>
</form>

<hr class="my-4 hr-soft">

<div class="card p-3">
  <div class="d-flex gap-3 align-items-start">
    <div class="stat-icon tint-secondary flex-shrink-0"><i class="bi bi-eye-slash"></i></div>
    <div>
      <div class="fw-semibold small">You do not need an account to report</div>
      <p class="text-muted fs-tiny mb-2">
        Anyone can file a report, anonymously if they prefer. An account simply lets you
        keep a history and receive updates in one place.
      </p>
      <a href="<?= e(url('report')) ?>" class="btn btn-outline-primary btn-sm">Report a violation</a>
    </div>
  </div>
</div>
