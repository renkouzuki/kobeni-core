<?php

namespace KobeniFramework\View\Components;

abstract class Component
{
    protected $data = [];

    public function __construct($data = [])
    {
        $this->data = $data;
    }

    abstract public function render();

    protected function view($view, $data = [])
    {
        $data = array_merge($this->data, $data);

        $projectRoot = dirname(getcwd());
        $viewFile = $projectRoot . '/resources/views/components/' . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new \Exception("Component view not found: {$view}");
        }

        extract($data);

        ob_start();
        include $viewFile;
        return ob_get_clean();
    }
}
