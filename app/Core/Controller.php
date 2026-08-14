<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'public'): void
    {
        $data += [
            'errors' => Flash::errors(),
            'old'    => Flash::old(),
        ];
        View::render($view, $data, $layout);
    }

    protected function redirect(string $path): never
    {
        View::redirect($path);
    }

    /**
     * Render an error page with the matching HTTP status and stop.
     * Always use this rather than rendering an error view directly, so the
     * status code and the page never disagree.
     */
    protected function fail(int $code, string $message, string $layout = 'app'): never
    {
        http_response_code($code);
        if (Request::wantsJson()) {
            View::json(['error' => $message], $code);
        }
        View::render('errors/error', ['code' => $code, 'message' => $message], $layout);
        exit;
    }

    protected function json(mixed $payload, int $code = 200): never
    {
        View::json($payload, $code);
    }

    /** Validate POST input; on failure flash errors and bounce back. */
    protected function validate(array $rules, array $labels = [], ?string $backTo = null): array
    {
        $validator = new Validator($_POST, $labels);
        if ($validator->check($rules)) {
            return array_intersect_key($_POST, $rules);
        }
        $old = $_POST;
        unset($old['password'], $old['password_confirmation'], $old['_token']);
        Flash::withErrors($validator->errors(), $old);
        Flash::error($validator->firstError() ?? 'Please correct the highlighted fields.');
        if ($backTo !== null) {
            View::redirect($backTo);
        }
        View::back();
    }

    protected function paginate(int $total, ?int $perPage = null): array
    {
        $perPage = $perPage ?? (int)App::config('pagination.per_page', 15);
        $pages   = max(1, (int)ceil($total / $perPage));
        $page    = min(Request::page(), $pages);
        return [
            'page'    => $page,
            'pages'   => $pages,
            'perPage' => $perPage,
            'offset'  => ($page - 1) * $perPage,
            'total'   => $total,
        ];
    }
}
