<?php

namespace BAuth\Exceptions;

/**
 * Exception levée lors d'une authentification échouée
 */
class AuthenticationException extends BAuthException
{
    public function __construct(string $message = "Authentication failed", int $code = 401)
    {
        parent::__construct($message, $code);
    }
}
