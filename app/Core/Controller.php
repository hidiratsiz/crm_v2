<?php

namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        echo View::render($view, $data);
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . Url::to($path));
        exit;
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}
