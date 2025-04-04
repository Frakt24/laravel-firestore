<?php
namespace Frakt24\LaravelPHPFirestore;

use Frakt24\LaravelPHPFirestore\Exceptions\Client\InvalidPathProvided;
use Frakt24\LaravelPHPFirestore\Exceptions\Client\NotFound;
use Frakt24\LaravelPHPFirestore\FirestoreDocument;
use Frakt24\LaravelPHPFirestore\FirestoreClient;
use Frakt24\LaravelPHPFirestore\Helpers\FirestoreHelper;

class FirestoreDatabaseResource
{
    /**
     * @var \Frakt24\LaravelPHPFirestore\FirestoreClient
     */
    private $client;

    /**
     * @var array
     */
    private $options = [];

    public function __construct(FirestoreClient $client) {
        $this->client = $client;
    }

    /**
     * To validate parity of slashes in path
     *
     * @param string $path
     * @param boolean $shouldBeOdd
     *
     * @throws \Frakt24\LaravelPHPFirestore\Exceptions\Client\InvalidPathProvided
     *
     * @return boolean
     */
    private function validatePath($path, $shouldBeOdd = true)
    {
        $slashesCount = substr_count($path, '/');
        $isEvenNumber = $slashesCount % 2 == 0;



        if (($shouldBeOdd && $isEvenNumber) || (!$shouldBeOdd && !$isEvenNumber)) {
            throw new InvalidPathProvided($path);
        }

        return true;
    }

    /**
     * List all documents in collection path given
     *
     * @param string $collection
     * @param array $parameters
     * @param array $options
     *
     * @return array
     */
    public function listDocuments($collection, array $parameters = [], array $options = [])
    {
        $this->validatePath($collection, false);

        $response = $this->client->request('GET', 'documents/' . FirestoreHelper::normalizeCollection($collection), $options, $parameters);

        if (isset($response['documents'])) {
            $documents = array_map(function($doc) {
                return new FirestoreDocument($doc);
            }, $response['documents']);
        } else {
            $documents = [];
        }

        return array_merge($response, [
            'documents' => $documents,
        ]);
    }

    /**
     * Get document object by given path
     *
     * @param string $documentPath
     * @param array $parameters
     * @param array $options
     *
     * @return \Frakt24\LaravelPHPFirestore\FirestoreDocument
     */
    public function getDocument($documentPath, array $parameters = [], array $options = [])
    {
        $this->validatePath($documentPath);

        $documentPath = 'documents/' . FirestoreHelper::normalizeCollection($documentPath);
        $document     = $this->client->request('GET', $documentPath, $options, $parameters);

        if ( FirestoreDocument::isValidDocument($document) ) {
            return new FirestoreDocument($document, $this);
        }

        throw new NotFound((string) $this->client->getLastResponse()->getBody());
    }

    /**
     * Get batch (multiple) documents
     *
     * @param array $documentsId
     * @param array $parameters
     * @param array $options
     *
     * @return array
     */
    public function getBatchDocuments(array $documentsId, array $parameters = [], array $options = [])
    {
        // Validates all documents provided
        $documentsPath = array_map(function($doc) {
            $this->validatePath($doc);

            return $this->client->getRelativeDatabasePath() . '/documents/' . FirestoreHelper::normalizeCollection($doc);
        }, $documentsId);

        $response = $this->client->request('POST', 'documents:batchGet', array_merge($options, [
            'json' => [
                'documents' => $documentsPath,
            ]
        ]), $parameters);

        $documentBasePathLength = strlen($this->client->getRelativeDatabasePath() . '/documents/');
        $results = [
            'documents' => [],
            'missing' => [],
        ];

        foreach ($response as $doc) {
            if (array_key_exists('found', $doc)) {
                $results['documents'][] = new FirestoreDocument($doc['found']);
            } else {
                $results['missing'][] = substr($doc['missing'], $documentBasePathLength);
            }
        }

        return $results;
    }

