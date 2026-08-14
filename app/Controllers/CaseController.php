<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Notify;
use App\Core\Request;
use App\Core\Uploader;
use App\Models\CaseFile;
use App\Models\Category;
use App\Models\Lookup;
use App\Models\Report;
use App\Models\User;

final class CaseController extends Controller
{
    // -------------------------------------------------------------- report list
    public function reportsIndex(): void
    {
        $reports = new Report();
        $filters = Request::only(['q', 'status', 'category', 'province', 'urgency', 'from', 'to']);
        $page    = $this->paginate($reports->searchCount($filters));

        $this->view('cases/reports-index', [
            'title'      => 'All reports',
            'rows'       => $reports->search($filters, $page['perPage'], $page['offset']),
            'page'       => $page,
            'filters'    => $filters,
            'categories' => (new Category())->active(),
            'provinces'  => Lookup::provinces(),
        ], 'app');
    }

    public function reportShow(string $id): void
    {
        if (!ctype_digit($id)) {
            $this->redirect('reports');
        }
        $reports = new Report();
        $report  = $reports->detail((int)$id);
        if ($report === null) {
            $this->fail(404, 'Report not found.');
        }
        if (!empty($report['case_id'])) {
            $this->redirect('cases/' . $report['case_id']);
        }
        $this->view('cases/report-show', [
            'title'       => $report['reference_no'],
            'report'      => $report,
            'attachments' => $reports->attachments((int)$id),
        ], 'app');
    }

    // ---------------------------------------------------------------- case list
    public function index(): void
    {
        $cases   = new CaseFile();
        $filters = Request::only(['q', 'status', 'priority', 'category', 'province', 'unassigned', 'overdue']);

        // Officers only ever see their own workload.
        $onlyMine = !Auth::can('case.view_any');
        if ($onlyMine) {
            if (!Auth::can('case.view_assigned')) {
                $this->fail(403, 'You have no access to case files.');
            }
            $filters['officer_id'] = (int)Auth::id();
        }
        if (($filters['status'] ?? '') === '') {
            $filters['status'] = 'open';
        }

        $page = $this->paginate($cases->searchCount($filters));
        $this->view('cases/index', [
            'title'      => $onlyMine ? 'My assigned cases' : 'Case management',
            'rows'       => $cases->search($filters, $page['perPage'], $page['offset']),
            'page'       => $page,
            'filters'    => $filters,
            'onlyMine'   => $onlyMine,
            'categories' => (new Category())->active(),
            'provinces'  => Lookup::provinces(),
            'stats'      => $cases->stats($onlyMine ? (int)Auth::id() : null),
        ], 'app');
    }

    public function show(string $id): void
    {
        $case = $this->authorisedCase($id);

        $reports = new Report();
        $this->view('cases/show', [
            'title'       => $case['case_number'],
            'case'        => $case,
            'timeline'    => (new CaseFile())->timeline((int)$case['id']),
            'attachments' => array_merge(
                $reports->attachments((int)$case['report_id']),
                $this->caseEvidence((int)$case['id'])
            ),
            'officers'    => Auth::can('case.assign') ? (new User())->officers() : [],
            'statuses'    => CaseFile::STATUSES,
        ], 'app');
    }

    // ------------------------------------------------------------------ actions
    public function assign(string $id): void
    {
        $case      = $this->authorisedCase($id);
        $officerId = Request::int('officer_id');
        $note      = (string)Request::input('note', '');

        $valid = Database::instance()->scalar(
            'SELECT 1 FROM users u JOIN user_roles ur ON ur.user_id = u.id
               JOIN roles r ON r.id = ur.role_id
              WHERE u.id = ? AND r.slug = "officer" AND u.status = "active" AND u.deleted_at IS NULL',
            [$officerId]
        );
        if (!$valid) {
            Flash::error('Please choose an active human rights officer.');
            $this->redirect('cases/' . $case['id']);
        }

        (new CaseFile())->assign((int)$case['id'], $officerId, $note !== '' ? $note : null);
        Flash::success('Case assigned.');
        $this->redirect('cases/' . $case['id']);
    }

