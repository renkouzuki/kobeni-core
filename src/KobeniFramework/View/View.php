<?php

namespace KobeniFramework\View;

class View
{
    public static function make($view, $data = [])
    {
        $rootPath = dirname(dirname(dirname(__DIR__)));
        $viewFile = $rootPath . '/resources/views/' . $view . '.php';
        
        // var_dump([
        //     'Requested View' => $view,
        //     'Root Path' => $rootPath,
        //     'Full Path' => $viewFile,
        //     'File Exists' => file_exists($viewFile),
        //     'Current Directory' => __DIR__,
        // ]);
        
        if (!file_exists($viewFile)) {
            throw new \Exception("View not found: {$view}");
        }

        extract($data);
        
        ob_start();
        include $viewFile;
        return ob_get_clean();
    }
}