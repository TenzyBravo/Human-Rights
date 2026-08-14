<?php /** @var array $events @var array $provinces @var array $errors @var array $old */ ?>
<div class="mb-4">
  <h1 class="h4 fw-semibold mb-1">Community events</h1>
  <p class="text-muted small mb-0">Published events appear on the public events page.</p>
</div>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card p-4">
      <h2 class="h6 fw-semibold mb-3">Add an event</h2>
      <form method="post" action="<?= e(url('awareness/events/save')) ?>" novalidate>
        <?= csrf() ?>
        <div class="mb-3">
          <label class="form-label required" for="title">Title</label>
          <input type="text" id="title" name="title" required minlength="5" maxlength="200"
                 class="form-control<?= invalid($errors, 'title') ?>" value="<?= old($old, 'title') ?>">
          <?= errorFor($errors, 'title') ?>
        </div>
        <div class="mb-3">
          <label class="form-label" for="description">Description</label>
          <textarea id="description" name="description" rows="3" maxlength="5000"
                    class="form-control"><?= old($old, 'description') ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label" for="venue">Venue</label>
          <input type="text" id="venue" name="venue" maxlength="200" class="form-control"
                 value="<?= old($old, 'venue') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label" for="province_id">Province</label>
          <select id="province_id" name="province_id" class="form-select">
            <option value="">Not specified</option>
            <?php foreach ($provinces as $province): ?>
              <option value="<?= e((string)$province['id']) ?>"><?= e($province['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-12">
            <label class="form-label required" for="starts_at">Starts</label>
            <input type="datetime-local" id="starts_at" name="starts_at" required
                   class="form-control form-control-sm">
          </div>
          <div class="col-12">
            <label class="form-label" for="ends_at">Ends</label>
            <input type="datetime-local" id="ends_at" name="ends_at" class="form-control form-control-sm">
          </div>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label" for="capacity">Capacity</label>
            <input type="number" id="capacity" name="capacity" min="0" max="100000"
                   class="form-control form-control-sm">
          </div>
          <div class="col-6">
            <label class="form-label required" for="status">Status</label>
            <select id="status" name="status" required class="form-select form-select-sm">
              <option value="published">Published</option>
              <option value="draft">Draft</option>
              <option value="cancelled">Cancelled</option>
              <option value="completed">Completed</option>
            </select>
          </div>
        </div>
        <button class="btn btn-primary w-100"><i class="bi bi-calendar-plus me-2"></i>Add event</button>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card table-card">
      <?php if ($events === []): ?>
        <div class="empty-state"><i class="bi bi-calendar-x"></i>No events yet.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th scope="col">Event</th><th scope="col">When</th>
                <th scope="col">Where</th><th scope="col">Status</th><th scope="col"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($events as $event): ?>
                <tr>
                  <td class="small">
                    <span class="d-block fw-semibold"><?= e(excerpt($event['title'], 44)) ?></span>
                    <span class="d-block text-muted fs-tiny"><?= e(excerpt($event['description'], 60)) ?></span>
                  </td>
                  <td class="small text-nowrap"><?= e(dt($event['starts_at'])) ?></td>
                  <td class="small">
                    <?= e($event['venue'] ?: '—') ?>
                    <?php if ($event['province_name']): ?>
                      <span class="d-block text-muted fs-tiny"><?= e($event['province_name']) ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge badge-soft bg-<?= e(match ($event['status']) {
                        'published' => 'success', 'draft' => 'warning',
                        'cancelled' => 'danger', default => 'secondary' }) ?>">
                      <?= e(ucfirst((string)$event['status'])) ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse"
                            data-bs-target="#editEv<?= e((string)$event['id']) ?>" aria-label="Edit">
                      <i class="bi bi-pencil"></i>
                    </button>
                  </td>
                </tr>
                <tr class="collapse" id="editEv<?= e((string)$event['id']) ?>">
                  <td colspan="5" class="bg-body-tertiary">
                    <form method="post" action="<?= e(url('awareness/events/save')) ?>" class="row g-2 align-items-end">
                      <?= csrf() ?>
                      <input type="hidden" name="id" value="<?= e((string)$event['id']) ?>">
                      <div class="col-md-4">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" required maxlength="200"
                               class="form-control form-control-sm" value="<?= e($event['title']) ?>">
                      </div>
                      <div class="col-md-3">
                        <label class="form-label">Venue</label>
                        <input type="text" name="venue" maxlength="200"
                               class="form-control form-control-sm" value="<?= e($event['venue']) ?>">
                      </div>
                      <div class="col-md-3">
                        <label class="form-label">Starts</label>
                        <input type="datetime-local" name="starts_at" required class="form-control form-control-sm"
                               value="<?= e(date('Y-m-d\TH:i', strtotime((string)$event['starts_at']))) ?>">
                      </div>
                      <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select form-select-sm">
                          <?php foreach (['published', 'draft', 'cancelled', 'completed'] as $status): ?>
                            <option value="<?= e($status) ?>"<?= $event['status'] === $status ? ' selected' : '' ?>>
                              <?= e(ucfirst($status)) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-3">
                        <label class="form-label">Province</label>
                        <select name="province_id" class="form-select form-select-sm">
                          <option value="">Not specified</option>
                          <?php foreach ($provinces as $province): ?>
                            <option value="<?= e((string)$province['id']) ?>"
                              <?= (int)$event['province_id'] === (int)$province['id'] ? ' selected' : '' ?>>
                              <?= e($province['name']) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-3">
                        <label class="form-label">Ends</label>
                        <input type="datetime-local" name="ends_at" class="form-control form-control-sm"
                               value="<?= $event['ends_at'] ? e(date('Y-m-d\TH:i', strtotime((string)$event['ends_at']))) : '' ?>">
                      </div>
                      <div class="col-md-2">
                        <label class="form-label">Capacity</label>
                        <input type="number" name="capacity" min="0" class="form-control form-control-sm"
                               value="<?= e((string)$event['capacity']) ?>">
                      </div>
                      <div class="col-md-4 text-end">
                        <button class="btn btn-primary btn-sm">Save changes</button>
                      </div>
                      <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="2" maxlength="5000"
                                  class="form-control form-control-sm"><?= e($event['description']) ?></textarea>
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
