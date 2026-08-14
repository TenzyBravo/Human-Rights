<?php /** @var array $rows @var array $page @var array $filters */
use App\Models\Campaign;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h4 fw-semibold mb-1">Awareness content</h1>
    <p class="text-muted small mb-0">Campaigns, news, articles, success stories and newsletters.</p>
  </div>
  <a href="<?= e(url('awareness/create')) ?>" class="btn btn-primary">
    <i class="bi bi-plus-lg me-2"></i>New content
  </a>
</div>

<form class="card p-3 mb-3" method="get" action="<?= e(url('awareness/manage')) ?>">
  <div class="row g-2 align-items-end">
    <div class="col-lg-5">
      <label class="form-label" for="q">Search</label>
      <input type="search" id="q" name="q" class="form-control form-control-sm"
             placeholder="Title or summary" value="<?= e($filters['q'] ?? '') ?>">
    </div>
    <div class="col-6 col-lg-3">
      <label class="form-label" for="type">Type</label>
      <select id="type" name="type" class="form-select form-select-sm" data-autosubmit>
        <option value="">Any type</option>
        <?php foreach (Campaign::TYPES as $type): ?>
          <option value="<?= e($type) ?>"<?= ($filters['type'] ?? '') === $type ? ' selected' : '' ?>>
            <?= e(statusLabel($type)) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-lg-3">
      <label class="form-label" for="status">Status</label>
      <select id="status" name="status" class="form-select form-select-sm" data-autosubmit>
        <option value="">Any status</option>
        <?php foreach (['draft', 'published', 'archived'] as $status): ?>
          <option value="<?= e($status) ?>"<?= ($filters['status'] ?? '') === $status ? ' selected' : '' ?>>
            <?= e(ucfirst($status)) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-lg-1">
      <button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i></button>
    </div>
  </div>
</form>

<div class="card table-card">
  <?php if ($rows === []): ?>
    <div class="empty-state">
      <i class="bi bi-megaphone"></i>
      <p class="fw-semibold mb-1">No content yet</p>
      <a href="<?= e(url('awareness/create')) ?>" class="btn btn-primary btn-sm">Create the first item</a>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead>
          <tr>
            <th scope="col">Title</th><th scope="col">Type</th><th scope="col">Status</th>
            <th scope="col" class="text-end">Views</th><th scope="col">Author</th>
            <th scope="col">Updated</th><th scope="col"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td class="small">
                <span class="d-block fw-semibold"><?= e(excerpt($row['title'], 52)) ?></span>
                <span class="d-block text-muted fs-tiny mono"><?= e($row['slug']) ?></span>
              </td>
              <td><span class="badge badge-soft bg-secondary"><?= e(statusLabel((string)$row['type'])) ?></span></td>
              <td>
                <span class="badge badge-soft bg-<?= e(match ($row['status']) {
                    'published' => 'success', 'draft' => 'warning', default => 'secondary' }) ?>">
                  <?= e(ucfirst((string)$row['status'])) ?>
                </span>
              </td>
              <td class="text-end small"><?= e(number_format((int)$row['views'])) ?></td>
              <td class="small text-muted"><?= e($row['author'] ?: '—') ?></td>
              <td class="small text-muted"><?= e(d($row['updated_at'] ?: $row['created_at'])) ?></td>
              <td class="text-end text-nowrap">
                <?php if ($row['status'] === 'published'): ?>
                  <a class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener"
                     href="<?= e(url('campaigns/' . $row['slug'])) ?>" aria-label="Preview">
                    <i class="bi bi-eye"></i>
                  </a>
                <?php endif; ?>
                <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('awareness/edit/' . $row['id'])) ?>"
                   aria-label="Edit"><i class="bi bi-pencil"></i></a>
                <form method="post" action="<?= e(url('awareness/delete/' . $row['id'])) ?>" class="d-inline">
                  <?= csrf() ?>
                  <button class="btn btn-outline-danger btn-sm" aria-label="Delete"
                          data-confirm="Delete this item? It will be removed from the public site.">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php \App\Core\View::partial('pagination', ['page' => $page]); ?>
