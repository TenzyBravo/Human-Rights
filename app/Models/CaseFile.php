<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Notify;

/**
 * A case is opened automatically for every submitted report and carries the
 * investigation lifecycle. ("case" is a PHP reserved word, hence CaseFile.)
 */
final class CaseFile extends Model
{
    protected string $table = 'cases';

    public const STATUSES = ['submitted','under_review','assigned','investigating',
                             'awaiting_information','escalated','resolved','closed','rejected'];

    public const OPEN_STATUSES = ['submitted','under_review','assigned','investigating',
                                  'awaiting_information','escalated'];

    public function nextCaseNumber(): string
    {
        $year = date('Y');
        $seq  = (int)$this->db()->scalar(
            'SELECT COUNT(*) + 1 FROM cases WHERE YEAR(created_at) = ?', [(int)$year]
        );
        do {
            $number = sprintf('CASE-%s-%05d', $year, $seq);
            $taken  = $this->db()->scalar('SELECT 1 FROM cases WHERE case_number = ?', [$number]);
            $seq++;
        } while ($taken);
        return $number;
    }

    /** Opens the case that accompanies a newly submitted report. */
    public function openForReport(int $reportId, string $urgency): int
    {
        $slaDays = (int)($this->db()->scalar(
            'SELECT svalue FROM settings WHERE skey = "sla_days_default"'
        ) ?: 30);
        // Critical reports get a much tighter clock.
        $days = match ($urgency) {
            'critical' => 3,
            'high'     => 7,
            'medium'   => 14,
            default    => $slaDays,
        };

        return $this->db()->insert('cases', [
            'case_number' => $this->nextCaseNumber(),
            'report_id'   => $reportId,
            'status'      => 'under_review',
            'priority'    => $urgency,
            'due_at'      => date('Y-m-d H:i:s', strtotime("+{$days} days")),
            'created_by'  => Auth::id(),
        ]);
    }

    public function detail(int $id): ?array
    {
        return $this->db()->one(
            'SELECT c.*, r.reference_no, r.title, r.description, r.incident_date, r.incident_time,
                    r.location, r.latitude, r.longitude, r.persons_involved, r.organization_involved,
                    r.victim_count, r.witness_details, r.urgency, r.is_anonymous,
                    r.reporter_name, r.reporter_email, r.reporter_phone, r.reporter_user_id,
                    r.id AS report_id,
                    rc.name AS category_name,
                    p.name AS province_name, d.name AS district_name,
                    CONCAT(o.first_name," ",o.last_name) AS officer_name, o.email AS officer_email,
                    CONCAT(s.first_name," ",s.last_name) AS supervisor_name
               FROM cases c
               JOIN reports r            ON r.id = c.report_id
               JOIN report_categories rc ON rc.id = r.category_id
               LEFT JOIN provinces p     ON p.id = r.province_id
               LEFT JOIN districts d     ON d.id = r.district_id
               LEFT JOIN users o         ON o.id = c.officer_id
               LEFT JOIN users s         ON s.id = c.supervisor_id
              WHERE c.id = ? AND c.deleted_at IS NULL',
            [$id]
        );
    }

