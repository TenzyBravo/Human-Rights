<?php /** @var array $rows @var array $page @var array $filters @var array $roles */ ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h4 fw-semibold mb-1">User management</h1>
    <p class="text-muted small mb-0">Create accounts, assign roles and control access.</p>
  </div>
  <a href="<?= e(url('admin/users/create')) ?>" class="btn btn-primary">
    <i class="bi bi-person-plus me-2"></i>Add user
  </a>
</div>

<form class="card p-3 mb-3" method="get" action="<?= e(url('admin/users')) ?>">
  <div class="row g-2 align-items-end">
    <div class="col-lg-5">
      <label class="form-label" for="q">Search</label>
      <input type="search" id="q" name="q" class="form-control form-control-sm"
             placeholder="Name, email or phone" value="<?= e($filters['q'] ?? '') ?>">
    </div>
    <div class="col-6 col-lg-3">
      <label class="form-label" for="role">Role</label>
      <select id="role" name="role" class="form-select form-select-sm" data-autosubmit>
        <option value="">Any role</option>
        <?php foreach ($roles as $role): ?>
          <option value="<?= e($role['slug']) ?>"<?= ($filters['role'] ?? '') === $role['slug'] ? ' selected' : '' ?>>
            <?= e($role['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-lg-3">
      <label class="form-label" for="status">Status</label>
      <select id="status" name="status" class="form-select form-select-sm" data-autosubmit>
        <option value="">Any status</option>
        <?php foreach (['active', 'pending', 'suspended', 'locked'] as $status): ?>
          <option value="<?= e($status) ?>"<?= ($filters['status'] ?? '') === $status ? ' selected' : '' ?>>
            <?= e(ucfirst($status)) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-lg-1 d-flex gap-1">
      <button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i></button>
      <a href="<?= e(url('admin/users')) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
    </div>
  </div>
</form>

<div class="card table-card">
  <?php if ($rows === []): ?>
    <div class="empty-state"><i class="bi bi-people"></i>No users match these filters.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead>
          <tr>
            <th scope="col">User</th><th scope="col">Roles</th><th scope="col">Status</th>
            <th scope="col">Last sign-in</th><th scope="col">Created</th><th scope="col"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td>
                <span class="d-inline-flex align-items-center gap-2">
                  <span class="avatar avatar-sm"><?= e(initials($row['first_name'] . ' ' . $row['last_name'])) ?></span>
                  <span class="small">
                    <span class="d-block fw-semibold"><?= e($row['first_name'] . ' ' . $row['last_name']) ?></span>
                    <span class="d-block text-muted fs-tiny"><?= e($row['email']) ?></span>
                  </span>
                </span>
              </td>
              <td class="small"><?= e($row['role_names'] ?: '—') ?></td>
              <td>
                <span class="badge badge-soft bg-<?= e(match ($row['status']) {
                    'active' => 'success', 'pending' => 'warning',
                    'suspended' => 'danger', default => 'secondary' }) ?>">
                  <?= e(ucfirst((string)$row['status'])) ?>
                </span>
                <?php if ($row['locked_until'] !== null && strtotime((string)$row['locked_until']) > time()): ?>
                  <span class="badge badge-soft bg-danger" title="Temporarily locked after failed sign-ins">
                    <i class="bi bi-lock"></i>
                  </span>
                <?php endif; ?>
              </td>
              <td class="small text-muted"><?= e(dt($row['last_login_at'], 'd M Y')) ?></td>
              <td class="small text-muted"><?= e(d($row['created_at'])) ?></td>
              <td class="text-end">
                <div class="dropdown">
                  <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="dropdown" aria-label="Actions">
                    <i class="bi bi-three-dots"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li>
                      <a class="dropdown-item" href="<?= e(url('admin/users/' . $row['id'])) ?>">
                        <i class="bi bi-pencil me-2"></i>Edit
                      </a>
                    </li>
                    <?php foreach ([['active', 'Activate', 'bi-check2-circle'],
                                    ['suspended', 'Suspend', 'bi-slash-circle']] as [$status, $label, $icon]): ?>
                      <?php if ($row['status'] === $status) { continue; } ?>
                      <li>
                        <form method="post" action="<?= e(url('admin/users/' . $row['id'] . '/status')) ?>">
                          <?= csrf() ?>
                          <input type="hidden" name="status" value="<?= e($status) ?>">
                          <button class="dropdown-item">
                            <i class="bi <?= e($icon) ?> me-2"></i><?= e($label) ?>
                          </button>
                        </form>
                      </li>
                    <?php endforeach; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                      <form method="post" action="<?= e(url('admin/users/' . $row['id'] . '/delete')) ?>">
                        <?= csrf() ?>
                        <button class="dropdown-item text-danger"
                                data-confirm="Deactivate this account? Case history is preserved.">
                          <i class="bi bi-trash me-2"></i>Deactivate
                        </button>
                      </form>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php \App\Core\View::partial('pagination', ['page' => $page]); ?>
