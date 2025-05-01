<?php

namespace Frakt24\LaravelPHPFirestore;

class FirestoreDatabase
{
    /**
     * @var FirestoreClient
     */
    private $client;

    /**
     * @var string
     */
    private $databaseId;

    public function __construct(FirestoreClient $client, string $databaseId = '(default)')
    {
        $this->client = $client;
        $this->databaseId = $databaseId;
    }

    /**
     * Export documents to Google Cloud Storage
     *
     * @param array $options Export options including:
     *                      - collectionIds: Which collection ids to export
     *                      - outputUriPrefix: The output URI prefix in Google Cloud Storage
     * @return array Operation details
     */
    public function exportDocuments(array $options): array
    {
        $path = "projects/{$this->client->getConfig('projectId')}/databases/{$this->databaseId}:exportDocuments";
        return $this->client->request('POST', $path, ['json' => $options]);
    }

    /**
     * Import documents from Google Cloud Storage
     *
     * @param array $options Import options including:
     *                      - collectionIds: Which collection ids to import
     *                      - inputUriPrefix: Location of the exported files in Google Cloud Storage
     * @return array Operation details
     */
    public function importDocuments(array $options): array
    {
        $path = "projects/{$this->client->getConfig('projectId')}/databases/{$this->databaseId}:importDocuments";
        return $this->client->request('POST', $path, ['json' => $options]);
    }

    /**
     * Bulk delete documents
     *
     * @param array $documentPaths Array of document paths to delete
     * @return array Operation details
     */
    public function bulkDeleteDocuments(array $documentPaths): array
    {
        $path = "projects/{$this->client->getConfig('projectId')}/databases/{$this->databaseId}:bulkDeleteDocuments";
        return $this->client->request('POST', $path, [
            'json' => ['documentPaths' => $documentPaths]
        ]);
    }

    /**
     * Get database information
     *
     * @return array Database details
     */
    public function getDatabaseInfo(): array
    {
        $path = "projects/{$this->client->getConfig('projectId')}/databases/{$this->databaseId}";
        return $this->client->request('GET', $path);
    }

    /**
     * List all databases in the project
     *
     * @return array List of databases
     */
    public function listDatabases(): array
    {
        $path = "projects/{$this->client->getConfig('projectId')}/databases";
        return $this->client->request('GET', $path);
    }
}
