<?php

namespace Frakt24\LaravelPHPFirestore\Tests\Concerns;

use Frakt24\LaravelPHPFirestore\Core\Client;
use Mockery;

trait InteractsWithFirestore
{
    protected function mockFirestoreResponse(array $response, int $times = 1): void
    {
        $this->client->shouldReceive('request')
            ->times($times)
            ->andReturn($response);
    }

    protected function assertFirestoreDocumentCreated(string $collection, string $documentId, array $data): void
    {
        $this->client->shouldHaveReceived('request')
            ->with('POST', "/{$collection}/{$documentId}", [
                'json' => $data
            ]);
    }

    protected function assertFirestoreDocumentUpdated(string $collection, string $documentId, array $data): void
    {
        $this->client->shouldHaveReceived('request')
            ->with('PATCH', "/{$collection}/{$documentId}", [
                'json' => $data
            ]);
    }

    protected function assertFirestoreDocumentDeleted(string $collection, string $documentId): void
    {
        $this->client->shouldHaveReceived('request')
            ->with('DELETE', "/{$collection}/{$documentId}");
    }

    protected function assertFirestoreQueryExecuted(string $collection, array $query): void
    {
        $this->client->shouldHaveReceived('request')
            ->with('POST', "/{$collection}:runQuery", [
                'json' => $query
            ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
