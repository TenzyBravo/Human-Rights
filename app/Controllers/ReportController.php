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

final class ReportController extends Controller
{
    public function create(): void
    {
        $this->view('report/create', [
            'title'      => 'Report a Human Rights Violation',
            'categories' => (new Category())->active(),
            'provinces'  => Lookup::provinces(),
            'districts'  => Lookup::districts(),
            'user'       => Auth::user(),
        ]);
    }

    public function store(): void
    {
        $anonymous = Request::bool('is_anonymous');

        $rules = [
            'category_id'      => 'required|int|exists:report_categories,id',
            'title'            => 'required|min:8|max:200',
            'description'      => 'required|min:30|max:20000',
            'incident_date'    => 'nullable|date|before_or_today',
            'incident_time'    => 'nullable|time',
            'location'         => 'nullable|max:255',
            // Verified against the lookup tables so a tampered id becomes a field
            // error rather than a foreign-key failure on insert.
            'province_id'      => 'nullable|int|exists:provinces,id',
            'district_id'      => 'nullable|int|exists:districts,id',
            'latitude'         => 'nullable|numeric|between:-90,90',
            'longitude'        => 'nullable|numeric|between:-180,180',
            'persons_involved' => 'nullable|max:2000',
            'organization_involved' => 'nullable|max:200',
            'victim_count'     => 'nullable|int|between:0,100000',
            'witness_details'  => 'nullable|max:2000',
            'urgency'          => 'required|in:low,medium,high,critical',
            'consent'          => 'accepted',
        ];

        // Identifying details are required only when the reporter is not anonymous.
        if (!$anonymous) {
            $rules['reporter_name']  = 'required|min:3|max:160';
            $rules['reporter_email'] = 'nullable|email|max:190';
            $rules['reporter_phone'] = 'nullable|phone';
            $rules['reporter_address'] = 'nullable|max:255';
            $rules['reporter_nationality'] = 'nullable|max:80';
        }

        $data = $this->validate($rules, [
            'consent'     => 'The consent declaration',
            'category_id' => 'Incident category',
        ], 'report');

        $reports = new Report();
        $cases   = new CaseFile();
        $db      = Database::instance();

        $db->begin();
        try {
            $reference = $reports->nextReference();
            $reportId  = $reports->create([
                'reference_no'     => $reference,
                'reporter_user_id' => $anonymous ? null : Auth::id(),
                'is_anonymous'     => $anonymous ? 1 : 0,
                'reporter_name'    => $anonymous ? null : ($data['reporter_name'] ?? null),
                'reporter_phone'   => $anonymous ? null : ($data['reporter_phone'] ?: null),
                'reporter_email'   => $anonymous ? null : ($data['reporter_email'] ?: null),
                'reporter_address' => $anonymous ? null : ($data['reporter_address'] ?: null),
                'reporter_nationality' => $anonymous ? null : ($data['reporter_nationality'] ?: null),
                'province_id'      => $data['province_id'] ? (int)$data['province_id'] : null,
                'district_id'      => $data['district_id'] ? (int)$data['district_id'] : null,
                'category_id'      => (int)$data['category_id'],
                'title'            => $data['title'],
                'description'      => $data['description'],
                'incident_date'    => $data['incident_date'] ?: null,
                'incident_time'    => $data['incident_time'] ?: null,
                'location'         => $data['location'] ?: null,
                'latitude'         => $data['latitude'] !== '' ? $data['latitude'] : null,
                'longitude'        => $data['longitude'] !== '' ? $data['longitude'] : null,
                'persons_involved' => $data['persons_involved'] ?: null,
                'organization_involved' => $data['organization_involved'] ?: null,
                'victim_count'     => (int)($data['victim_count'] ?: 1),
                'witness_details'  => $data['witness_details'] ?: null,
                'urgency'          => $data['urgency'],
                'consent_given'    => 1,
                'status'           => 'submitted',
            ]);

            $caseId = $cases->openForReport($reportId, (string)$data['urgency']);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            \App\Core\App::log('error', 'Report submission failed: ' . $e->getMessage());
            Flash::error('We could not save your report. Please try again.');
            $this->redirect('report');
        }

        // Evidence is stored after the transaction so a failed upload never
        // discards an otherwise valid report.
        $upload = Uploader::handle('evidence', 'report', $reportId);
        foreach ($upload['errors'] as $message) {
            Flash::warning('Evidence: ' . $message);
        }

        Audit::log('report.submitted', 'report', $reportId,
            'Report ' . $reference . ($anonymous ? ' (anonymous)' : ''));

        Notify::role('supervisor', 'New report received: ' . $reference,
            statusLabel((string)$data['urgency']) . ' urgency — ' . excerpt($data['title'], 80),
            'cases/' . $caseId);

        if (!$anonymous && !empty($data['reporter_email'])) {
            Notify::reporter($data['reporter_email'], 'Report received — ' . $reference,
                'Your report has been received and assigned reference ' . $reference
                . '. Track its progress at ' . url('track?ref=' . $reference));
        }
        if (Auth::id() !== null && !$anonymous) {
            Notify::user((int)Auth::id(), 'Report submitted: ' . $reference,
                'We have received your report and opened a case file.', 'my/reports/' . $reportId);
        }

        $this->redirect('report/success/' . $reference);
    }

