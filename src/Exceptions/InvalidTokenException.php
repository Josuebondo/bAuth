<?php

namespace Bmvc\BAuth\Exceptions;

/**
 * Exception levée quand un token est invalide
 */
class InvalidTokenException extends BAuthException
{
    public function __construct(string $message = "Invalid token", int $code = 401)
    {
        parent::__construct($message, $code);
    }
}
