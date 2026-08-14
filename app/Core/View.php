<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    /**
     * Render a view inside a layout.
     * @param array<string,mixed> $data
     */
    public static function render(string $view, array $data = [], string $layout = 'public'): void
    {
        $file = App::config('app.views') . '/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$view}");
        }

        // Values are escaped at the point of output with e(), never here.
        extract($data, EXTR_SKIP);

        ob_start();
        require $file;
        $content = ob_get_clean();

        $layoutFile = App::config('app.views') . '/layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            echo $content;
            return;
        }
        require $layoutFile;
    }

    public static function partial(string $partial, array $data = []): void
    {
        $file = App::config('app.views') . '/partials/' . $partial . '.php';
        if (is_file($file)) {
            extract($data, EXTR_SKIP);
            require $file;
        }
    }

    public static function redirect(string $path, int $code = 302): never
    {
        $target = preg_match('#^https?://#i', $path) ? $path : App::url($path);
        header('Location: ' . $target, true, $code);
        exit;
    }

    public static function back(): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        // Only follow same-origin referers.
        if (is_string($referer) && str_starts_with($referer, App::baseUrl())) {
            header('Location: ' . $referer, true, 302);
            exit;
        }
        self::redirect('/');
    }

    public static function json(mixed $payload, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
