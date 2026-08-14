<?php /** @var array|null $user @var array $roles @var array $provinces @var array $errors @var array $old */
$isNew = $user === null;
$current = $isNew ? [] : $user;
?>
<div class="mb-4">
  <a href="<?= e(url('admin/users')) ?>" class="small text-muted"><i class="bi bi-arrow-left me-1"></i>All users</a>
  <h1 class="h4 fw-semibold mt-2 mb-1"><?= $isNew ? 'Add a user' : 'Edit user' ?></h1>
  <p class="text-muted small mb-0">
    <?= $isNew
        ? 'Staff accounts are created here. Citizens normally register themselves.'
        : 'Leave the password field blank to keep the existing password.' ?>
  </p>
</div>

<form method="post" action="<?= e(url('admin/users/save')) ?>" novalidate>
  <?= csrf() ?>
  <?php if (!$isNew): ?>
    <input type="hidden" name="id" value="<?= e((string)$user['id']) ?>">
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card p-4">
        <h2 class="h6 fw-semibold mb-3">Account details</h2>
        <div class="row g-3">
          <div class="col-sm-6">
            <label class="form-label required" for="first_name">First name</label>
            <input type="text" id="first_name" name="first_name" required maxlength="80"
                   class="form-control<?= invalid($errors, 'first_name') ?>"
                   value="<?= e($old['first_name'] ?? $current['first_name'] ?? '') ?>">
            <?= errorFor($errors, 'first_name') ?>
          </div>
          <div class="col-sm-6">
            <label class="form-label required" for="last_name">Last name</label>
            <input type="text" id="last_name" name="last_name" required maxlength="80"
                   class="form-control<?= invalid($errors, 'last_name') ?>"
                   value="<?= e($old['last_name'] ?? $current['last_name'] ?? '') ?>">
            <?= errorFor($errors, 'last_name') ?>
          </div>
          <div class="col-sm-6">
            <label class="form-label required" for="email">Email address</label>
            <input type="email" id="email" name="email" required maxlength="190"
                   class="form-control<?= invalid($errors, 'email') ?>"
                   value="<?= e($old['email'] ?? $current['email'] ?? '') ?>">
            <?= errorFor($errors, 'email') ?>
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="phone">Phone number</label>
            <input type="tel" id="phone" name="phone" maxlength="30"
                   class="form-control<?= invalid($errors, 'phone') ?>"
                   value="<?= e($old['phone'] ?? $current['phone'] ?? '') ?>">
            <?= errorFor($errors, 'phone') ?>
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="province_id">Province</label>
            <select id="province_id" name="province_id" class="form-select">
              <option value="">Not specified</option>
              <?php foreach ($provinces as $province): ?>
                <option value="<?= e((string)$province['id']) ?>"
                  <?= (int)($current['province_id'] ?? 0) === (int)$province['id'] ? ' selected' : '' ?>>
                  <?= e($province['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-6">
            <label class="form-label required" for="status">Account status</label>
            <select id="status" name="status" required class="form-select">
              <?php foreach (['active' => 'Active', 'pending' => 'Pending verification',
                              'suspended' => 'Suspended', 'locked' => 'Locked'] as $value => $label): ?>
                <option value="<?= e($value) ?>"
                  <?= ($current['status'] ?? 'active') === $value ? ' selected' : '' ?>>
                  <?= e($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label<?= $isNew ? ' required' : '' ?>" for="password">
              <?= $isNew ? 'Initial password' : 'New password' ?>
            </label>
            <div class="input-group">
              <input type="password" id="password" name="password" <?= $isNew ? 'required' : '' ?>
                     autocomplete="new-password" data-pw-meter="pwMeter"
                     class="form-control<?= invalid($errors, 'password') ?>"
                     placeholder="<?= $isNew ? '' : 'Leave blank to keep the current password' ?>">
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
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card p-4">
        <h2 class="h6 fw-semibold mb-1">Roles</h2>
        <p class="text-muted fs-tiny mb-3">
          A user may hold more than one role; permissions are the union of all of them.
        </p>
        <?php $assigned = $current['roles'] ?? ['citizen']; ?>
        <?php foreach ($roles as $role): ?>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="roles[]"
                   id="role_<?= e($role['slug']) ?>" value="<?= e($role['slug']) ?>"
                   <?= in_array($role['slug'], $assigned, true) ? 'checked' : '' ?>>
            <label class="form-check-label small" for="role_<?= e($role['slug']) ?>">
              <span class="fw-semibold d-block"><?= e($role['name']) ?></span>
            </label>
          </div>
        <?php endforeach; ?>
        <a href="<?= e(url('admin/roles')) ?>" class="btn btn-link btn-sm px-0 mt-2">
          Review the permission matrix <i class="bi bi-arrow-right"></i>
        </a>
      </div>

      <div class="card p-4 mt-4">
        <button class="btn btn-primary w-100">
          <i class="bi bi-check2 me-2"></i><?= $isNew ? 'Create user' : 'Save changes' ?>
        </button>
        <a href="<?= e(url('admin/users')) ?>" class="btn btn-link btn-sm w-100 mt-2 text-muted">Cancel</a>
      </div>

      <?php if (!$isNew): ?>
        <div class="card p-4 mt-4">
          <h2 class="h6 fw-semibold mb-2">Account history</h2>
          <dl class="row small mb-0">
            <dt class="col-6 text-muted fw-normal">Created</dt>
            <dd class="col-6"><?= e(d($user['created_at'])) ?></dd>
            <dt class="col-6 text-muted fw-normal">Last sign-in</dt>
            <dd class="col-6"><?= e(dt($user['last_login_at'], 'd M Y')) ?></dd>
            <dt class="col-6 text-muted fw-normal">Email verified</dt>
            <dd class="col-6"><?= $user['email_verified_at'] ? 'Yes' : 'No' ?></dd>
            <dt class="col-6 text-muted fw-normal">Failed attempts</dt>
            <dd class="col-6"><?= e((string)$user['failed_attempts']) ?></dd>
          </dl>
        </div>
      <?php endif; ?>
    </div>
  </div>
</form>
