<?php
/**
 * Route table. Middleware: auth | guest | verified | role:a|b | can:permission
 */
declare(strict_types=1);

use App\Core\Router;

$r = new Router();

// ------------------------------------------------------------------- public
$r->get('/',                    ['HomeController', 'index']);
$r->get('/about',               ['HomeController', 'about']);
$r->get('/contact',             ['HomeController', 'contact']);
$r->post('/contact',            ['HomeController', 'submitContact']);
$r->get('/faqs',                ['HomeController', 'faqs']);
$r->get('/statistics',          ['HomeController', 'statistics']);
$r->get('/api/statistics',      ['HomeController', 'statisticsJson']);
$r->get('/api/districts/{province}', ['HomeController', 'districtsJson']);

$r->get('/campaigns',           ['AwarenessController', 'campaigns']);
$r->get('/news',                ['AwarenessController', 'news']);
$r->get('/stories',             ['AwarenessController', 'stories']);
$r->get('/resources',           ['AwarenessController', 'resources']);
$r->get('/resources/{slug}',    ['AwarenessController', 'showResource']);
$r->get('/events',              ['AwarenessController', 'events']);
$r->get('/campaigns/{slug}',    ['AwarenessController', 'show']);

// --------------------------------------------------------------- reporting
$r->get('/report',              ['ReportController', 'create']);
$r->post('/report',             ['ReportController', 'store']);
$r->get('/report/success/{ref}',['ReportController', 'success']);
$r->get('/report/receipt/{ref}',['ReportController', 'receipt']);
$r->get('/track',               ['ReportController', 'track']);
$r->post('/track',              ['ReportController', 'trackLookup']);

// ---------------------------------------------------------------- auth
$r->get('/register',            ['AuthController', 'showRegister'],  ['guest']);
$r->post('/register',           ['AuthController', 'register'],      ['guest']);
$r->get('/login',               ['AuthController', 'showLogin'],     ['guest']);
$r->post('/login',              ['AuthController', 'login'],         ['guest']);
$r->post('/logout',             ['AuthController', 'logout'],        ['auth']);
$r->get('/verify/{token}',      ['AuthController', 'verify']);
$r->get('/verify-notice',       ['AuthController', 'verifyNotice'],  ['auth']);
$r->post('/verify-resend',      ['AuthController', 'resendVerification'], ['auth']);
$r->get('/forgot-password',     ['AuthController', 'showForgot'],    ['guest']);
$r->post('/forgot-password',    ['AuthController', 'sendReset'],     ['guest']);
$r->get('/reset-password/{token}', ['AuthController', 'showReset'],  ['guest']);
$r->post('/reset-password',     ['AuthController', 'resetPassword'], ['guest']);

// ---------------------------------------------------------- citizen area
$r->get('/my/reports',          ['ReportController', 'mine'],        ['auth']);
$r->get('/my/reports/{id}',     ['ReportController', 'showMine'],    ['auth']);
$r->get('/my/profile',          ['ProfileController', 'edit'],       ['auth']);
$r->post('/my/profile',         ['ProfileController', 'update'],     ['auth']);
$r->post('/my/password',        ['ProfileController', 'changePassword'], ['auth']);
$r->get('/notifications',       ['ProfileController', 'notifications'], ['auth']);
$r->post('/notifications/read', ['ProfileController', 'markRead'],   ['auth']);

// ------------------------------------------------------- staff dashboards
$r->get('/dashboard',           ['DashboardController', 'index'],    ['role:officer|supervisor|admin']);
$r->get('/api/dashboard/charts',['DashboardController', 'charts'],   ['role:officer|supervisor|awareness|admin']);

