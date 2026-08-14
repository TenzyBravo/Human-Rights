<?php /** @var array $categories @var array $errors @var array $old */ ?>
<div class="mb-4">
  <h1 class="h4 fw-semibold mb-1">Report categories</h1>
  <p class="text-muted small mb-0">
    These appear in the reporting form. A category already used by reports is deactivated
    rather than deleted, so historical data stays intact.
  </p>
</div>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card p-4">
      <h2 class="h6 fw-semibold mb-3">Add a category</h2>
      <form method="post" action="<?= e(url('admin/categories/save')) ?>" novalidate>
        <?= csrf() ?>
        <div class="mb-3">
          <label class="form-label required" for="name">Name</label>
          <input type="text" id="name" name="name" required maxlength="120"
                 class="form-control<?= invalid($errors, 'name') ?>" value="<?= old($old, 'name') ?>">
          <?= errorFor($errors, 'name') ?>
        </div>
        <div class="mb-3">
          <label class="form-label" for="description">Description</label>
          <textarea id="description" name="description" rows="3" maxlength="500"
                    class="form-control"><?= old($old, 'description') ?></textarea>
          <div class="form-text">Shown as guidance to the person reporting.</div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="icon">Bootstrap icon class</label>
          <input type="text" id="icon" name="icon" maxlength="60" class="form-control"
                 value="<?= old($old, 'icon', 'bi-flag') ?>" placeholder="bi-flag">
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
          <label class="form-check-label small" for="is_active">Available for new reports</label>
        </div>
        <button class="btn btn-primary w-100"><i class="bi bi-plus-lg me-2"></i>Add category</button>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card table-card">
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th scope="col">Category</th><th scope="col" class="text-end">Reports</th>
              <th scope="col">Status</th><th scope="col"></th>
            </tr>
          </thead>
          <tbody>
            <?php if ($categories === []): ?>
              <tr><td colspan="4" class="text-muted small">No categories defined.</td></tr>
            <?php else: foreach ($categories as $category): ?>
              <tr>
                <td>
                  <span class="d-inline-flex align-items-center gap-2">
                    <i class="bi <?= e($category['icon'] ?: 'bi-flag') ?> text-primary"></i>
                    <span class="small">
                      <span class="d-block fw-semibold"><?= e($category['name']) ?></span>
                      <span class="d-block text-muted fs-tiny"><?= e(excerpt($category['description'], 70)) ?></span>
                    </span>
                  </span>
                </td>
                <td class="text-end small"><?= e(number_format((int)$category['report_count'])) ?></td>
                <td>
                  <span class="badge badge-soft bg-<?= (int)$category['is_active'] === 1 ? 'success' : 'secondary' ?>">
                    <?= (int)$category['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                  </span>
                </td>
                <td class="text-end">
                  <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse"
                          data-bs-target="#editCat<?= e((string)$category['id']) ?>" aria-label="Edit">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <form method="post" action="<?= e(url('admin/categories/delete/' . $category['id'])) ?>" class="d-inline">
                    <?= csrf() ?>
                    <button class="btn btn-outline-danger btn-sm"
                            data-confirm="Remove this category? If it is in use it will be deactivated instead."
                            aria-label="Delete">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
              <tr class="collapse" id="editCat<?= e((string)$category['id']) ?>">
                <td colspan="4" class="bg-body-tertiary">
                  <form method="post" action="<?= e(url('admin/categories/save')) ?>" class="row g-2 align-items-end">
                    <?= csrf() ?>
                    <input type="hidden" name="id" value="<?= e((string)$category['id']) ?>">
                    <div class="col-md-4">
                      <label class="form-label">Name</label>
                      <input type="text" name="name" required maxlength="120"
                             class="form-control form-control-sm" value="<?= e($category['name']) ?>">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Description</label>
                      <input type="text" name="description" maxlength="500"
                             class="form-control form-control-sm" value="<?= e($category['description']) ?>">
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Icon</label>
                      <input type="text" name="icon" maxlength="60"
                             class="form-control form-control-sm" value="<?= e($category['icon']) ?>">
                    </div>
                    <div class="col-md-2 d-flex gap-2 align-items-center">
                      <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               id="active<?= e((string)$category['id']) ?>"
                               <?= (int)$category['is_active'] === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="active<?= e((string)$category['id']) ?>">Active</label>
                      </div>
                      <button class="btn btn-primary btn-sm">Save</button>
                    </div>
                  </form>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
