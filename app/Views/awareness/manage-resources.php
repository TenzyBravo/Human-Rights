<?php /** @var array $resources @var array $types @var array $errors @var array $old */ ?>
<div class="mb-4">
  <h1 class="h4 fw-semibold mb-1">Educational resources</h1>
  <p class="text-muted small mb-0">Documents, videos, training material and links to source texts.</p>
</div>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card p-4">
      <h2 class="h6 fw-semibold mb-3">Add a resource</h2>
      <form method="post" action="<?= e(url('awareness/resources/save')) ?>"
            enctype="multipart/form-data" novalidate>
        <?= csrf() ?>
        <div class="mb-3">
          <label class="form-label required" for="title">Title</label>
          <input type="text" id="title" name="title" required minlength="5" maxlength="200"
                 class="form-control<?= invalid($errors, 'title') ?>" value="<?= old($old, 'title') ?>">
          <?= errorFor($errors, 'title') ?>
        </div>
        <div class="mb-3">
          <label class="form-label required" for="resource_type">Type</label>
          <select id="resource_type" name="resource_type" required class="form-select">
            <?php foreach ($types as $type): ?>
              <option value="<?= e($type) ?>"><?= e(statusLabel($type)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label" for="description">Description</label>
          <textarea id="description" name="description" rows="4" maxlength="5000"
                    class="form-control"><?= old($old, 'description') ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label" for="external_url">External URL</label>
          <input type="url" id="external_url" name="external_url" maxlength="500"
                 class="form-control<?= invalid($errors, 'external_url') ?>"
                 value="<?= old($old, 'external_url') ?>" placeholder="https://…">
          <?= errorFor($errors, 'external_url') ?>
        </div>
        <div class="mb-3">
          <label class="form-label" for="file">Attach a file</label>
          <input type="file" id="file" name="file" class="form-control form-control-sm">
          <div class="form-text">PDF, Word, image, audio or video.</div>
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" checked>
          <label class="form-check-label small" for="is_published">Publish immediately</label>
        </div>
        <button class="btn btn-primary w-100"><i class="bi bi-plus-lg me-2"></i>Add resource</button>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card table-card">
      <?php if ($resources === []): ?>
        <div class="empty-state"><i class="bi bi-journal"></i>No resources yet.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th scope="col">Resource</th><th scope="col">Type</th><th scope="col">Status</th>
                <th scope="col" class="text-end">Opened</th><th scope="col"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($resources as $resource): ?>
                <tr>
                  <td class="small">
                    <span class="d-block fw-semibold"><?= e(excerpt($resource['title'], 48)) ?></span>
                    <span class="d-block text-muted fs-tiny"><?= e(excerpt($resource['description'], 66)) ?></span>
                  </td>
                  <td><span class="badge badge-soft bg-secondary"><?= e(statusLabel((string)$resource['resource_type'])) ?></span></td>
                  <td>
                    <span class="badge badge-soft bg-<?= (int)$resource['is_published'] === 1 ? 'success' : 'warning' ?>">
                      <?= (int)$resource['is_published'] === 1 ? 'Published' : 'Draft' ?>
                    </span>
                  </td>
                  <td class="text-end small"><?= e(number_format((int)$resource['downloads'])) ?></td>
                  <td class="text-end text-nowrap">
                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse"
                            data-bs-target="#editRes<?= e((string)$resource['id']) ?>" aria-label="Edit">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <form method="post" action="<?= e(url('awareness/resources/delete/' . $resource['id'])) ?>" class="d-inline">
                      <?= csrf() ?>
                      <button class="btn btn-outline-danger btn-sm" aria-label="Delete"
                              data-confirm="Delete this resource?"><i class="bi bi-trash"></i></button>
                    </form>
                  </td>
                </tr>
                <tr class="collapse" id="editRes<?= e((string)$resource['id']) ?>">
                  <td colspan="5" class="bg-body-tertiary">
                    <form method="post" action="<?= e(url('awareness/resources/save')) ?>"
                          enctype="multipart/form-data" class="row g-2 align-items-end">
                      <?= csrf() ?>
                      <input type="hidden" name="id" value="<?= e((string)$resource['id']) ?>">
                      <div class="col-md-4">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" required maxlength="200"
                               class="form-control form-control-sm" value="<?= e($resource['title']) ?>">
                      </div>
                      <div class="col-md-2">
                        <label class="form-label">Type</label>
                        <select name="resource_type" class="form-select form-select-sm">
                          <?php foreach ($types as $type): ?>
                            <option value="<?= e($type) ?>"<?= $resource['resource_type'] === $type ? ' selected' : '' ?>>
                              <?= e(statusLabel($type)) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-3">
                        <label class="form-label">External URL</label>
                        <input type="url" name="external_url" maxlength="500"
                               class="form-control form-control-sm" value="<?= e($resource['external_url']) ?>">
                      </div>
                      <div class="col-md-2">
                        <label class="form-label">Replace file</label>
                        <input type="file" name="file" class="form-control form-control-sm">
                      </div>
                      <div class="col-md-1 d-flex flex-column gap-1">
                        <div class="form-check mb-0">
                          <input class="form-check-input" type="checkbox" name="is_published" value="1"
                                 id="pub<?= e((string)$resource['id']) ?>"
                                 <?= (int)$resource['is_published'] === 1 ? 'checked' : '' ?>>
                          <label class="form-check-label fs-tiny" for="pub<?= e((string)$resource['id']) ?>">Live</label>
                        </div>
                        <button class="btn btn-primary btn-sm">Save</button>
                      </div>
                      <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="2" maxlength="5000"
                                  class="form-control form-control-sm"><?= e($resource['description']) ?></textarea>
                      </div>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
