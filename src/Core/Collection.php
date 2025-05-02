<?php

namespace Frakt24\LaravelPHPFirestore\Core;

use Frakt24\LaravelPHPFirestore\Contracts\FirestoreCollection as FirestoreCollectionContract;
use Frakt24\LaravelPHPFirestore\Contracts\FirestoreDocument;
use Frakt24\LaravelPHPFirestore\Core\Document;
use Frakt24\LaravelPHPFirestore\Core\Query as FirestoreQuery;
use Frakt24\LaravelPHPFirestore\Core\Batch;
use Frakt24\LaravelPHPFirestore\Core\DatabaseResource as FirestoreDatabaseResource;
use Frakt24\LaravelPHPFirestore\Core\Snapshot as FirestoreSnapshot;
use Frakt24\LaravelPHPFirestore\Core\StructuredQuery as FirestoreStructuredQuery;
use Frakt24\LaravelPHPFirestore\Core\AggregationQuery as FirestoreAggregationQuery;

class Collection implements FirestoreCollectionContract
{
    /**
     * @var array
     */
    private $metadata = [];

    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var \Frakt24\LaravelPHPFirestore\FirestoreDatabaseResource
     */
    protected $databaseResource;

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
     * Collection constructor
     *
     * @param array $data Collection data from API
     * @param string $path Collection path
     * @param FirestoreDatabaseResource $databaseResource
     */
    public function __construct(array $data, string $path, FirestoreDatabaseResource $databaseResource)
    {
        $this->data = $data;
        $this->path = $path;
        $this->databaseResource = $databaseResource;
        $this->nextPageToken = $data['nextPageToken'] ?? null;
        $this->readTime = $data['readTime'] ?? null;

        if (isset($data['documents'])) {
            foreach ($data['documents'] as $document) {
                $this->documents[] = new Document($document, $databaseResource);
            }
        }
    }

    /**
     * Get the collection path
     *
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Add a document to the collection
     *
     * @param array|Document $data
     * @param string|null $documentId
     * @return FirestoreDocument
     */
    public function add($data, ?string $documentId = null): FirestoreDocument
    {
        if ($data instanceof Document) {
            $data = $data->data();
        }

        $response = $this->databaseResource->addDocument($this->path, $data, $documentId);
        return new Document(
            $response['name'],
            $this->path . '/' . ($documentId ?? basename($response['name'])),
            $response,
            $this->databaseResource
        );
    }

    /**
     * Add a document with automatic timestamps
     *
     * @param array|Document $data
     * @param string|null $documentId
     * @return FirestoreDocument
     */
    public function addWithTimestamp($data, ?string $documentId = null): FirestoreDocument
    {
        $data['created_at'] = ['timestampValue' => date('c')];
        $data['updated_at'] = ['timestampValue' => date('c')];
        return $this->add($data, $documentId);
    }

    /**
     * Get a document by ID
     *
     * @param string $id
     * @return FirestoreDocument
     */
    public function document(string $id): FirestoreDocument
    {
        $doc = $this->databaseResource->getDocument("{$this->path}/{$id}");
        if (!$doc) {
            throw new \RuntimeException("Document {$id} not found in collection {$this->path}");
        }
        return $doc;
    }

    /**
     * Create a new query on the collection
     *
     * @return FirestoreQuery
     */
    public function query(): FirestoreQuery
    {
        return (new FirestoreQuery())->from($this->path);
    }

    /**
     * Get a list of documents in the collection
     *
     * @param int $pageSize
     * @return FirestoreSnapshot
     */
    public function list(int $pageSize = 100): FirestoreSnapshot
    {
        $response = $this->databaseResource->getClient()->request('GET', $this->path, [
            'query' => [
                'pageSize' => $pageSize
            ]
        ]);
        
        return new FirestoreSnapshot($response, $this->databaseResource);
    }

    /**
     * Get a batch writer for the collection
     *
     * @return \Frakt24\LaravelPHPFirestore\Contracts\FirestoreBatch
     */
    public function batch(): \Frakt24\LaravelPHPFirestore\Contracts\FirestoreBatch
    {
        return new Batch($this->databaseResource->getClient());
    }

    /**
     * Start a transaction on the collection
     *
     * @param callable $callback
     * @return mixed
     */
    public function transaction(callable $callback)
    {
        return $this->databaseResource->getClient()->runTransaction($callback);
    }

    /**
     * Get a subcollection
     *
     * @param string $path
     * @return FirestoreCollectionContract
     */
    public function collection(string $path): FirestoreCollectionContract
    {
        $fullPath = "{$this->path}/{$path}";
        return $this->databaseResource->collection($fullPath);
    }

    /**
     * Listen for real-time updates
     *
     * @param callable $callback
     * @param array $options
     * @throws \RuntimeException
     */
    public function listen(callable $callback, array $options = [])
    {
        // Implementation depends on your real-time update strategy
        // This could use WebSockets, Server-Sent Events, or long polling
        throw new \RuntimeException('Real-time updates not implemented yet');
    }

    /**
     * Get all documents in the collection
     *
     * @return array
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
     * Get the database resource
     *
     * @return FirestoreDatabaseResource
     */
    public function getDatabaseResource(): FirestoreDatabaseResource
    {
        return $this->databaseResource;
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
     * Run a structured query
     *
     * @param FirestoreStructuredQuery $query
     * @param array $options
     * @return array
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
            return new Document($result['document'], $this->databaseResource);
        }, $response);
    }

    /**
     * Run an aggregation query
     *
     * @param FirestoreAggregationQuery $query
     * @param array $options
     * @return array
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
     * @param string $documentId
     * @param array $options
     * @return bool
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
}
