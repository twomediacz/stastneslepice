<?php

namespace App\Core;

class View
{
    public static function render(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data);

        ob_start();
        require __DIR__ . '/../Views/pages/' . $view . '.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/' . $layout . '.php';
    }
}
