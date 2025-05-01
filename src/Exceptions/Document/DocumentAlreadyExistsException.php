<?php

namespace Frakt24\LaravelPHPFirestore\Exceptions\Document;

use Frakt24\LaravelPHPFirestore\Exceptions\FirestoreException;

class DocumentAlreadyExistsException extends FirestoreException
{
    private $documentPath;

    public function __construct(string $documentPath, string $message = '', array $errorData = [], ?\Throwable $previous = null)
    {
        $message = $message ?: "Document already exists at path: {$documentPath}";
        parent::__construct($message, 409, $errorData, $previous);
        $this->documentPath = $documentPath;
    }

    public function getDocumentPath(): string
    {
        return $this->documentPath;
    }
}
