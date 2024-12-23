<?php

namespace KobeniFramework\Auth\Exceptions;

use Exception;
use Throwable;

class TokenException extends Exception {

    public function __construct(string $message = "Token error." , int $code = 401 , ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public static function expired():static {
        return new static("Token has expired." , 401);
    }

    public static function invalid():static{
        return new static("Token is invalid.", 401);
    }

    public static function blackListed():static{
        return new static('Token has been blacklisted.' , 401);
    }

    public static function missing():static{
        return new static('Token not provided.' , 401);
    }
}