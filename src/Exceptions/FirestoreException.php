<?php

namespace Frakt24\LaravelPHPFirestore\Exceptions;

class FirestoreException extends \Exception
{
    protected $errorData;

    public function __construct(string $message, int $code = 0, array $errorData = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorData = $errorData;
    }

    public function getErrorData(): array
    {
        return $this->errorData;
    }
}
