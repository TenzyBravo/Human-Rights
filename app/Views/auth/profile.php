<?php /** @var array $user @var array $provinces @var array $districts @var array $loginHistory @var array $errors */ ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h4 fw-semibold mb-1">My profile</h1>
    <p class="text-muted small mb-0">Keep your contact details current so we can reach you about your reports.</p>
  </div>
  <span class="badge badge-soft bg-<?= e($user['status'] === 'active' ? 'success' : 'warning') ?>">
    Account <?= e($user['status']) ?>
  </span>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card p-4">
      <h2 class="h6 fw-semibold mb-3">Personal details</h2>
      <form method="post" action="<?= e(url('my/profile')) ?>" novalidate>
        <?= csrf() ?>
        <div class="row g-3">
          <div class="col-sm-6">
            <label class="form-label required" for="first_name">First name</label>
            <input type="text" id="first_name" name="first_name" required maxlength="80"
                   class="form-control<?= invalid($errors, 'first_name') ?>" value="<?= e($user['first_name']) ?>">
            <?= errorFor($errors, 'first_name') ?>
          </div>
          <div class="col-sm-6">
            <label class="form-label required" for="last_name">Last name</label>
            <input type="text" id="last_name" name="last_name" required maxlength="80"
                   class="form-control<?= invalid($errors, 'last_name') ?>" value="<?= e($user['last_name']) ?>">
            <?= errorFor($errors, 'last_name') ?>
          </div>
          <div class="col-sm-6">
            <label class="form-label required" for="email">Email address</label>
            <input type="email" id="email" name="email" required maxlength="190"
                   class="form-control<?= invalid($errors, 'email') ?>" value="<?= e($user['email']) ?>">
            <?= errorFor($errors, 'email') ?>
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="phone">Phone number</label>
            <input type="tel" id="phone" name="phone" maxlength="30"
                   class="form-control<?= invalid($errors, 'phone') ?>" value="<?= e($user['phone']) ?>">
            <?= errorFor($errors, 'phone') ?>
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="province_id">Province</label>
            <select id="province_id" name="province_id" class="form-select">
              <option value="">Select province</option>
              <?php foreach ($provinces as $province): ?>
                <option value="<?= e((string)$province['id']) ?>"
                  <?= (int)$user['province_id'] === (int)$province['id'] ? ' selected' : '' ?>>
                  <?= e($province['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="district_id">District</label>
            <select id="district_id" name="district_id" class="form-select"
                    data-districts-for="province_id" data-endpoint="<?= e(url('api/districts/__ID__')) ?>">
              <option value="">Select district</option>
              <?php foreach ($districts as $district): ?>
                <?php if ((int)$district['province_id'] !== (int)$user['province_id']) { continue; } ?>
                <option value="<?= e((string)$district['id']) ?>"
                  <?= (int)$user['district_id'] === (int)$district['id'] ? ' selected' : '' ?>>
                  <?= e($district['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="nationality">Nationality</label>
            <input type="text" id="nationality" name="nationality" maxlength="80"
                   class="form-control" value="<?= e($user['nationality']) ?>">
          </div>
          <div class="col-12">
            <label class="form-label" for="address">Address</label>
            <input type="text" id="address" name="address" maxlength="255"
                   class="form-control" value="<?= e($user['address']) ?>">
          </div>
          <div class="col-12">
            <button class="btn btn-primary"><i class="bi bi-check2 me-2"></i>Save changes</button>
          </div>
        </div>
      </form>
    </div>

    <div class="card p-4 mt-4">
      <h2 class="h6 fw-semibold mb-3">Change password</h2>
      <form method="post" action="<?= e(url('my/password')) ?>" novalidate>
        <?= csrf() ?>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label required" for="current_password">Current password</label>
            <input type="password" id="current_password" name="current_password" required
                   autocomplete="current-password"
                   class="form-control<?= invalid($errors, 'current_password') ?>">
            <?= errorFor($errors, 'current_password') ?>
          </div>
          <div class="col-sm-6">
            <label class="form-label required" for="password">New password</label>
            <input type="password" id="password" name="password" required autocomplete="new-password"
                   class="form-control<?= invalid($errors, 'password') ?>" data-pw-meter="pwMeter">
            <div class="pw-meter mt-2" id="pwMeter"><span></span></div>
            <div class="form-text fw-semibold" id="pwMeter-label"></div>
            <?= errorFor($errors, 'password') ?>
          </div>
          <div class="col-sm-6">
            <label class="form-label required" for="password_confirmation">Confirm new password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required
                   autocomplete="new-password"
                   class="form-control<?= invalid($errors, 'password_confirmation') ?>">
            <?= errorFor($errors, 'password_confirmation') ?>
          </div>
          <div class="col-12">
            <button class="btn btn-outline-primary"><i class="bi bi-shield-lock me-2"></i>Update password</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card p-4">
      <div class="d-flex align-items-center gap-3 mb-3">
        <span class="avatar" style="width:52px;height:52px;font-size:1.1rem"><?= e(initials(\App\Core\Auth::name())) ?></span>
        <div>
          <div class="fw-semibold"><?= e(\App\Core\Auth::name()) ?></div>
          <div class="text-muted small"><?= e(implode(', ', array_map('ucfirst', \App\Core\Auth::roles()))) ?></div>
        </div>
      </div>
      <dl class="row small mb-0">
        <dt class="col-5 text-muted fw-normal">Member since</dt>
        <dd class="col-7"><?= e(d($user['created_at'])) ?></dd>
        <dt class="col-5 text-muted fw-normal">Last sign-in</dt>
        <dd class="col-7"><?= e(dt($user['last_login_at'])) ?></dd>
        <dt class="col-5 text-muted fw-normal">Email verified</dt>
        <dd class="col-7"><?= $user['email_verified_at'] ? e(d($user['email_verified_at'])) : 'Not verified' ?></dd>
      </dl>
    </div>

    <div class="card mt-4 table-card">
      <div class="p-3 border-bottom">
        <h2 class="h6 fw-semibold mb-0">Recent sign-in activity</h2>
        <p class="text-muted fs-tiny mb-0">Contact an administrator if you see something you do not recognise.</p>
      </div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead>
            <tr><th scope="col">When</th><th scope="col">IP address</th><th scope="col">Result</th></tr>
          </thead>
          <tbody>
            <?php if ($loginHistory === []): ?>
              <tr><td colspan="3" class="text-muted small">No sign-in history recorded.</td></tr>
            <?php else: foreach ($loginHistory as $row): ?>
              <tr>
                <td class="small"><?= e(dt($row['created_at'])) ?></td>
                <td class="small mono"><?= e($row['ip_address']) ?></td>
                <td>
                  <span class="badge badge-soft bg-<?= (int)$row['successful'] === 1 ? 'success' : 'danger' ?>">
                    <?= (int)$row['successful'] === 1 ? 'Success' : 'Failed' ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