    /**
     * Insert document under collection path given
     *
     * @param string $collection
     * @param array|FirestoreDocument $payload
     * @param string|null $documentId
     * @param array $parameters
     * @param array $options
     *
     * @return \Frakt24\LaravelPHPFirestore\FirestoreDocument
     */
    public function addDocument($collection, $payload, $documentId = null, array $parameters = [], array $options = [])
    {
        $this->validatePath($collection, false);

        if (!($payload instanceof FirestoreDocument)) {
            $document = new FirestoreDocument();
            if (is_array($payload)) {
                $document->fillValues($payload);
            }
        } else {
            $document = $payload;
        }

        if ($documentId) {
            $parameters['documentId'] = $documentId;
        }

        $jsonBody = $document->toJson();

        if (empty($jsonBody) || $jsonBody === 'null') {
            error_log("toJson() produced empty or null JSON. Document fields: " . print_r($document->toArray(), true));
            throw new \RuntimeException("toJson() produced invalid or empty JSON.");
        }

        $requestOptions = array_merge($options, [
            'body' => $jsonBody,
        ]);

        unset($requestOptions['json']);


        $response = $this->client->request(
            'POST',
            'documents/' . FirestoreHelper::normalizeCollection($collection),
            $requestOptions,
            $parameters
        );

        return new FirestoreDocument($response);
    }

    /**
     * Insert a document under collection path given, When you want to add an array with nested
     * it makes the nested arrays a FirestoreObject
     *
     *
     * @param string $documentPath
     * @param array|FirestoreDocument $payload
     * @param string|null $documentId
     * @param array $parameters
     * @param array $options
     *
     * @return \Frakt24\LaravelPHPFirestore\FirestoreDocument
     */
    public function addNestedDocuments($documentPath, $payload, $documentId = null, array $parameters = [], array $options = []): \Frakt24\LaravelPHPFirestore\FirestoreDocument
    {
        $payload = FirestoreHelper::normalizedNestedArray($payload);
        return $this->addDocument($documentPath, $payload, $documentId , $parameters,$options);
    }

    /**
     * It'll merge document with existing one or insert if it doesn't exist. When you want your update your
     * data and don't want to affect existing parameters, use this.
     *
     * @param string $documentPath
     * @param array|FirestoreDocument $payload
     * @param boolean $documentExists
     * @param array $parameters
     * @param array $options
     *
     * @return \Frakt24\LaravelPHPFirestore\FirestoreDocument
     */
    public function updateDocument($documentPath, $payload, $documentExists = null, array $parameters = [], array $options = [])
    {
        $this->options['merge'] = true;

        return $this->setDocument($documentPath, $payload, $documentExists, $parameters, $options);
    }

    /**
     * To overwrite/insret your document into firestore.
     *
     * @param string $documentPath
     * @param array|FirestoreDocument $payload
     * @param boolean $documentExists
     * @param array $parameters
     * @param array $options
     *
     * @return \Frakt24\LaravelPHPFirestore\FirestoreDocument
     */
    public function setDocument($documentPath, $payload, $documentExists = null, array $parameters = [], array $options = [])
    {

        $this->validatePath($documentPath);

        if ($payload instanceof FirestoreDocument) {
            $document = $payload;
        } elseif ( is_array($payload) ) {
            $document = (new FirestoreDocument)->fillValues($payload);
        }

        if ($documentExists !== null) {
            $parameters['currentDocument.exists'] = !!$documentExists;
        }

        if ( array_key_exists('merge', $this->options) && $this->options['merge'] === true ) {
            $parameters['updateMask.fieldPaths'] = array_unique(array_keys($document->toArray()));
        }

        $response = $this->client->request('PATCH', 'documents/' . FirestoreHelper::normalizeCollection($documentPath), array_merge($options, [
            'json' => FirestoreHelper::decode($document->toJson())
        ]), $parameters);

        return new FirestoreDocument($response);
    }

    /**
     * Delete document from firestore
     *
     * @param string $document
     * @param array $options
     *
     * @return boolean
     */
    public function deleteDocument($document, array $options = [])
    {
        if ($document instanceof FirestoreDocument) {
            $document = FirestoreHelper::normalizeCollection($document->getRelativeName());
        }

        $this->validatePath($document);

        $response = $this->client->request('DELETE', 'documents/' . FirestoreHelper::normalizeCollection($document), $options);

        return count($response) === 0 ? true : false;
    }
}
