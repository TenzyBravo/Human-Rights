<?php /** @var array $notifications */ ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h4 fw-semibold mb-1">Notifications</h1>
    <p class="text-muted small mb-0">Updates on your reports, cases and assignments.</p>
  </div>
  <form method="post" action="<?= e(url('notifications/read')) ?>">
    <?= csrf() ?>
    <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-check2-all me-1"></i>Mark all read</button>
  </form>
</div>

<div class="card table-card">
  <?php if ($notifications === []): ?>
    <div class="empty-state"><i class="bi bi-bell-slash"></i>You have no notifications yet.</div>
  <?php else: ?>
    <div class="list-group list-group-flush">
      <?php foreach ($notifications as $n): ?>
        <div class="list-group-item d-flex gap-3 align-items-start<?= $n['read_at'] === null ? ' bg-body-tertiary' : '' ?>">
          <div class="stat-icon <?= $n['read_at'] === null ? 'tint-primary' : 'tint-dark' ?> flex-shrink-0">
            <i class="bi bi-bell"></i>
          </div>
          <div class="flex-grow-1">
            <div class="d-flex flex-wrap justify-content-between gap-2">
              <span class="fw-semibold small"><?= e($n['title']) ?></span>
              <span class="text-muted fs-tiny"><?= e(dt($n['created_at'])) ?></span>
            </div>
            <p class="text-muted small mb-1"><?= e($n['body']) ?></p>
            <?php if ($n['link']): ?>
              <a class="small fw-semibold" href="<?= e(url($n['link'])) ?>">Open <i class="bi bi-arrow-right"></i></a>
            <?php endif; ?>
          </div>
          <?php if ($n['read_at'] === null): ?>
            <form method="post" action="<?= e(url('notifications/read')) ?>">
              <?= csrf() ?>
              <input type="hidden" name="id" value="<?= e((string)$n['id']) ?>">
              <button class="btn btn-icon btn-sm" title="Mark as read" aria-label="Mark as read">
                <i class="bi bi-check2"></i>
              </button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
