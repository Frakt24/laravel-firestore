<?php

namespace Frakt24\LaravelPHPFirestore\Tests\Unit\Core;

use Frakt24\LaravelPHPFirestore\Tests\TestCase;
use Frakt24\LaravelPHPFirestore\Tests\Concerns\InteractsWithFirestore;
use Frakt24\LaravelPHPFirestore\Core\Client;
use Frakt24\LaravelPHPFirestore\Core\Batch;
use GuzzleHttp\Client as GuzzleClient;
use RuntimeException;
use Mockery;

class BatchTest extends TestCase
{
    use InteractsWithFirestore;

    protected Batch $batch;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = Mockery::mock(Client::class);
        $this->batch = new Batch($this->client);
    }

    /** @test */
    public function it_can_add_operations_to_batch()
    {
        $this->batch->add('create', 'users/123', ['name' => 'Test']);
        $this->batch->add('update', 'users/456', ['status' => 'active']);
        $this->batch->add('delete', 'users/789');

        $this->assertEquals(3, $this->batch->size());
    }

    /** @test */
    public function it_throws_exception_when_exceeding_batch_size()
    {
        $this->expectException(RuntimeException::class);

        for ($i = 0; $i <= 500; $i++) {
            $this->batch->add('create', "users/{$i}", ['name' => 'Test']);
        }
    }

    /** @test */
    public function it_can_commit_batch_operations()
    {
        $this->batch->add('create', 'users/123', ['name' => 'Test']);
        $this->batch->add('update', 'users/456', ['status' => 'active']);
        $this->batch->add('delete', 'users/789');

        $expectedWrites = [
            [
                'create' => [
                    'document' => 'users/123',
                    'fields' => ['name' => 'Test']
                ]
            ],
            [
                'update' => [
                    'document' => 'users/456',
                    'fields' => ['status' => 'active']
                ],
                'updateMask' => ['fieldPaths' => ['status']]
            ],
            [
                'delete' => 'users/789'
            ]
        ];

        $this->client->shouldReceive('request')
            ->once()
            ->with('POST', ':batchWrite', ['json' => ['writes' => $expectedWrites]])
            ->andReturn(['writeResults' => []]);

        $result = $this->batch->commit();
        $this->assertIsArray($result);
        $this->assertEquals([], $result);
    }

    /** @test */
    public function it_can_clear_batch_operations()
    {
        $this->batch->add('create', 'users/123', ['name' => 'Test']);
        $this->batch->add('update', 'users/456', ['status' => 'active']);
        
        $this->assertEquals(2, $this->batch->size());
        
        $this->batch->clear();
        
        $this->assertEquals(0, $this->batch->size());
    }
}
