<?php

namespace KobeniFramework\View;

class View
{
    public static function make($view, $data = [])
    {
        // Get the main framework root path (going up from vendor directory)
        $rootPath = dirname(dirname(dirname(dirname(dirname(__DIR__)))));
        $viewFile = $rootPath . '/resources/views/' . $view . '.php';

        // Debug view path
        echo "Looking for view at: " . $viewFile . "\n";
        echo "View exists: " . (file_exists($viewFile) ? 'Yes' : 'No') . "\n";

        if (!file_exists($viewFile)) {
            throw new \Exception("View not found: {$view}");
        }

        extract($data);

        ob_start();
        include $viewFile;
        return ob_get_clean();
    }
}
