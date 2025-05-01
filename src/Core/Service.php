<?php

namespace Frakt24\LaravelPHPFirestore\Core;

use Frakt24\LaravelPHPFirestore\Auth\FirestoreCredentials;

class FirestoreService
{
    protected FirestoreClient $client;

    public function __construct(array $config = [])
    {
        $projectId = $config['project_id'] ?? '';
        $apiKey = $config['api_key'] ?? '';
        $options = $config['options'] ?? ['database' => '(default)'];

        $this->client = new FirestoreClient($projectId, $apiKey, $options);
    }

    /**
     * Add a document to a collection.
     *
     * @param string $collection
     * @param array|FirestoreDocument $data
     * @param string|null $documentId   Optional custom document ID.
     * @return mixed
     */
    public function addDocument(string $collection, $data, ?string $documentId = null)
    {
        return $this->client->addDocument($collection, $data, $documentId);
    }

    /**
     * Update (or merge) a document.
     *
     * @param string $documentPath Format: "<collection>/<documentID>"
     * @param array $data
     * @param bool $checkExists  Force document must exist.
     * @return mixed
     */
    public function updateDocument(
        string $documentPath,
        array $data,
        bool $checkExists = false
    ) {
        return $this->client->updateDocument($documentPath, $data, $checkExists);
    }

    /**
     * Set (overwrite/insert) a document.
     *
     * @param string $documentPath
     * @param array|FirestoreDocument $payload
     * @param bool $documentExists
     * @param array $parameters
     * @param array $options Options such as ['exists' => true]
     * @return mixed
     */
    public function setDocument(
        string $documentPath,
        array|FirestoreDocument $payload,
        bool $documentExists,
        array $parameters = [],
        array $options = []
    ) {
        return $this->client->setDocument($documentPath, $payload, $documentExists, $parameters, $options);
    }

    /**
     * Delete a document.
     *
     * @param string $document
     * @param array $options
     * @return mixed
     */
    public function deleteDocument(string $document, array $options = [])
    {
        return $this->client->deleteDocument($document, $options);
    }

    /**
     * List documents in a collection with pagination support
     *
     * @param string $collectionPath Path to the collection
     * @param array $options Additional options including:
     *                      - pageSize: The maximum number of documents to return
     *                      - pageToken: The token for the next page
     *                      - orderBy: The order to sort results by
     *                      - mask: The fields to return in the response
     *                      - readTime: Read state at this time (RFC 3339 format)
     * @return FirestoreCollection
     */
    public function listDocuments(string $collectionPath, array $options = []): FirestoreCollection
    {
        return $this->client->listDocuments($collectionPath, $options);
    }

    /**
     * Get a specific document
     *
     * @param string $collection
     * @param array $parameters
     * @param array $options
     * @return FirestoreDocument
     */
    public function getDocument(string $collection, array $parameters = [], array $options= []): FirestoreDocument
    {
        return $this->client->getDocument($collection, $parameters, $options);
    }

    /**
     * Delete an entire collection and all its documents recursively
     *
     * @param string $collectionPath Path to the collection
     * @param int $batchSize Number of documents to delete in each batch
     * @return bool True if successful
     */
    public function deleteCollection(string $collectionPath, int $batchSize = 100): bool
    {
        $documents = $this->listDocuments($collectionPath, ['pageSize' => $batchSize]);
        
        if (empty($documents)) {
            return true;
        }

        foreach ($documents as $document) {
            // Delete any nested collections
            $nestedCollections = $this->client->request('GET', "{$document['name']}/collectionIds");
            foreach ($nestedCollections as $nestedCollection) {
                $this->deleteCollection("{$document['name']}/{$nestedCollection}", $batchSize);
            }
            
            // Delete the document
            $this->deleteDocument($document['name']);
        }

        // Check if there are more documents to delete
        $nextDocuments = $this->listDocuments($collectionPath, ['pageSize' => 1]);
        if (!empty($nextDocuments)) {
            return $this->deleteCollection($collectionPath, $batchSize);
        }

        return true;
    }

    /**
     * Get metadata about a collection including document count and existence
     *
     * @param string $collectionPath Path to the collection
     * @param array $options Additional options including:
     *                      - pageSize: Number of documents to count (default: 1000)
     *                      - readTime: Read state at this time (RFC 3339 format)
     * @return array Collection metadata
     */
    public function getCollectionMetadata(string $collectionPath, array $options = []): array
    {
        return $this->client->getCollectionMetadata($collectionPath, $options);
    }