    public function changeStatus(string $id): void
    {
        $case   = $this->authorisedCase($id);
        $status = (string)Request::input('status', '');
        $note   = (string)Request::input('note', '');

        if (!in_array($status, CaseFile::STATUSES, true)) {
            Flash::error('That is not a valid case status.');
            $this->redirect('cases/' . $case['id']);
        }
        // Only a supervisor/admin may resolve, close or reject.
        if (in_array($status, ['resolved', 'closed', 'rejected'], true) && !Auth::can('case.close')) {
            Flash::error('Only a supervisor can resolve or close a case.');
            $this->redirect('cases/' . $case['id']);
        }

        if ((new CaseFile())->changeStatus((int)$case['id'], $status, $note !== '' ? $note : null)) {
            Flash::success('Status updated to ' . statusLabel($status) . '.');
        } else {
            Flash::info('The case is already at that status.');
        }
        $this->redirect('cases/' . $case['id']);
    }

    public function addNote(string $id): void
    {
        $case = $this->authorisedCase($id);
        $data = $this->validate([
            'body'  => 'required|min:3|max:5000',
            'title' => 'nullable|max:200',
        ], [], 'cases/' . $case['id']);

        (new CaseFile())->addActivity((int)$case['id'], 'note', [
            'title'       => $data['title'] ?: 'Case note',
            'body'        => $data['body'],
            'is_internal' => Request::bool('share_with_reporter') ? 0 : 1,
        ]);
        Flash::success('Note added to the case timeline.');
        $this->redirect('cases/' . $case['id']);
    }

    public function uploadEvidence(string $id): void
    {
        $case = $this->authorisedCase($id);

        $activityId = (new CaseFile())->addActivity((int)$case['id'], 'evidence', [
            'title' => (string)(Request::input('title') ?: 'Evidence uploaded'),
            'body'  => (string)Request::input('body', ''),
        ]);

        $result = Uploader::handle('evidence', 'case_activity', $activityId);
        foreach ($result['errors'] as $message) {
            Flash::warning($message);
        }
        if ($result['stored'] !== []) {
            Flash::success(count($result['stored']) . ' file(s) attached to the case.');
        } elseif ($result['errors'] === []) {
            Flash::info('No files were selected.');
        }
        $this->redirect('cases/' . $case['id']);
    }

    public function scheduleInterview(string $id): void
    {
        $case = $this->authorisedCase($id);
        $data = $this->validate([
            'title'        => 'required|min:3|max:200',
            'scheduled_at' => 'required|max:20',
            'body'         => 'nullable|max:2000',
        ], ['scheduled_at' => 'Interview date and time'], 'cases/' . $case['id']);

        $when = strtotime((string)$data['scheduled_at']);
        if ($when === false) {
            Flash::error('Please provide a valid date and time.');
            $this->redirect('cases/' . $case['id']);
        }

        (new CaseFile())->addActivity((int)$case['id'], 'interview', [
            'title'        => $data['title'],
            'body'         => $data['body'] ?: null,
            'scheduled_at' => date('Y-m-d H:i:s', $when),
            'is_internal'  => 0,
        ]);
        Flash::success('Interview scheduled for ' . date('d M Y, H:i', $when) . '.');
        $this->redirect('cases/' . $case['id']);
    }

    public function requestInformation(string $id): void
    {
        $case = $this->authorisedCase($id);
        $data = $this->validate(['body' => 'required|min:10|max:3000'], [], 'cases/' . $case['id']);

        $cases = new CaseFile();
        $cases->addActivity((int)$case['id'], 'information_request', [
            'title'       => 'Additional information requested',
            'body'        => $data['body'],
            'is_internal' => 0,
        ]);
        $cases->changeStatus((int)$case['id'], 'awaiting_information', 'Awaiting reporter response.');

        $subject = 'More information needed — ' . $case['reference_no'];
        if (!empty($case['reporter_user_id'])) {
            Notify::user((int)$case['reporter_user_id'], $subject, $data['body'],
                'my/reports/' . $case['report_id'], true);
        } elseif (!empty($case['reporter_email'])) {
            Notify::reporter($case['reporter_email'], $subject, $data['body']);
        } else {
            Flash::warning('This report was filed anonymously, so the reporter could not be contacted.');
        }

        Flash::success('Information request recorded.');
        $this->redirect('cases/' . $case['id']);
    }