    public function findByReportId(int $reportId): ?array
    {
        return $this->db()->one(
            'SELECT * FROM cases WHERE report_id = ? AND deleted_at IS NULL', [$reportId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function timeline(int $caseId): array
    {
        return $this->db()->all(
            'SELECT a.*, CONCAT(u.first_name," ",u.last_name) AS author
               FROM case_activities a
               LEFT JOIN users u ON u.id = a.user_id
              WHERE a.case_id = ? AND a.deleted_at IS NULL
              ORDER BY a.created_at DESC, a.id DESC',
            [$caseId]
        );
    }

    public function addActivity(int $caseId, string $type, array $data = []): int
    {
        $id = $this->db()->insert('case_activities', [
            'case_id'      => $caseId,
            'user_id'      => Auth::id(),
            'type'         => $type,
            'title'        => $data['title']        ?? null,
            'body'         => $data['body']         ?? null,
            'old_value'    => $data['old_value']    ?? null,
            'new_value'    => $data['new_value']    ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'is_internal'  => (int)($data['is_internal'] ?? 1),
        ]);
        Audit::log('case.activity.' . $type, 'case', $caseId, $data['title'] ?? $type);
        return $id;
    }

    public function assign(int $caseId, int $officerId, ?string $note = null): void
    {
        $case = $this->find($caseId);
        if ($case === null) {
            return;
        }
        $this->db()->begin();
        try {
            // Close out the previous assignment, if any.
            $this->db()->run(
                'UPDATE case_assignments SET unassigned_at = NOW()
                  WHERE case_id = ? AND unassigned_at IS NULL', [$caseId]
            );
            $this->db()->insert('case_assignments', [
                'case_id'     => $caseId,
                'officer_id'  => $officerId,
                'assigned_by' => (int)Auth::id(),
                'note'        => $note,
            ]);
            $newStatus = in_array($case['status'], ['submitted','under_review'], true) ? 'assigned' : $case['status'];
            $this->db()->update('cases', [
                'officer_id'    => $officerId,
                'supervisor_id' => Auth::id(),
                'status'        => $newStatus,
                'updated_by'    => Auth::id(),
            ], 'id = :id', ['id' => $caseId]);
            (new Report())->setStatus((int)$case['report_id'], $newStatus);

            $this->addActivity($caseId, 'assignment', [
                'title'     => 'Case assigned',
                'body'      => $note,
                'new_value' => (string)$officerId,
            ]);
            $this->db()->commit();
        } catch (\Throwable $e) {
            $this->db()->rollback();
            throw $e;
        }

        Notify::user($officerId, 'New case assigned to you',
            'Case ' . $case['case_number'] . ' has been assigned to you for investigation.',
            'cases/' . $caseId, true);
        Audit::log('case.assigned', 'case', $caseId, 'Assigned to user #' . $officerId);
    }

    public function changeStatus(int $caseId, string $status, ?string $note = null): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }
        $case = $this->find($caseId);
        if ($case === null || $case['status'] === $status) {
            return false;
        }

        $data = ['status' => $status, 'updated_by' => Auth::id()];
        if ($status === 'resolved') {
            $data['resolved_at'] = date('Y-m-d H:i:s');
        }
        if ($status === 'closed') {
            $data['closed_at'] = date('Y-m-d H:i:s');
            if ($case['resolved_at'] === null) {
                $data['resolved_at'] = date('Y-m-d H:i:s');
            }
        }
        if ($note !== null && $note !== '' && in_array($status, ['resolved','closed'], true)) {
            $data['resolution_summary'] = $note;
        }

        $this->db()->update('cases', $data, 'id = :id', ['id' => $caseId]);
        (new Report())->setStatus((int)$case['report_id'], $status);

        $this->addActivity($caseId, 'status_change', [
            'title'     => 'Status changed to ' . statusLabel($status),
            'body'      => $note,
            'old_value' => (string)$case['status'],
            'new_value' => $status,
            'is_internal' => 0,
        ]);
        Audit::log('case.status_changed', 'case', $caseId,
            $case['status'] . ' -> ' . $status, ['status' => $case['status']], ['status' => $status]);

        $this->notifyStatusChange($case, $status);
        return true;
    }

    private function notifyStatusChange(array $case, string $status): void
    {
        $report = (new Report())->find((int)$case['report_id']);
        if ($report === null) {
            return;
        }
        $label   = statusLabel($status);
        $subject = 'Update on report ' . $report['reference_no'];
        $body    = 'The status of your report is now: ' . $label . '.';

        if (!empty($report['reporter_user_id'])) {
            Notify::user((int)$report['reporter_user_id'], $subject, $body,
                'my/reports/' . $report['id'], true);
        } elseif (!empty($report['reporter_email'])) {
            Notify::reporter($report['reporter_email'], $subject,
                $body . ' Track it at ' . url('track?ref=' . $report['reference_no']));
        }

        if (in_array($status, ['escalated','resolved'], true)) {
            Notify::role('supervisor', 'Case ' . $case['case_number'] . ' ' . $label,
                'Officer action requires your attention.', 'cases/' . $case['id']);
        }
        if ($status === 'escalated') {
            Notify::role('admin', 'Case escalated: ' . $case['case_number'],
                'A case has been escalated.', 'cases/' . $case['id']);
        }
    }

    // ------------------------------------------------------------------ lists
    public function search(array $f, int $limit, int $offset): array
    {
        [$where, $params] = $this->filters($f);
        return $this->db()->all(
            "SELECT c.id, c.case_number, c.status, c.priority, c.opened_at, c.due_at, c.resolved_at,
                    r.reference_no, r.title, r.urgency,
                    rc.name AS category_name, p.name AS province_name,
                    CONCAT(o.first_name,' ',o.last_name) AS officer_name,
                    TIMESTAMPDIFF(DAY, c.opened_at, COALESCE(c.resolved_at, NOW())) AS age_days
               FROM cases c
               JOIN reports r            ON r.id = c.report_id
               JOIN report_categories rc ON rc.id = r.category_id
               LEFT JOIN provinces p     ON p.id = r.province_id
               LEFT JOIN users o         ON o.id = c.officer_id
              WHERE $where
              ORDER BY FIELD(c.priority,'critical','high','medium','low'), c.opened_at DESC
              LIMIT $limit OFFSET $offset",
            $params
        );
    }

    public function searchCount(array $f): int
    {
        [$where, $params] = $this->filters($f);
        return (int)$this->db()->scalar(
            "SELECT COUNT(*) FROM cases c
               JOIN reports r ON r.id = c.report_id
              WHERE $where", $params
        );
    }

    private function filters(array $f): array
    {
        $where  = ['c.deleted_at IS NULL'];
        $params = [];
        if (!empty($f['officer_id'])) {
            $where[]              = 'c.officer_id = :officer';
            $params['officer']    = (int)$f['officer_id'];
        }
        if (!empty($f['q'])) {
            $where[]     = '(c.case_number LIKE :q OR r.title LIKE :q OR r.reference_no LIKE :q)';
            $params['q'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['status'])) {
            if ($f['status'] === 'open') {
                $where[] = 'c.status IN ("' . implode('","', self::OPEN_STATUSES) . '")';
            } elseif (in_array($f['status'], self::STATUSES, true)) {
                $where[]          = 'c.status = :status';
                $params['status'] = $f['status'];
            }
        }
        if (!empty($f['priority'])) {
            $where[]            = 'c.priority = :priority';
            $params['priority'] = $f['priority'];
        }
        if (!empty($f['category'])) {
            $where[]       = 'r.category_id = :cat';
            $params['cat'] = (int)$f['category'];
        }
        if (!empty($f['province'])) {
            $where[]        = 'r.province_id = :prov';
            $params['prov'] = (int)$f['province'];
        }
        if (!empty($f['unassigned'])) {
            $where[] = 'c.officer_id IS NULL';
        }
        if (!empty($f['overdue'])) {
            $where[] = 'c.due_at < NOW() AND c.resolved_at IS NULL';
        }
        return [implode(' AND ', $where), $params];
    }

    // ------------------------------------------------------------- statistics
    public function stats(?int $officerId = null): array
    {
        $where  = 'deleted_at IS NULL';
        $params = [];
        if ($officerId !== null) {
            $where           .= ' AND officer_id = :officer';
            $params['officer'] = $officerId;
        }
        $open = '"' . implode('","', self::OPEN_STATUSES) . '"';
        $row  = $this->db()->one(
            "SELECT COUNT(*) AS total,
                    SUM(status IN ($open)) AS active,
                    SUM(status = 'resolved') AS resolved,
                    SUM(status = 'closed')   AS closed,
                    SUM(status = 'escalated') AS escalated,
                    SUM(officer_id IS NULL)  AS unassigned,
                    SUM(due_at < NOW() AND resolved_at IS NULL) AS overdue,
                    ROUND(AVG(CASE WHEN resolved_at IS NOT NULL
                         THEN TIMESTAMPDIFF(DAY, opened_at, resolved_at) END), 1) AS avg_resolution_days
               FROM cases WHERE $where", $params
        ) ?? [];
        $out = array_map(static fn($v) => $v === null ? 0 : (int)$v, $row);
        $out['avg_resolution_days'] = (float)($row['avg_resolution_days'] ?? 0);
        return $out;
    }

    /** Officer workload / performance table for supervisors. */
    public function officerPerformance(): array
    {
        $open = '"' . implode('","', self::OPEN_STATUSES) . '"';
        return $this->db()->all(
            "SELECT u.id, CONCAT(u.first_name,' ',u.last_name) AS officer,
                    COUNT(c.id) AS total,
                    SUM(c.status IN ($open)) AS active,
                    SUM(c.status IN ('resolved','closed')) AS completed,
                    SUM(c.due_at < NOW() AND c.resolved_at IS NULL) AS overdue,
                    ROUND(AVG(CASE WHEN c.resolved_at IS NOT NULL
                         THEN TIMESTAMPDIFF(DAY, c.opened_at, c.resolved_at) END), 1) AS avg_days
               FROM users u
               JOIN user_roles ur ON ur.user_id = u.id
               JOIN roles r       ON r.id = ur.role_id AND r.slug = 'officer'
               LEFT JOIN cases c  ON c.officer_id = u.id AND c.deleted_at IS NULL
              WHERE u.deleted_at IS NULL
              GROUP BY u.id
              ORDER BY active DESC, completed DESC"
        );
    }

    public function upcomingInterviews(?int $officerId = null, int $limit = 10): array
    {
        $limit  = max(1, min(50, $limit));
        $where  = 'a.type = "interview" AND a.scheduled_at >= NOW() AND a.deleted_at IS NULL';
        $params = [];
        if ($officerId !== null) {
            $where            .= ' AND c.officer_id = :officer';
            $params['officer'] = $officerId;
        }
        return $this->db()->all(
            "SELECT a.id, a.title, a.scheduled_at, c.id AS case_id, c.case_number, r.title AS report_title
               FROM case_activities a
               JOIN cases c   ON c.id = a.case_id
               JOIN reports r ON r.id = c.report_id
              WHERE $where
              ORDER BY a.scheduled_at ASC LIMIT $limit", $params
        );
    }
}
