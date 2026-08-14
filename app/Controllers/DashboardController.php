<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Notify;
use App\Models\AuditLog;
use App\Models\CaseFile;
use App\Models\Report;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $cases   = new CaseFile();
        $reports = new Report();

        $isOfficerOnly = Auth::hasRole('officer') && !Auth::hasRole('supervisor', 'admin');
        $officerId     = $isOfficerOnly ? (int)Auth::id() : null;

        $myFilters = ['status' => 'open'];
        if ($officerId !== null) {
            $myFilters['officer_id'] = $officerId;
        }

        $this->view('dashboard/index', [
            'title'         => $isOfficerOnly ? 'Officer dashboard' : 'Supervisor dashboard',
            'isOfficerOnly' => $isOfficerOnly,
            'caseStats'     => $cases->stats($officerId),
            'reportStats'   => $reports->totals(),
            'myCases'       => $cases->search($myFilters, 8, 0),
            'unassigned'    => Auth::can('case.assign')
                                ? $cases->search(['unassigned' => 1, 'status' => 'open'], 6, 0) : [],
            'overdue'       => $cases->search(
                                $officerId !== null
                                    ? ['overdue' => 1, 'officer_id' => $officerId]
                                    : ['overdue' => 1], 5, 0),
            'interviews'    => $cases->upcomingInterviews($officerId, 6),
            'recentReports' => $reports->recent(6),
            'notifications' => Notify::unread((int)Auth::id(), 6),
            'performance'   => Auth::can('dashboard.department') ? $cases->officerPerformance() : [],
            'activity'      => Auth::can('audit.view') ? (new AuditLog())->recent(8) : [],
        ], 'app');
    }

    /** JSON feed for every dashboard chart. */
    public function charts(): void
    {
        $reports = new Report();
        $cases   = new CaseFile();
        $this->json([
            'byCategory' => $reports->byCategory(),
            'byProvince' => $reports->byProvince(),
            'byStatus'   => $reports->byStatus(),
            'trend'      => $reports->monthlyTrend(12),
            'heatmap'    => $reports->heatmap(),
            'caseStats'  => $cases->stats(),
        ]);
    }
}
