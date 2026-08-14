<?php /** @var array $rows @var array $page @var array $filters @var array $actions */ ?>
<div class="mb-4">
  <h1 class="h4 fw-semibold mb-1">Audit logs</h1>
  <p class="text-muted small mb-0">
    An append-only record of every security-relevant action. Entries cannot be edited or
    deleted from the interface, and sensitive values are redacted before they are written.
  </p>
</div>

<form class="card p-3 mb-3" method="get" action="<?= e(url('admin/audit')) ?>">
  <div class="row g-2 align-items-end">
    <div class="col-lg-4">
      <label class="form-label" for="q">Search</label>
      <input type="search" id="q" name="q" class="form-control form-control-sm"
             placeholder="Description, user email or entity" value="<?= e($filters['q'] ?? '') ?>">
    </div>
    <div class="col-6 col-lg-3">
      <label class="form-label" for="action">Action</label>
      <select id="action" name="action" class="form-select form-select-sm" data-autosubmit>
        <option value="">Any action</option>
        <?php foreach ($actions as $action): ?>
          <option value="<?= e($action) ?>"<?= ($filters['action'] ?? '') === $action ? ' selected' : '' ?>>
            <?= e($action) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-lg-2">
      <label class="form-label" for="from">From</label>
      <input type="date" id="from" name="from" class="form-control form-control-sm" value="<?= e($filters['from'] ?? '') ?>">
    </div>
    <div class="col-6 col-lg-2">
      <label class="form-label" for="to">To</label>
      <input type="date" id="to" name="to" class="form-control form-control-sm" value="<?= e($filters['to'] ?? '') ?>">
    </div>
    <div class="col-6 col-lg-1 d-flex gap-1">
      <button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i></button>
      <a href="<?= e(url('admin/audit')) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
    </div>
  </div>
</form>

<div class="card table-card">
  <?php if ($rows === []): ?>
    <div class="empty-state"><i class="bi bi-shield-lock"></i>No audit entries match these filters.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead>
          <tr>
            <th scope="col">When</th><th scope="col">Actor</th><th scope="col">Action</th>
            <th scope="col">Entity</th><th scope="col">Detail</th><th scope="col">IP</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <?php
            $danger = str_contains((string)$row['action'], 'failed')
                   || str_contains((string)$row['action'], 'denied')
                   || str_contains((string)$row['action'], 'locked');
            ?>
            <tr>
              <td class="fs-tiny text-muted text-nowrap"><?= e(dt($row['created_at'], 'd M y H:i:s')) ?></td>
              <td class="small">
                <?php if ($row['actor']): ?>
                  <span class="d-block fw-semibold"><?= e($row['actor']) ?></span>
                  <span class="d-block text-muted fs-tiny"><?= e($row['actor_email']) ?></span>
                <?php else: ?>
                  <span class="text-muted">Anonymous / system</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge badge-soft bg-<?= $danger ? 'danger' : 'secondary' ?> mono">
                  <?= e($row['action']) ?>
                </span>
              </td>
              <td class="fs-tiny text-muted">
                <?= e($row['entity_type'] ?: '—') ?><?= $row['entity_id'] ? ' #' . e((string)$row['entity_id']) : '' ?>
              </td>
              <td class="small"><?= e(excerpt($row['description'], 70)) ?></td>
              <td class="fs-tiny mono text-muted"><?= e($row['ip_address']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php \App\Core\View::partial('pagination', ['page' => $page]); ?>
