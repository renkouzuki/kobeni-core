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

        $view = new self();

        extract($data);

        ob_start();
        include $viewFile;
        return ob_get_clean();
    }

    public static function component($name, $data = [])
    {
        $className = "App\\View\\Components\\" . ucfirst($name);
        if (!class_exists($className)) {
            throw new \Exception("Component not found: {$name}");
        }

        $component = new $className($data);
        return $component->render();
    }
}