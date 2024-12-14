<?php

namespace KobeniFramework\View;

class View
{
    public static function make($view, $data = [])
    {
        $projectRoot = dirname(getcwd());

        $viewFile = $projectRoot . '/resources/views/' . $view . '.php';

        // echo "Looking for view at: " . $viewFile . "\n";
        // echo "View exists: " . (file_exists($viewFile) ? 'Yes' : 'No') . "\n";

        if (!file_exists($viewFile)) {
            throw new \Exception("View not found: {$view}");
        }

        extract($data);

        ob_start();
        include $viewFile;
        return ob_get_clean();
    }
}
