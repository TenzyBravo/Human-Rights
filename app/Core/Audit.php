<?php
declare(strict_types=1);

namespace App\Core;

final class Audit
{
    /** Keys never written to the audit trail in clear text. */
    private const REDACT = ['password', 'password_hash', 'password_confirmation', 'token',
                            'remember_token', 'verify_token', 'twofa_secret', 'national_id',
                            'national_id_enc', '_token'];

    public static function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null,
        ?array $old = null,
        ?array $new = null
    ): void {
        try {
            Database::instance()->insert('audit_logs', [
                'user_id'     => Auth::id(),
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'description' => $description === null ? null : mb_substr($description, 0, 500),
                'old_values'  => self::encode($old),
                'new_values'  => self::encode($new),
                'ip_address'  => Request::ip(),
                'user_agent'  => Request::userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Auditing must never break the request it is recording.
            App::log('audit', 'Failed to write audit entry (' . $action . '): ' . $e->getMessage());
        }
    }

    public static function activity(string $summary): void
    {
        try {
            Database::instance()->insert('activity_logs', [
                'user_id'    => Auth::id(),
                'summary'    => mb_substr($summary, 0, 300),
                'route'      => Request::path(),
                'method'     => Request::method(),
                'ip_address' => Request::ip(),
            ]);
        } catch (\Throwable $e) {
            App::log('audit', 'Failed to write activity entry: ' . $e->getMessage());
        }
    }

    private static function encode(?array $values): ?string
    {
        if ($values === null) {
            return null;
        }
        foreach (array_keys($values) as $key) {
            if (in_array(strtolower((string)$key), self::REDACT, true)) {
                $values[$key] = '[redacted]';
            }
        }
        return json_encode($values, JSON_UNESCAPED_UNICODE) ?: null;
    }
}
