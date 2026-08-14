<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Rule-string validator.
 *
 *   $v = new Validator($_POST);
 *   $v->check([
 *      'email' => 'required|email|max:190',
 *      'title' => 'required|min:5|max:200',
 *      'age'   => 'nullable|int|between:0,120',
 *   ]);
 *   if ($v->fails()) { ... $v->errors() ... }
 */
final class Validator
{
    private array $data;
    private array $errors = [];
    private array $labels = [];

    public function __construct(array $data, array $labels = [])
    {
        $this->data   = $data;
        $this->labels = $labels;
    }

    public function check(array $rules): bool
    {
        foreach ($rules as $field => $ruleString) {
            $value    = $this->data[$field] ?? null;
            $ruleList = explode('|', $ruleString);
            $nullable = in_array('nullable', $ruleList, true);

            $isEmpty = $value === null || $value === '' || (is_array($value) && $value === []);
            if ($isEmpty && $nullable) {
                continue;
            }

            foreach ($ruleList as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                if ($name === 'nullable') {
                    continue;
                }
                if ($isEmpty && $name !== 'required' && $name !== 'accepted') {
                    continue;
                }
                $this->applyRule($field, (string)$name, $param, $value);
            }
        }
        return !$this->fails();
    }

    private function applyRule(string $field, string $rule, ?string $param, mixed $value): void
    {
        $label = $this->labels[$field] ?? ucwords(str_replace('_', ' ', $field));
        $str   = is_scalar($value) ? (string)$value : '';

        switch ($rule) {
            case 'required':
                if ($value === null || $str === '' || (is_array($value) && $value === [])) {
                    $this->add($field, "$label is required.");
                }
                break;

            case 'accepted':
                if (!in_array($value, ['1', 'on', 'yes', 'true', true], true)) {
                    $this->add($field, "$label must be accepted.");
                }
                break;

            case 'email':
                if (!filter_var($str, FILTER_VALIDATE_EMAIL)) {
                    $this->add($field, "$label must be a valid email address.");
                }
                break;

            case 'url':
                if (!filter_var($str, FILTER_VALIDATE_URL)) {
                    $this->add($field, "$label must be a valid URL.");
                }
                break;

            case 'int':
                if (!preg_match('/^-?\d+$/', $str)) {
                    $this->add($field, "$label must be a whole number.");
                }
                break;

            case 'numeric':
                if (!is_numeric($str)) {
                    $this->add($field, "$label must be a number.");
                }
                break;

            case 'phone':
                if (!preg_match('/^\+?[0-9 ()-]{7,20}$/', $str)) {
                    $this->add($field, "$label must be a valid phone number.");
                }
                break;

            case 'min':
                if (mb_strlen($str) < (int)$param) {
                    $this->add($field, "$label must be at least {$param} characters.");
                }
                break;

            case 'max':
                if (mb_strlen($str) > (int)$param) {
                    $this->add($field, "$label may not exceed {$param} characters.");
                }
                break;

            case 'between':
                [$lo, $hi] = array_pad(explode(',', (string)$param), 2, '0');
                if (!is_numeric($str) || $str < (float)$lo || $str > (float)$hi) {
                    $this->add($field, "$label must be between {$lo} and {$hi}.");
                }
                break;

            case 'in':
                if (!in_array($str, explode(',', (string)$param), true)) {
                    $this->add($field, "$label contains an invalid selection.");
                }
                break;

            case 'date':
                $d = \DateTime::createFromFormat('Y-m-d', $str);
                if (!$d || $d->format('Y-m-d') !== $str) {
                    $this->add($field, "$label must be a valid date.");
                }
                break;

            case 'before_or_today':
                if (strtotime($str) > strtotime('today 23:59:59')) {
                    $this->add($field, "$label cannot be in the future.");
                }
                break;

            case 'time':
                if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $str)) {
                    $this->add($field, "$label must be a valid time.");
                }
                break;

            case 'match':
                if ($str !== (string)($this->data[(string)$param] ?? '')) {
                    $this->add($field, "$label does not match.");
                }
                break;

            case 'password':
                $min = (int)App::config('security.password_min', 10);
                if (mb_strlen($str) < $min) {
                    $this->add($field, "$label must be at least {$min} characters.");
                }
                if (!preg_match('/[A-Z]/', $str)) {
                    $this->add($field, "$label must contain an uppercase letter.");
                }
                if (!preg_match('/[a-z]/', $str)) {
                    $this->add($field, "$label must contain a lowercase letter.");
                }
                if (!preg_match('/\d/', $str)) {
                    $this->add($field, "$label must contain a number.");
                }
                if (!preg_match('/[^A-Za-z0-9]/', $str)) {
                    $this->add($field, "$label must contain a symbol.");
                }
                break;

            case 'exists':
                // exists:table,column
                [$table, $column] = array_pad(explode(',', (string)$param), 2, 'id');
                if (!preg_match('/^[a-z_]+$/', $table) || !preg_match('/^[a-z_]+$/', $column)) {
                    break; // never interpolate anything user-controlled
                }
                $found = Database::instance()->scalar(
                    "SELECT 1 FROM `$table` WHERE `$column` = ? AND deleted_at IS NULL LIMIT 1",
                    [$str]
                );
                if (!$found) {
                    $this->add($field, "$label is not a valid selection.");
                }
                break;

            case 'unique':
                // unique:table,column[,ignoreId]
                $parts  = explode(',', (string)$param);
                $table  = $parts[0] ?? '';
                $column = $parts[1] ?? 'id';
                $ignore = $parts[2] ?? null;
                if (!preg_match('/^[a-z_]+$/', $table) || !preg_match('/^[a-z_]+$/', $column)) {
                    break;
                }
                $sql    = "SELECT 1 FROM `$table` WHERE `$column` = ? AND deleted_at IS NULL";
                $params = [$str];
                if ($ignore !== null && ctype_digit((string)$ignore)) {
                    $sql .= ' AND id <> ?';
                    $params[] = (int)$ignore;
                }
                if (Database::instance()->scalar($sql . ' LIMIT 1', $params)) {
                    $this->add($field, "$label is already in use.");
                }
                break;
        }
    }

    public function add(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string,array<int,string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $messages) {
            return $messages[0] ?? null;
        }
        return null;
    }
}
