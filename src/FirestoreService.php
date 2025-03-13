<?php

namespace Frakt24\LaravelPHPFirestore;

use Frakt24\LaravelPHPFirestore\Authentication\FirestoreAuthentication;

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
     * List documents with additional parameters for pagination.
     *
     * @param string $collection
     * @param array $params
     * @return mixed
     */
    public function listDocuments(string $collection, array $params = [])
    {
        return $this->client->listDocuments($collection, $params);
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

    public function authenticator(): FirestoreAuthentication
    {
        return $this->client->authenticator();
    }

}
