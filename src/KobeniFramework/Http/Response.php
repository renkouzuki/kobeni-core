<?php

namespace KobeniFramework\Http;

class Response
{
    public static function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        return json_encode($data);
    }
}