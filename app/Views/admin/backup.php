<?php /** @var array $files @var string|null $mysqldump */ ?>
<div class="mb-4">
  <h1 class="h4 fw-semibold mb-1">Backup &amp; restore</h1>
  <p class="text-muted small mb-0">
    Database snapshots are written to <span class="mono">storage/backups</span> with file mode
    0600 and are never reachable from the web root.
  </p>
</div>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card p-4">
      <h2 class="h6 fw-semibold mb-3">Create a backup</h2>
      <?php if ($mysqldump === null): ?>
        <div class="alert alert-warning small">
          <i class="bi bi-exclamation-triangle me-1"></i>
          <strong>mysqldump was not found.</strong> Add its path to the candidate list in
          <span class="mono">AdminController::mysqldumpPath()</span>, or take backups from
          the command line instead.
        </div>
      <?php else: ?>
        <p class="text-muted small">
          A single-transaction dump including routines and events. This does not lock the
          tables, so it is safe to run while the system is in use.
        </p>
        <p class="fs-tiny text-muted mono mb-3"><?= e($mysqldump) ?></p>
        <form method="post" action="<?= e(url('admin/backup/run')) ?>">
          <?= csrf() ?>
          <button class="btn btn-primary w-100" data-confirm="Create a database backup now?">
            <i class="bi bi-database-down me-2"></i>Back up now
          </button>
        </form>
      <?php endif; ?>
    </div>

    <div class="card p-4 mt-4">
      <h2 class="h6 fw-semibold mb-2">Restoring</h2>
      <p class="text-muted small mb-2">
        Restoring overwrites live data, so it is deliberately not exposed through the web
        interface. Run it from a shell with the database offline to users:
      </p>
      <pre class="bg-body-tertiary p-2 rounded fs-tiny mb-2"><code>mysql -u root -p hrrams \
  &lt; storage/backups/hrrams-YYYYMMDD-HHMMSS.sql</code></pre>
      <p class="text-muted fs-tiny mb-0">
        Take a fresh backup first, and verify the file size and date before restoring.
      </p>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card table-card">
      <div class="p-3 border-bottom">
        <h2 class="h6 fw-semibold mb-0">Available backups</h2>
        <p class="text-muted fs-tiny mb-0">
          Store copies off-server. A backup that lives only on the machine it protects is not a backup.
        </p>
      </div>
      <?php if ($files === []): ?>
        <div class="empty-state"><i class="bi bi-database"></i>No backups have been created yet.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th scope="col">File</th><th scope="col">Size</th>
                <th scope="col">Created</th><th scope="col"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($files as $file): ?>
                <tr>
                  <td class="mono small"><?= e($file['name']) ?></td>
                  <td class="small"><?= e(bytes($file['size'])) ?></td>
                  <td class="small text-muted"><?= e(dt($file['modified'])) ?></td>
                  <td class="text-end">
                    <a class="btn btn-outline-secondary btn-sm"
                       href="<?= e(url('admin/backup/download/' . $file['name'])) ?>">
                      <i class="bi bi-download me-1"></i>Download
                    </a>
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
