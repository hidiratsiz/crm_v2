<?php

namespace App\Core;

class View
{
    public static function render(string $view, array $data = []): string
    {
        extract($data);
        $viewPath = __DIR__ . '/../../resources/views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            http_response_code(500);
            return "View not found: {$view}";
        }

        ob_start();
        require $viewPath;
        return ob_get_clean();
    }

    /**
     * Renders a view inside the shared layout (header/sidebar/footer).
     */
    public static function renderWithLayout(string $view, array $data = []): string
    {
        $content = self::render($view, $data);
        return self::render('layouts/app', array_merge($data, ['content' => $content]));
    }
}
