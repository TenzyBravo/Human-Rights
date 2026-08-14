<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Controller;
use App\Core\Request;
use App\Models\CaseFile;
use App\Models\Category;
use App\Models\Lookup;
use App\Models\Report;

/**
 * Analytical reporting: daily/weekly/monthly/annual rollups with
 * CSV, Excel-compatible and print-to-PDF export.
 */
final class ReportsController extends Controller
{
    private const PERIODS = ['daily', 'weekly', 'monthly', 'annual', 'custom'];

    public function index(): void
    {
        [$from, $to, $period] = $this->range();
        $reports = new Report();
        $cases   = new CaseFile();

        $this->view('admin/analytics', [
            'title'      => 'Reports & analytics',
            'period'     => $period,
            'from'       => $from,
            'to'         => $to,
            'summary'    => $this->summary($from, $to),
            'byCategory' => $this->groupBy('rc.name', $from, $to, 'report_categories rc ON rc.id = r.category_id'),
            'byProvince' => $this->groupBy('p.name', $from, $to, 'provinces p ON p.id = r.province_id'),
            'byStatus'   => $this->groupBy('r.status', $from, $to),
            'trend'      => $reports->monthlyTrend(12),
            'officers'   => $cases->officerPerformance(),
            'categories' => (new Category())->active(),
            'provinces'  => Lookup::provinces(),
        ], 'app');
    }

    public function export(): void
    {
        [$from, $to, $period] = $this->range();
        $format = (string)Request::input('format', 'csv');
        $rows   = (new Report())->search(
            ['from' => $from, 'to' => $to] + Request::only(['status', 'category', 'province', 'urgency']),
            5000, 0
        );

        Audit::log('report.exported', null, null,
            sprintf('%s export, %s to %s (%d rows)', strtoupper($format), $from, $to, count($rows)));

        match ($format) {
            'xls'   => $this->exportExcel($rows, $period, $from, $to),
            'pdf'   => $this->exportPdf($rows, $period, $from, $to),
            default => $this->exportCsv($rows, $period),
        };
    }

