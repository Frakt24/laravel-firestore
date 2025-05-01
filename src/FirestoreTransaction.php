<?php

namespace Frakt24\LaravelPHPFirestore;

class FirestoreTransaction
{
    private $client;
    private $transactionId;
    private $operations = [];

    public function __construct(FirestoreClient $client, string $transactionId)
    {
        $this->client = $client;
        $this->transactionId = $transactionId;
    }

    /**
     * Add a write operation to the transaction
     *
     * @param string $operation Type of operation (create, update, delete)
     * @param string $documentPath Path to the document
     * @param array $data Document data (for create/update)
     * @param array $options Additional options
     * @return self
     */
    public function addOperation(string $operation, string $documentPath, array $data = [], array $options = []): self
    {
        $this->operations[] = [
            'type' => $operation,
            'path' => $documentPath,
            'data' => $data,
            'options' => $options
        ];
        return $this;
    }

    /**
     * Commit the transaction
     *
     * @return array Result of the commit
     */
    public function commit(): array
    {
        $writes = [];
        foreach ($this->operations as $op) {
            $write = ['currentDocument' => ['exists' => true]];
            
            switch ($op['type']) {
                case 'create':
                    $write['document'] = $op['data'];
                    break;
                case 'update':
                    $write['updateMask'] = ['fieldPaths' => array_keys($op['data'])];
                    $write['update'] = $op['data'];
                    break;
                case 'delete':
                    $write['delete'] = $op['path'];
                    break;
            }
            
            $writes[] = $write;
        }

        return $this->client->request('POST', ':commit', [
            'json' => [
                'transaction' => $this->transactionId,
                'writes' => $writes
            ]
        ]);
    }

    /**
     * Roll back the transaction
     *
     * @return array Result of the rollback
     */
    public function rollback(): array
    {
        return $this->client->request('POST', ':rollback', [
            'json' => ['transaction' => $this->transactionId]
        ]);
    }
}
