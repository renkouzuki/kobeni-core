<?php

namespace KobeniFramework\Middleware;

abstract class Middleware
{
    abstract public function handle($next);
}