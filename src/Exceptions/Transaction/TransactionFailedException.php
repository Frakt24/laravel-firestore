<?php

namespace Frakt24\LaravelPHPFirestore\Exceptions\Transaction;

use Frakt24\LaravelPHPFirestore\Exceptions\FirestoreException;

class TransactionFailedException extends FirestoreException
{
    private $transactionId;

    public function __construct(string $transactionId, string $message = '', array $errorData = [], ?\Throwable $previous = null)
    {
        $message = $message ?: "Transaction failed: {$transactionId}";
        parent::__construct($message, 500, $errorData, $previous);
        $this->transactionId = $transactionId;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }
}
