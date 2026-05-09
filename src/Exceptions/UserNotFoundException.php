<?php

namespace Bmvc\BAuth\Exceptions;

/**
 * Exception levée quand un utilisateur n'est pas trouvé
 */
class UserNotFoundException extends BAuthException
{
    public function __construct(string $message = "User not found", int $code = 404)
    {
        parent::__construct($message, $code);
    }
}
