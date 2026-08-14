<?php /** @var array $roles @var array $permissions @var array $matrix */
$byModule = [];
foreach ($permissions as $permission) {
    $byModule[$permission['module']][] = $permission;
}
?>
<div class="mb-4">
  <h1 class="h4 fw-semibold mb-1">Roles &amp; permissions</h1>
  <p class="text-muted small mb-0">
    Tick the capabilities each role should have, then save that role. The administrator role
    always retains every permission and cannot be edited.
  </p>
</div>

<div class="row g-4">
  <?php foreach ($roles as $role): ?>
    <?php $isAdmin = $role['slug'] === 'admin'; ?>
    <div class="col-lg-6">
      <div class="card h-100">
        <form method="post" action="<?= e(url('admin/roles/save')) ?>">
          <?= csrf() ?>
          <input type="hidden" name="role_id" value="<?= e((string)$role['id']) ?>">

          <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
              <h2 class="h6 fw-semibold mb-0"><?= e($role['name']) ?></h2>
              <span class="text-muted fs-tiny mono"><?= e($role['slug']) ?></span>
            </div>
            <?php if ($isAdmin): ?>
              <span class="badge badge-soft bg-dark">Full access</span>
            <?php else: ?>
              <button class="btn btn-primary btn-sm"><i class="bi bi-check2 me-1"></i>Save</button>
            <?php endif; ?>
          </div>

          <div class="p-3" style="max-height:340px;overflow-y:auto">
            <?php foreach ($byModule as $module => $items): ?>
              <div class="nav-group-label px-0 pt-2"><?= e(statusLabel((string)$module)) ?></div>
              <?php foreach ($items as $permission): ?>
                <?php $checked = $isAdmin || isset($matrix[(int)$role['id']][(int)$permission['id']]); ?>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="permissions[]"
                         id="p<?= e((string)$role['id']) ?>_<?= e((string)$permission['id']) ?>"
                         value="<?= e((string)$permission['id']) ?>"
                         <?= $checked ? 'checked' : '' ?> <?= $isAdmin ? 'disabled' : '' ?>>
                  <label class="form-check-label small"
                         for="p<?= e((string)$role['id']) ?>_<?= e((string)$permission['id']) ?>">
                    <?= e($permission['name']) ?>
                    <span class="text-muted fs-tiny d-block mono"><?= e($permission['slug']) ?></span>
                  </label>
                </div>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </div>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card p-4 mt-4">
  <h2 class="h6 fw-semibold mb-2">How authorisation is enforced</h2>
  <p class="text-muted small mb-0">
    Every route declares the permission it needs. Before a controller runs, the middleware
    resolves the signed-in user's roles, unions their permissions, and rejects the request with
    a 403 and an audit entry if the required permission is absent. Menu items and buttons are
    hidden the same way, so the interface only ever offers actions the user can actually perform.
  </p>
</div>
