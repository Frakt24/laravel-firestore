<?php

namespace Frakt24\LaravelPHPFirestore\Exceptions\Query;

use Frakt24\LaravelPHPFirestore\Exceptions\FirestoreException;

class QueryException extends FirestoreException
{
    private $query;

    public function __construct(array $query, string $message, array $errorData = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 400, $errorData, $previous);
        $this->query = $query;
    }

    public function getQuery(): array
    {
        return $this->query;
    }
}
