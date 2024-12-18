<?php

if (!function_exists('required')) {
    function required(): \KobeniFramework\Validation\Rule
    {
        return \KobeniFramework\Validation\Rule::create()->required();
    }
}

if (!function_exists('optional')) {
    function optional(): \KobeniFramework\Validation\Rule
    {
        return \KobeniFramework\Validation\Rule::create();
    }
}