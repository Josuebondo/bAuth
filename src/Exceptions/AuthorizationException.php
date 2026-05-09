<?php

namespace BAuth\Exceptions;

/**
 * Exception levée lors d'une authorization échouée
 */
class AuthorizationException extends BAuthException
{
    public function __construct(string $message = "Unauthorized", int $code = 403)
    {
        parent::__construct($message, $code);
    }
}
