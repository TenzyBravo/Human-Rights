<?php /** @var array|null $item @var array $types @var array $errors @var array $old */
$c = $item ?? [];
?>
<div class="mb-4">
  <a href="<?= e(url('awareness/manage')) ?>" class="small text-muted">
    <i class="bi bi-arrow-left me-1"></i>All content
  </a>
  <h1 class="h4 fw-semibold mt-2 mb-1"><?= $item === null ? 'New content' : 'Edit content' ?></h1>
  <p class="text-muted small mb-0">
    Saving as <strong>Published</strong> makes this visible on the public site immediately.
  </p>
</div>

<form method="post" action="<?= e(url('awareness/save')) ?>" enctype="multipart/form-data" novalidate>
  <?= csrf() ?>
  <?php if ($item !== null): ?>
    <input type="hidden" name="id" value="<?= e((string)$item['id']) ?>">
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card p-4">
        <div class="mb-3">
          <label class="form-label required" for="ctitle">Title</label>
          <input type="text" id="ctitle" name="title" required minlength="5" maxlength="200"
                 class="form-control<?= invalid($errors, 'title') ?>"
                 value="<?= e($old['title'] ?? $c['title'] ?? '') ?>">
          <?= errorFor($errors, 'title') ?>
        </div>
        <div class="mb-3">
          <label class="form-label" for="summary">Summary</label>
          <textarea id="summary" name="summary" rows="2" maxlength="500"
                    class="form-control"><?= e($old['summary'] ?? $c['summary'] ?? '') ?></textarea>
          <div class="form-text">Shown on listing pages and in search results. Keep it under two lines.</div>
        </div>
        <div class="mb-0">
          <label class="form-label" for="body">Body</label>
          <textarea id="body" name="body" rows="14" maxlength="200000"
                    class="form-control"><?= e($old['body'] ?? $c['body'] ?? '') ?></textarea>
          <div class="form-text">
            Plain text with blank lines between paragraphs. Basic formatting tags are permitted;
            anything else is stripped before the page is rendered.
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card p-4">
        <h2 class="h6 fw-semibold mb-3">Publication</h2>
        <div class="mb-3">
          <label class="form-label required" for="type">Type</label>
          <select id="type" name="type" required class="form-select">
            <?php foreach ($types as $type): ?>
              <option value="<?= e($type) ?>"<?= ($c['type'] ?? 'campaign') === $type ? ' selected' : '' ?>>
                <?= e(statusLabel($type)) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label required" for="status">Status</label>
          <select id="status" name="status" required class="form-select">
            <?php foreach (['draft' => 'Draft — not public', 'published' => 'Published — live',
                            'archived' => 'Archived — hidden'] as $value => $label): ?>
              <option value="<?= e($value) ?>"<?= ($c['status'] ?? 'draft') === $value ? ' selected' : '' ?>>
                <?= e($label) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label" for="starts_on">Starts</label>
            <input type="date" id="starts_on" name="starts_on" class="form-control form-control-sm"
                   value="<?= e($c['starts_on'] ?? '') ?>">
          </div>
          <div class="col-6">
            <label class="form-label" for="ends_on">Ends</label>
            <input type="date" id="ends_on" name="ends_on" class="form-control form-control-sm"
                   value="<?= e($c['ends_on'] ?? '') ?>">
          </div>
        </div>
        <div class="mb-0">
          <label class="form-label" for="cover">Cover image</label>
          <input type="file" id="cover" name="cover" class="form-control form-control-sm"
                 accept="image/jpeg,image/png,image/webp">
          <?php if (!empty($c['cover_path'])): ?>
            <div class="form-text">A cover image is already attached. Uploading replaces it.</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card p-4 mt-4">
        <button class="btn btn-primary w-100"><i class="bi bi-check2 me-2"></i>Save content</button>
        <a href="<?= e(url('awareness/manage')) ?>" class="btn btn-link btn-sm w-100 mt-2 text-muted">Cancel</a>
      </div>

      <?php if ($item !== null): ?>
        <div class="card p-4 mt-4">
          <h2 class="h6 fw-semibold mb-2">Statistics</h2>
          <dl class="row small mb-0">
            <dt class="col-6 text-muted fw-normal">Views</dt>
            <dd class="col-6"><?= e(number_format((int)$item['views'])) ?></dd>
            <dt class="col-6 text-muted fw-normal">Created</dt>
            <dd class="col-6"><?= e(d($item['created_at'])) ?></dd>
            <dt class="col-6 text-muted fw-normal">Published</dt>
            <dd class="col-6"><?= $item['published_at'] ? e(d($item['published_at'])) : '—' ?></dd>
          </dl>
        </div>
      <?php endif; ?>
    </div>
  </div>
</form>