// --------------------------------------------------------- case management
$r->get('/reports',             ['CaseController', 'reportsIndex'],  ['can:report.view_any']);
$r->get('/reports/{id}',        ['CaseController', 'reportShow'],    ['can:report.view_any']);
$r->get('/cases',               ['CaseController', 'index'],         ['auth']);
$r->get('/cases/{id}',          ['CaseController', 'show'],          ['auth']);
$r->post('/cases/{id}/assign',  ['CaseController', 'assign'],        ['can:case.assign']);
$r->post('/cases/{id}/status',  ['CaseController', 'changeStatus'],  ['can:case.update']);
$r->post('/cases/{id}/note',    ['CaseController', 'addNote'],       ['can:case.note']);
$r->post('/cases/{id}/evidence',['CaseController', 'uploadEvidence'],['can:case.evidence']);
$r->post('/cases/{id}/interview',['CaseController','scheduleInterview'], ['can:case.interview']);
$r->post('/cases/{id}/request-info', ['CaseController', 'requestInformation'], ['can:case.update']);
$r->post('/cases/{id}/escalate',['CaseController', 'escalate'],      ['can:case.escalate']);
$r->post('/cases/{id}/close',   ['CaseController', 'close'],         ['can:case.close']);
$r->get('/attachments/{id}',    ['CaseController', 'attachment'],    ['auth']);
$r->get('/officers',            ['CaseController', 'performance'],   ['can:dashboard.department']);

// ---------------------------------------------------------- awareness admin
$r->get('/awareness/manage',    ['AwarenessController', 'manage'],   ['can:awareness.manage']);
$r->get('/awareness/create',    ['AwarenessController', 'form'],     ['can:awareness.manage']);
$r->get('/awareness/edit/{id}', ['AwarenessController', 'form'],     ['can:awareness.manage']);
$r->post('/awareness/save',     ['AwarenessController', 'save'],     ['can:awareness.manage']);
$r->post('/awareness/delete/{id}', ['AwarenessController', 'destroy'], ['can:awareness.manage']);
$r->get('/awareness/resources', ['AwarenessController', 'manageResources'], ['can:awareness.manage']);
$r->post('/awareness/resources/save', ['AwarenessController', 'saveResource'], ['can:awareness.manage']);
$r->post('/awareness/resources/delete/{id}', ['AwarenessController', 'destroyResource'], ['can:awareness.manage']);
$r->get('/awareness/events',    ['AwarenessController', 'manageEvents'], ['can:event.manage']);
$r->post('/awareness/events/save', ['AwarenessController', 'saveEvent'], ['can:event.manage']);
$r->get('/awareness/analytics', ['AwarenessController', 'analytics'], ['can:awareness.analytics']);

// -------------------------------------------------------------- reporting
$r->get('/analytics',           ['ReportsController', 'index'],      ['can:report.export']);
$r->get('/analytics/export',    ['ReportsController', 'export'],     ['can:report.export']);

// ------------------------------------------------------------------- admin
$r->get('/admin/dashboard',     ['AdminController', 'dashboard'],    ['can:dashboard.admin']);
$r->get('/admin/users',         ['AdminController', 'users'],        ['can:user.manage']);
$r->get('/admin/users/create',  ['AdminController', 'userForm'],     ['can:user.manage']);
$r->get('/admin/users/{id}',    ['AdminController', 'userForm'],     ['can:user.manage']);
$r->post('/admin/users/save',   ['AdminController', 'saveUser'],     ['can:user.manage']);
$r->post('/admin/users/{id}/status', ['AdminController', 'setUserStatus'], ['can:user.manage']);
$r->post('/admin/users/{id}/delete', ['AdminController', 'deleteUser'], ['can:user.manage']);
$r->get('/admin/roles',         ['AdminController', 'roles'],        ['can:role.manage']);
$r->post('/admin/roles/save',   ['AdminController', 'saveRolePermissions'], ['can:role.manage']);
$r->get('/admin/categories',    ['AdminController', 'categories'],   ['can:category.manage']);
$r->post('/admin/categories/save', ['AdminController', 'saveCategory'], ['can:category.manage']);
$r->post('/admin/categories/delete/{id}', ['AdminController', 'deleteCategory'], ['can:category.manage']);
$r->get('/admin/settings',      ['AdminController', 'settings'],     ['can:settings.manage']);
$r->post('/admin/settings',     ['AdminController', 'saveSettings'], ['can:settings.manage']);
$r->get('/admin/audit',         ['AdminController', 'audit'],        ['can:audit.view']);
$r->get('/admin/backup',        ['AdminController', 'backup'],       ['can:backup.manage']);
$r->post('/admin/backup/run',   ['AdminController', 'runBackup'],    ['can:backup.manage']);
$r->get('/admin/backup/download/{file}', ['AdminController', 'downloadBackup'], ['can:backup.manage']);

return $r;