    public function success(string $ref): void
    {
        $report = (new Report())->findByReference($ref);
        if ($report === null) {
            $this->redirect('report');
        }
        $this->view('report/success', [
            'title'  => 'Report submitted',
            'report' => $report,
        ]);
    }

    /** Printable acknowledgement receipt. */
    public function receipt(string $ref): void
    {
        $report = (new Report())->findByReference($ref);
        if ($report === null) {
            $this->redirect('track');
        }
        $this->view('report/receipt', ['title' => 'Acknowledgement receipt', 'report' => $report], 'print');
    }

    // ---------------------------------------------------------------- tracking
    public function track(): void
    {
        $report = null;
        $ref    = trim((string)Request::input('ref', ''));
        if ($ref !== '') {
            $report = (new Report())->findByReference($ref);
            if ($report === null) {
                Flash::warning('No report was found with that reference number.');
            }
        }
        $this->view('report/track', [
            'title'  => 'Track a report',
            'ref'    => $ref,
            'report' => $report,
        ]);
    }

    public function trackLookup(): void
    {
        $ref = (string)Request::input('ref', '');
        $this->redirect('track?ref=' . urlencode($ref));
    }

    // ----------------------------------------------------------- citizen views
    public function mine(): void
    {
        $userId = (int)Auth::id();
        $this->view('report/mine', [
            'title'   => 'My reports',
            'reports' => (new Report())->forUser($userId),
        ], 'app');
    }

    public function showMine(string $id): void
    {
        if (!ctype_digit($id)) {
            $this->redirect('my/reports');
        }
        $reports = new Report();
        $report  = $reports->detail((int)$id);

        // A citizen may only ever see their own submission.
        if ($report === null || (int)($report['reporter_user_id'] ?? 0) !== (int)Auth::id()) {
            Audit::log('access.denied', 'report', (int)$id, 'Attempted to view another reporter\'s report');
            $this->fail(403, 'This report does not belong to you.');
        }

        $timeline = [];
        if (!empty($report['case_id'])) {
            $timeline = Database::instance()->all(
                'SELECT a.type, a.title, a.body, a.new_value, a.created_at
                   FROM case_activities a
                  WHERE a.case_id = ? AND a.is_internal = 0 AND a.deleted_at IS NULL
                  ORDER BY a.created_at DESC',
                [(int)$report['case_id']]
            );
        }

        $this->view('report/show-mine', [
            'title'       => $report['reference_no'],
            'report'      => $report,
            'attachments' => $reports->attachments((int)$id),
            'timeline'    => $timeline,
        ], 'app');
    }
}