    // ------------------------------------------------------------------ exports
    private function exportCsv(array $rows, string $period): never
    {
        $filename = sprintf('hrrams-%s-report-%s.csv', $period, date('Ymd-His'));
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8 correctly
        fputcsv($out, ['Reference', 'Case number', 'Title', 'Category', 'Province',
                       'Urgency', 'Status', 'Incident date', 'Submitted']);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['reference_no'], $row['case_number'] ?? '', $row['title'],
                $row['category_name'], $row['province_name'] ?? '',
                $row['urgency'], statusLabel((string)$row['status']),
                $row['incident_date'] ?? '', $row['created_at'],
            ]);
        }
        fclose($out);
        exit;
    }

    /** Excel-readable single-sheet XML — no external library required. */
    private function exportExcel(array $rows, string $period, string $from, string $to): never
    {
        $filename = sprintf('hrrams-%s-report-%s.xls', $period, date('Ymd-His'));
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8">';
        echo '<style>th{background:#0d6efd;color:#fff;font-weight:bold}td,th{border:1px solid #ccc;padding:4px}</style>';
        echo '</head><body>';
        echo '<h3>HRRAMS ' . e(ucfirst($period)) . ' Report</h3>';
        echo '<p>Period: ' . e($from) . ' to ' . e($to) . ' &nbsp;|&nbsp; Generated: ' . e(date('d M Y H:i')) . '</p>';
        echo '<table><tr><th>Reference</th><th>Case number</th><th>Title</th><th>Category</th>'
           . '<th>Province</th><th>Urgency</th><th>Status</th><th>Incident date</th><th>Submitted</th></tr>';
        foreach ($rows as $row) {
            echo '<tr>'
                . '<td>' . e($row['reference_no']) . '</td>'
                . '<td>' . e($row['case_number'] ?? '') . '</td>'
                . '<td>' . e($row['title']) . '</td>'
                . '<td>' . e($row['category_name']) . '</td>'
                . '<td>' . e($row['province_name'] ?? '') . '</td>'
                . '<td>' . e(ucfirst((string)$row['urgency'])) . '</td>'
                . '<td>' . e(statusLabel((string)$row['status'])) . '</td>'
                . '<td>' . e($row['incident_date'] ?? '') . '</td>'
                . '<td>' . e($row['created_at']) . '</td>'
                . '</tr>';
        }
        echo '</table></body></html>';
        exit;
    }

    /**
     * Print-ready HTML that the browser converts to PDF. This keeps the stack
     * dependency-free; swap in Dompdf here if a server-side PDF is required.
     */
    private function exportPdf(array $rows, string $period, string $from, string $to): never
    {
        $this->view('admin/analytics-print', [
            'title'   => ucfirst($period) . ' report',
            'rows'    => $rows,
            'from'    => $from,
            'to'      => $to,
            'summary' => $this->summary($from, $to),
        ], 'print');
        exit;
    }

    // ---------------------------------------------------------------- internals
    /** @return array{0:string,1:string,2:string} */
    private function range(): array
    {
        $period = (string)Request::input('period', 'monthly');
        if (!in_array($period, self::PERIODS, true)) {
            $period = 'monthly';
        }
        $from = (string)Request::input('from', '');
        $to   = (string)Request::input('to', '');

        if ($period !== 'custom' || $from === '' || $to === '') {
            [$from, $to] = match ($period) {
                'daily'  => [date('Y-m-d'), date('Y-m-d')],
                'weekly' => [date('Y-m-d', strtotime('monday this week')), date('Y-m-d')],
                'annual' => [date('Y-01-01'), date('Y-12-31')],
                default  => [date('Y-m-01'), date('Y-m-t')],
            };
            if ($period === 'custom') {
                $period = 'monthly';
            }
        }
        // Reject anything that is not a plain ISO date.
        foreach ([$from, $to] as $value) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return [date('Y-m-01'), date('Y-m-t'), 'monthly'];
            }
        }
        return [$from, $to, $period];
    }

    private function summary(string $from, string $to): array
    {
        $row = \App\Core\Database::instance()->one(
            'SELECT COUNT(*) AS total,
                    SUM(status IN ("resolved","closed")) AS completed,
                    SUM(status = "rejected") AS rejected,
                    -- HIGH_PRIORITY is a reserved word in MySQL 8, hence high_urgency.
                    SUM(urgency IN ("high","critical")) AS high_urgency,
                    SUM(is_anonymous = 1) AS anonymous
               FROM reports
              WHERE deleted_at IS NULL AND created_at BETWEEN ? AND ?',
            [$from . ' 00:00:00', $to . ' 23:59:59']
        ) ?? [];

        $avg = \App\Core\Database::instance()->scalar(
            'SELECT ROUND(AVG(TIMESTAMPDIFF(DAY, opened_at, resolved_at)), 1)
               FROM cases WHERE resolved_at IS NOT NULL AND deleted_at IS NULL
                AND opened_at BETWEEN ? AND ?',
            [$from . ' 00:00:00', $to . ' 23:59:59']
        );

        return [
            'total'            => (int)($row['total'] ?? 0),
            'completed'        => (int)($row['completed'] ?? 0),
            'rejected'         => (int)($row['rejected'] ?? 0),
            'high_priority'    => (int)($row['high_urgency'] ?? 0),
            'anonymous'        => (int)($row['anonymous'] ?? 0),
            'avg_resolution'   => (float)($avg ?? 0),
        ];
    }

    /** $groupExpr and $join are developer-supplied constants, never user input. */
    private function groupBy(string $groupExpr, string $from, string $to, ?string $join = null): array
    {
        $sql = 'SELECT ' . $groupExpr . ' AS label, COUNT(*) AS value FROM reports r';
        if ($join !== null) {
            $sql .= ' LEFT JOIN ' . $join;
        }
        $sql .= ' WHERE r.deleted_at IS NULL AND r.created_at BETWEEN ? AND ?
                  GROUP BY label ORDER BY value DESC';
        return \App\Core\Database::instance()->all($sql, [$from . ' 00:00:00', $to . ' 23:59:59']);
    }
}
