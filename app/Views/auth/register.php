<?php /** @var array $errors @var array $old @var array $provinces @var string $captcha */ ?>
<h1 class="h3 fw-semibold mb-1">Create your account</h1>
<p class="text-muted small mb-4">
  An account lets you track every report you file and receive updates in one place.
</p>

<form method="post" action="<?= e(url('register')) ?>" novalidate>
  <?= csrf() ?>
  <div class="row g-3">
    <div class="col-sm-6">
      <label class="form-label required" for="first_name">First name</label>
      <input type="text" id="first_name" name="first_name" required maxlength="80" autocomplete="given-name"
             class="form-control<?= invalid($errors, 'first_name') ?>" value="<?= old($old, 'first_name') ?>">
      <?= errorFor($errors, 'first_name') ?>
    </div>
    <div class="col-sm-6">
      <label class="form-label required" for="last_name">Last name</label>
      <input type="text" id="last_name" name="last_name" required maxlength="80" autocomplete="family-name"
             class="form-control<?= invalid($errors, 'last_name') ?>" value="<?= old($old, 'last_name') ?>">
      <?= errorFor($errors, 'last_name') ?>
    </div>

    <div class="col-12">
      <label class="form-label required" for="email">Email address</label>
      <input type="email" id="email" name="email" required maxlength="190" autocomplete="email"
             class="form-control<?= invalid($errors, 'email') ?>" value="<?= old($old, 'email') ?>">
      <div class="form-text">We send your verification link and case updates to this address.</div>
      <?= errorFor($errors, 'email') ?>
    </div>

    <div class="col-sm-6">
      <label class="form-label" for="phone">Phone number</label>
      <input type="tel" id="phone" name="phone" maxlength="30" autocomplete="tel"
             class="form-control<?= invalid($errors, 'phone') ?>" value="<?= old($old, 'phone') ?>"
             placeholder="+260 97 000 0000">
      <?= errorFor($errors, 'phone') ?>
    </div>
    <div class="col-sm-6">
      <label class="form-label" for="nationality">Nationality</label>
      <input type="text" id="nationality" name="nationality" maxlength="80"
             class="form-control" value="<?= old($old, 'nationality', 'Zambian') ?>">
    </div>

    <div class="col-sm-6">
      <label class="form-label" for="province_id">Province</label>
      <select id="province_id" name="province_id" class="form-select">
        <option value="">Select province</option>
        <?php foreach ($provinces as $province): ?>
          <option value="<?= e((string)$province['id']) ?>"
            <?= (string)($old['province_id'] ?? '') === (string)$province['id'] ? ' selected' : '' ?>>
            <?= e($province['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-sm-6">
      <label class="form-label" for="district_id">District</label>
      <select id="district_id" name="district_id" class="form-select"
              data-districts-for="province_id"
              data-endpoint="<?= e(url('api/districts/__ID__')) ?>">
        <option value="">Select a province first</option>
      </select>
    </div>

    <div class="col-12">
      <label class="form-label" for="address">Address</label>
      <input type="text" id="address" name="address" maxlength="255"
             class="form-control" value="<?= old($old, 'address') ?>">
    </div>

    <div class="col-12">
      <label class="form-label required" for="password">Password</label>
      <div class="input-group">
        <input type="password" id="password" name="password" required autocomplete="new-password"
               class="form-control<?= invalid($errors, 'password') ?>" data-pw-meter="pwMeter">
        <button class="btn btn-outline-secondary" type="button" data-toggle-password="password"
                aria-label="Show password"><i class="bi bi-eye"></i></button>
      </div>
      <div class="pw-meter mt-2" id="pwMeter"><span></span></div>
      <div class="d-flex justify-content-between">
        <div class="form-text">At least 10 characters, with upper and lower case, a number and a symbol.</div>
        <div class="form-text fw-semibold" id="pwMeter-label"></div>
      </div>
      <?= errorFor($errors, 'password') ?>
    </div>

    <div class="col-12">
      <label class="form-label required" for="password_confirmation">Confirm password</label>
      <input type="password" id="password_confirmation" name="password_confirmation" required
             autocomplete="new-password"
             class="form-control<?= invalid($errors, 'password_confirmation') ?>">
      <?= errorFor($errors, 'password_confirmation') ?>
    </div>

    <div class="col-12">
      <label class="form-label required" for="captcha">Security question: <?= e($captcha) ?></label>
      <input type="text" id="captcha" name="captcha" required inputmode="numeric" autocomplete="off"
             class="form-control<?= invalid($errors, 'captcha') ?>" style="max-width:9rem">
      <?= errorFor($errors, 'captcha') ?>
    </div>

    <div class="col-12">
      <div class="form-check">
        <input class="form-check-input<?= invalid($errors, 'terms') ?>" type="checkbox"
               id="terms" name="terms" value="1" required>
        <label class="form-check-label small" for="terms">
          I confirm the information I provide will be truthful, and I consent to it being
          processed for the purpose of handling human rights complaints.
        </label>
        <?= errorFor($errors, 'terms') ?>
      </div>
    </div>

    <div class="col-12">
      <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-person-plus me-2"></i>Create account
      </button>
    </div>
  </div>

  <p class="text-center small mt-3 mb-0">
    Already registered? <a href="<?= e(url('login')) ?>" class="fw-semibold">Sign in</a>
  </p>
</form>