    public function escalate(string $id): void
    {
        $case = $this->authorisedCase($id);
        $note = (string)Request::input('note', '');
        (new CaseFile())->changeStatus((int)$case['id'], 'escalated', $note !== '' ? $note : null);
        Flash::warning('Case escalated. Supervisors and administrators have been notified.');
        $this->redirect('cases/' . $case['id']);
    }

    public function close(string $id): void
    {
        $case = $this->authorisedCase($id);
        $data = $this->validate([
            'resolution_summary' => 'required|min:20|max:5000',
            'outcome'            => 'required|in:resolved,closed,rejected',
        ], ['resolution_summary' => 'Resolution summary'], 'cases/' . $case['id']);

        (new CaseFile())->changeStatus((int)$case['id'], (string)$data['outcome'], (string)$data['resolution_summary']);
        Flash::success('Case marked as ' . statusLabel((string)$data['outcome']) . '.');
        $this->redirect('cases/' . $case['id']);
    }

    // -------------------------------------------------------------- attachments
    public function attachment(string $id): void
    {
        if (!ctype_digit($id)) {
            $this->fail(404, 'File not found.');
        }
        $attachment = Database::instance()->one(
            'SELECT * FROM attachments WHERE id = ? AND deleted_at IS NULL', [(int)$id]
        );
        // A missing file and an unauthorised one both answer 403, so probing ids
        // cannot be used to discover which evidence exists.
        if ($attachment === null || !$this->mayViewAttachment($attachment)) {
            Audit::log('access.denied', 'attachment', (int)$id, 'Attempted evidence access');
            $this->fail(403, 'You may not view this file.');
        }
        Audit::log('evidence.viewed', 'attachment', (int)$id, $attachment['original_name']);
        Uploader::stream($attachment);
    }

    public function performance(): void
    {
        $cases = new CaseFile();
        $this->view('cases/performance', [
            'title'    => 'Officer performance',
            'rows'     => $cases->officerPerformance(),
            'stats'    => $cases->stats(),
        ], 'app');
    }

    // ---------------------------------------------------------------- internals
    /** Loads a case and enforces per-role visibility, or renders 403/404 and exits. */
    private function authorisedCase(string $id): array
    {
        if (!ctype_digit($id)) {
            $this->fail(404, 'Case not found.');
        }
        $case = (new CaseFile())->detail((int)$id);
        if ($case === null) {
            $this->fail(404, 'Case not found.');
        }
        $mayViewAll = Auth::can('case.view_any');
        $isOwner    = (int)($case['officer_id'] ?? 0) === (int)Auth::id();
        if (!$mayViewAll && !$isOwner) {
            Audit::log('access.denied', 'case', (int)$id, 'Case not assigned to this officer');
            $this->fail(403, 'This case is not assigned to you.');
        }
        return $case;
    }

    private function caseEvidence(int $caseId): array
    {
        return Database::instance()->all(
            'SELECT a.* FROM attachments a
               JOIN case_activities ca ON ca.id = a.entity_id AND a.entity_type = "case_activity"
              WHERE ca.case_id = ? AND a.deleted_at IS NULL
              ORDER BY a.created_at ASC',
            [$caseId]
        );
    }

    private function mayViewAttachment(array $attachment): bool
    {
        if (Auth::can('case.view_any') || Auth::can('report.view_any')) {
            return true;
        }
        $userId = (int)Auth::id();

        if ($attachment['entity_type'] === 'report') {
            $reportId = (int)$attachment['entity_id'];
            // The reporter who filed it, or the officer holding its case.
            $owner = Database::instance()->scalar(
                'SELECT 1 FROM reports r
                   LEFT JOIN cases c ON c.report_id = r.id AND c.deleted_at IS NULL
                  WHERE r.id = ? AND (r.reporter_user_id = ? OR c.officer_id = ?)',
                [$reportId, $userId, $userId]
            );
            return (bool)$owner;
        }

        if ($attachment['entity_type'] === 'case_activity') {
            return (bool)Database::instance()->scalar(
                'SELECT 1 FROM case_activities ca JOIN cases c ON c.id = ca.case_id
                  WHERE ca.id = ? AND c.officer_id = ?',
                [(int)$attachment['entity_id'], $userId]
            );
        }

        // Public content (campaign / resource covers) is freely viewable.
        return in_array($attachment['entity_type'], ['campaign', 'resource'], true);
    }
}
