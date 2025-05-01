<?php

namespace Frakt24\LaravelPHPFirestore\Exceptions\Authentication;

use Frakt24\LaravelPHPFirestore\Exceptions\FirestoreException;

class AuthenticationException extends FirestoreException
{
    public function __construct(string $message = 'Authentication failed', array $errorData = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 401, $errorData, $previous);
    }
}