    /**
     * Check if a collection exists and has documents
     *
     * @param string $collectionPath Path to the collection
     * @param array $options Additional options including:
     *                      - readTime: Read state at this time (RFC 3339 format)
     * @return bool True if the collection exists and has documents
     */
    public function collectionExists(string $collectionPath, array $options = []): bool
    {
        return $this->client->collectionExists($collectionPath, $options);
    }

    /**
     * List all collections at the root level or under a specific document path
     *
     * @param string|null $parentPath Optional parent document path
     * @param array $options Additional options including:
     *                      - pageSize: The maximum number of results to return
     *                      - pageToken: Token from a previous request for pagination
     *                      - readTime: Reads collections as they were at the given time (RFC 3339 format)
     * @return array ['collectionIds' => string[], 'nextPageToken' => string|null]
     */
    public function listCollections(?string $parentPath = null, array $options = []): array
    {
        return $this->client->listCollections($parentPath, $options);
    }

    /**
     * Run an aggregation query against the Firestore database
     *
     * @param string $parent The parent resource name
     * @param array $query The aggregation query to run
     * @return array The aggregation results
     */
    public function runAggregationQuery(string $parent, array $query): array
    {
        try {
            $response = $this->client->request('POST', "{$parent}:runAggregationQuery", [
                'json' => ['structuredAggregationQuery' => $query]
            ]);
            return $response ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Begin a new transaction
     *
     * @param array $options Transaction options
     * @return string Transaction ID
     */
    public function beginTransaction(array $options = []): string
    {
        try {
            $response = $this->client->request('POST', "projects/{$this->client->getConfig('projectId')}/databases/(default)/documents:beginTransaction", [
                'json' => $options
            ]);
            return $response['transaction'] ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Commit a transaction
     *
     * @param string $transaction Transaction ID
     * @param array $writes Array of write operations
     * @return array Commit response
     */
    public function commitTransaction(string $transaction, array $writes): array
    {
        try {
            $response = $this->client->request('POST', "projects/{$this->client->getConfig('projectId')}/databases/(default)/documents:commit", [
                'json' => [
                    'transaction' => $transaction,
                    'writes' => $writes
                ]
            ]);
            return $response ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Rollback a transaction
     *
     * @param string $transaction Transaction ID
     * @return bool Success status
     */
    public function rollbackTransaction(string $transaction): bool
    {
        try {
            $this->client->request('POST', "projects/{$this->client->getConfig('projectId')}/databases/(default)/documents:rollback", [
                'json' => ['transaction' => $transaction]
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Batch get multiple documents
     *
     * @param array $documentPaths Array of document paths to retrieve
     * @param array $options Additional options like transaction, newTransaction, etc.
     * @return array Array of documents
     */
    public function batchGetDocuments(array $documentPaths, array $options = []): array
    {
        try {
            $response = $this->client->request('POST', "projects/{$this->client->getConfig('projectId')}/databases/(default)/documents:batchGet", [
                'json' => array_merge([
                    'documents' => $documentPaths
                ], $options)
            ]);
            return $response ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Batch write multiple documents
     *
     * @param array $writes Array of write operations
     * @param array $options Additional options
     * @return array Write results
     */
    public function batchWriteDocuments(array $writes, array $options = []): array
    {
        try {
            $response = $this->client->request('POST', "projects/{$this->client->getConfig('projectId')}/databases/(default)/documents:batchWrite", [
                'json' => array_merge([
                    'writes' => $writes
                ], $options)
            ]);
            return $response ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Partition a query for parallel processing
     *
     * @param string $parent Parent resource name
     * @param array $query The structured query
     * @param int $partitionCount Desired number of partitions
     * @return array Array of partition cursors
     */
    public function partitionQuery(string $parent, array $query, int $partitionCount): array
    {
        try {
            $response = $this->client->request('POST', "{$parent}:partitionQuery", [
                'json' => [
                    'structuredQuery' => $query,
                    'partitionCount' => (string) $partitionCount
                ]
            ]);
            return $response['partitions'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if a document exists at the given path
     *
     * @param string $documentPath Full path to the document
     * @param array $options Additional options including:
     *                      - readTime: Read state at this time (RFC 3339 format)
     * @return bool True if the document exists
     */
    public function documentExists(string $documentPath, array $options = []): bool
    {
        try {
            $path = "projects/{$this->client->getConfig('projectId')}/databases/(default)/documents/{$documentPath}";
            
            $response = $this->client->request('GET', $path, [
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

    public function authenticator(): FirestoreAuthentication
    {
        return $this->client->authenticator();
    }

}
