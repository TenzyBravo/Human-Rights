<?php
use App\Core\Auth;
use App\Models\Setting;
?>
<aside class="app-sidebar" id="appSidebar">
  <a href="<?= e(url(Auth::homeRoute())) ?>" class="sidebar-brand">
    <span class="brand-mark"><i class="bi bi-shield-check"></i></span>
    <span class="brand-text"><?= e(Setting::get('site_name', 'HRRAMS')) ?></span>
  </a>

  <nav class="sidebar-nav" aria-label="Main navigation">
    <?php if (Auth::hasRole('officer', 'supervisor', 'admin')): ?>
      <div class="nav-group-label">Overview</div>
      <a class="nav-item<?= activeIf('dashboard') ?>" href="<?= e(url('dashboard')) ?>">
        <i class="bi bi-speedometer2"></i><span>Dashboard</span>
      </a>
    <?php endif; ?>

    <?php if (can('case.view_assigned') || can('case.view_any')): ?>
      <div class="nav-group-label">Case work</div>
      <a class="nav-item<?= activeIf('cases') ?>" href="<?= e(url('cases')) ?>">
        <i class="bi bi-folder2-open"></i>
        <span><?= can('case.view_any') ? 'All cases' : 'My cases' ?></span>
      </a>
      <?php if (can('case.assign')): ?>
        <a class="nav-item" href="<?= e(url('cases?unassigned=1&status=open')) ?>">
          <i class="bi bi-inbox"></i><span>Unassigned</span>
        </a>
      <?php endif; ?>
      <a class="nav-item" href="<?= e(url('cases?overdue=1')) ?>">
        <i class="bi bi-alarm"></i><span>Overdue</span>
      </a>
    <?php endif; ?>

    <?php if (can('report.view_any')): ?>
      <a class="nav-item<?= activeIf('reports') ?>" href="<?= e(url('reports')) ?>">
        <i class="bi bi-file-earmark-text"></i><span>All reports</span>
      </a>
    <?php endif; ?>

    <?php if (can('dashboard.department')): ?>
      <a class="nav-item<?= activeIf('officers') ?>" href="<?= e(url('officers')) ?>">
        <i class="bi bi-person-badge"></i><span>Officer performance</span>
      </a>
    <?php endif; ?>

    <?php if (can('awareness.manage') || can('event.manage')): ?>
      <div class="nav-group-label">Awareness</div>
      <?php if (can('awareness.manage')): ?>
        <a class="nav-item<?= activeIf('awareness/manage') ?>" href="<?= e(url('awareness/manage')) ?>">
          <i class="bi bi-megaphone"></i><span>Campaigns &amp; news</span>
        </a>
        <a class="nav-item<?= activeIf('awareness/resources') ?>" href="<?= e(url('awareness/resources')) ?>">
          <i class="bi bi-journal-text"></i><span>Resources</span>
        </a>
      <?php endif; ?>
      <?php if (can('event.manage')): ?>
        <a class="nav-item<?= activeIf('awareness/events') ?>" href="<?= e(url('awareness/events')) ?>">
          <i class="bi bi-calendar-event"></i><span>Events</span>
        </a>
      <?php endif; ?>
      <?php if (can('awareness.analytics')): ?>
        <a class="nav-item<?= activeIf('awareness/analytics') ?>" href="<?= e(url('awareness/analytics')) ?>">
          <i class="bi bi-graph-up"></i><span>Campaign analytics</span>
        </a>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (can('report.export')): ?>
      <div class="nav-group-label">Reporting</div>
      <a class="nav-item<?= activeIf('analytics') ?>" href="<?= e(url('analytics')) ?>">
        <i class="bi bi-bar-chart-line"></i><span>Reports &amp; analytics</span>
      </a>
    <?php endif; ?>

    <?php if (can('user.manage') || can('settings.manage') || can('audit.view')): ?>
      <div class="nav-group-label">Administration</div>
      <?php if (can('dashboard.admin')): ?>
        <a class="nav-item<?= activeIf('admin/dashboard') ?>" href="<?= e(url('admin/dashboard')) ?>">
          <i class="bi bi-grid-1x2"></i><span>Admin dashboard</span>
        </a>
      <?php endif; ?>
      <?php if (can('user.manage')): ?>
        <a class="nav-item<?= activeIf('admin/users') ?>" href="<?= e(url('admin/users')) ?>">
          <i class="bi bi-people"></i><span>Users</span>
        </a>
      <?php endif; ?>
      <?php if (can('role.manage')): ?>
        <a class="nav-item<?= activeIf('admin/roles') ?>" href="<?= e(url('admin/roles')) ?>">
          <i class="bi bi-diagram-3"></i><span>Roles &amp; permissions</span>
        </a>
      <?php endif; ?>
      <?php if (can('category.manage')): ?>
        <a class="nav-item<?= activeIf('admin/categories') ?>" href="<?= e(url('admin/categories')) ?>">
          <i class="bi bi-tags"></i><span>Categories</span>
        </a>
      <?php endif; ?>
      <?php if (can('settings.manage')): ?>
        <a class="nav-item<?= activeIf('admin/settings') ?>" href="<?= e(url('admin/settings')) ?>">
          <i class="bi bi-sliders"></i><span>Settings</span>
        </a>
      <?php endif; ?>
      <?php if (can('audit.view')): ?>
        <a class="nav-item<?= activeIf('admin/audit') ?>" href="<?= e(url('admin/audit')) ?>">
          <i class="bi bi-shield-lock"></i><span>Audit logs</span>
        </a>
      <?php endif; ?>
      <?php if (can('backup.manage')): ?>
        <a class="nav-item<?= activeIf('admin/backup') ?>" href="<?= e(url('admin/backup')) ?>">
          <i class="bi bi-database-down"></i><span>Backup &amp; restore</span>
        </a>
      <?php endif; ?>
    <?php endif; ?>

    <div class="nav-group-label">My account</div>
    <a class="nav-item<?= activeIf('my/reports') ?>" href="<?= e(url('my/reports')) ?>">
      <i class="bi bi-file-earmark-text"></i><span>My reports</span>
    </a>
    <a class="nav-item<?= activeIf('my/profile') ?>" href="<?= e(url('my/profile')) ?>">
      <i class="bi bi-person-gear"></i><span>My profile</span>
    </a>
  </nav>

  <div class="sidebar-foot">
    <a class="btn btn-primary btn-sm w-100" href="<?= e(url('report')) ?>">
      <i class="bi bi-plus-lg me-1"></i>New report
    </a>
  </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
