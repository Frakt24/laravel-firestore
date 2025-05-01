<?php

namespace Frakt24\LaravelPHPFirestore\Exceptions\Batch;

use Frakt24\LaravelPHPFirestore\Exceptions\FirestoreException;

class BatchSizeExceededException extends FirestoreException
{
    private $currentSize;
    private $maxSize;

    public function __construct(int $currentSize, int $maxSize, string $message = '', array $errorData = [], ?\Throwable $previous = null)
    {
        $message = $message ?: "Batch size ({$currentSize}) exceeds maximum allowed size ({$maxSize})";
        parent::__construct($message, 400, $errorData, $previous);
        $this->currentSize = $currentSize;
        $this->maxSize = $maxSize;
    }

    public function getCurrentSize(): int
    {
        return $this->currentSize;
    }

    public function getMaxSize(): int
    {
        return $this->maxSize;
    }
}
