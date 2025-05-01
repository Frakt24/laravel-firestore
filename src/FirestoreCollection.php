<?php

namespace Frakt24\LaravelPHPFirestore;

use Frakt24\LaravelPHPFirestore\Exceptions\Client\InvalidPathProvided;
use Frakt24\LaravelPHPFirestore\Helpers\FirestoreHelper;

class FirestoreCollection
{
    /**
     * @var array
     */
    private $metadata = [];

    /**
     * @var string
     */
    private $path;

    /**
     * @var \Frakt24\LaravelPHPFirestore\FirestoreDatabaseResource
     */
    private $databaseResource;

    /**
     * @var array
     */
    private $documents = [];

    /**
     * @var string|null
     */
    private $nextPageToken;

    /**
     * @var string|null
     */
    private $readTime;

    /**
     * FirestoreCollection constructor
     *
     * @param array $data Collection data from API
     * @param string $path Collection path
     * @param FirestoreDatabaseResource $databaseResource
     */
    public function __construct(array $data, string $path, FirestoreDatabaseResource $databaseResource)
    {
        $this->path = $path;
        $this->databaseResource = $databaseResource;
        $this->nextPageToken = $data['nextPageToken'] ?? null;
        $this->readTime = $data['readTime'] ?? null;

        if (isset($data['documents'])) {
            foreach ($data['documents'] as $document) {
                $this->documents[] = new FirestoreDocument($document, $databaseResource);
            }
        }
    }

    /**
     * Get the collection path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get all documents in the collection
     *
     * @return FirestoreDocument[]
     */
    public function getDocuments(): array
    {
        return $this->documents;
    }

    /**
     * Get the next page token if available
     *
     * @return string|null
     */
    public function getNextPageToken(): ?string
    {
        return $this->nextPageToken;
    }

    /**
     * Get the read time of the collection
     *
     * @return string|null
     */
    public function getReadTime(): ?string
    {
        return $this->readTime;
    }

    /**
     * Check if the collection has more documents
     *
     * @return bool
     */
    public function hasMore(): bool
    {
        return $this->nextPageToken !== null;
    }

    /**
     * Get the number of documents in the current page
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->documents);
    }

    /**
     * Add a new document to the collection
     *
     * @param array|FirestoreDocument $data
     * @param string|null $documentId
     * @return FirestoreDocument
     */
    public function addDocument($data, ?string $documentId = null): FirestoreDocument
    {
        return $this->databaseResource->addDocument($this->path, $data, $documentId);
    }

    /**
     * Get document by ID
     *
     * @param string $documentId
     * @return FirestoreDocument|null
     */
    public function getDocument(string $documentId): ?FirestoreDocument
    {
        return $this->databaseResource->getDocument("{$this->path}/{$documentId}");
    }

    /**
     * List subcollections of this collection
     *
     * @param array $options Listing options
     * @return array
     */
    public function listSubcollections(array $options = []): array
    {
        return $this->databaseResource->listCollections($this->path, $options);
    }

    /**
     * Create a new query for this collection
     *
     * @return FirestoreStructuredQuery
     */
    public function query(): FirestoreStructuredQuery
    {
        return (new FirestoreStructuredQuery())->from($this->path);
    }

    /**
     * Run a structured query
     *
     * @param FirestoreStructuredQuery $query
     * @param array $options Query options (e.g., readTime)
     * @return array Query results
     */
    public function runQuery(FirestoreStructuredQuery $query, array $options = []): array
    {
        $path = "projects/{$this->databaseResource->getClient()->getConfig('projectId')}/databases/(default)/documents";
        
        $response = $this->databaseResource->getClient()->request('POST', "{$path}:runQuery", [
            'json' => array_merge(
                ['structuredQuery' => $query->build()],
                $options
            )
        ]);

        return array_map(function($result) {
            return new FirestoreDocument($result['document'], $this->databaseResource);
        }, $response);
    }

    /**
     * Run an aggregation query
     *
     * @param FirestoreAggregationQuery $query
     * @param array $options Query options (e.g., readTime)
     * @return array Aggregation results
     */
    public function runAggregationQuery(FirestoreAggregationQuery $query, array $options = []): array
    {
        $path = "projects/{$this->databaseResource->getClient()->getConfig('projectId')}/databases/(default)/documents";
        
        $response = $this->databaseResource->getClient()->request('POST', "{$path}:runAggregationQuery", [
            'json' => array_merge(
                $query->build(),
                $options
            )
        ]);

        return $response['result'] ?? [];
    }

    /**
     * Check if a document exists in this collection
     *
     * @param string $documentId Document ID to check
     * @param array $options Additional options including:
     *                      - readTime: Read state at this time (RFC 3339 format)
     * @return bool True if the document exists
     */
    public function documentExists(string $documentId, array $options = []): bool
    {
        try {
            $path = "projects/{$this->databaseResource->getClient()->getConfig('projectId')}/databases/(default)/documents/{$this->path}/{$documentId}";
            
            $response = $this->databaseResource->getClient()->request('GET', $path, [
                'query' => array_filter([
                    'mask.fieldPaths' => '__name__', // Only fetch the document name to minimize data transfer
                    'readTime' => $options['readTime'] ?? null
                ])
            ]);
            
            return isset($response['name']);
        } catch (\Exception $e) {
            // If we get a 404 or any other error, the document doesn't exist
            return false;
        }
    }

    /**
     * Get the database resource
     *
     * @return FirestoreDatabaseResource
     */
    public function getDatabaseResource(): FirestoreDatabaseResource
    {
        return $this->databaseResource;
    }
}
