<?php

namespace Frakt24\LaravelPHPFirestore\Tests\Unit\Models;

use Frakt24\LaravelPHPFirestore\Tests\TestCase;
use Frakt24\LaravelPHPFirestore\Tests\Concerns\InteractsWithFirestore;
use Frakt24\LaravelPHPFirestore\Models\FirestoreModel;
use Frakt24\LaravelPHPFirestore\Core\Client;
use Frakt24\LaravelPHPFirestore\Core\Collection;
use Frakt24\LaravelPHPFirestore\Contracts\FirestoreDocument;
use Carbon\Carbon;
use Mockery;

class FirestoreModelTest extends TestCase
{
    use InteractsWithFirestore;

    protected TestModel $model;
    protected Client $client;
    protected Collection $collection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = Mockery::mock(Client::class);
        $this->collection = Mockery::mock(Collection::class);
        $this->model = new TestModel();
        $this->model->setClient($this->client);

        $this->client->shouldReceive('collection')
            ->with('test_models')
            ->andReturn($this->collection);
    }

    /** @test */
    public function it_can_create_a_document()
    {
        $data = ['name' => 'Test', 'email' => 'test@example.com'];
        $document = Mockery::mock(FirestoreDocument::class);
        $document->shouldReceive('getId')->andReturn('new-id-123');

        $this->collection->shouldReceive('add')
            ->once()
            ->withArgs(function ($arg1, $arg2) use ($data) {
                return $arg2 === null && 
                       isset($arg1['name']) && $arg1['name'] === $data['name'] &&
                       isset($arg1['email']) && $arg1['email'] === $data['email'] &&
                       isset($arg1['createdAt']) && isset($arg1['updatedAt']);
            })
            ->andReturn($document);

        $this->collection->shouldReceive('document')->never();

        $this->model->fill($data);
        $result = $this->model->save();
        
        $this->assertTrue($result);
        $this->assertEquals('new-id-123', $this->model->getId());
    }

    /** @test */
    public function it_can_update_a_document()
    {
        $this->model->fill(['name' => 'Test']);
        $this->model->setId('123');
        $this->model->setExists(true);

        $document = Mockery::mock(FirestoreDocument::class);
        $document->shouldReceive('update')
            ->once()
            ->withArgs(function ($data) {
                return isset($data['name']) && $data['name'] === 'Test' &&
                       isset($data['updatedAt']);
            })
            ->andReturnTrue();

        $this->collection->shouldReceive('document')
            ->once()
            ->with('123')
            ->andReturn($document);

        $this->collection->shouldReceive('add')->never();

        $result = $this->model->save();
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_delete_a_document()
    {
        $this->model->setId('123');
        $this->model->setExists(true);

        $document = Mockery::mock(FirestoreDocument::class);
        $document->shouldReceive('delete')
            ->once()
            ->andReturnTrue();

        $this->collection->shouldReceive('document')
            ->once()
            ->with('123')
            ->andReturn($document);

        $this->collection->shouldReceive('add')->never();

        FirestoreModel::setGlobalSoftDeletes(false);
        $result = $this->model->delete();
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_soft_delete_a_document()
    {
        $this->model->setId('123');
        $this->model->setExists(true);

        $document = Mockery::mock(FirestoreDocument::class);
        $document->shouldReceive('update')
            ->once()
            ->withArgs(function ($data) {
                return isset($data['deletedAt']) &&
                       isset($data['updatedAt']);
            })
            ->andReturnTrue();

        $this->collection->shouldReceive('document')
            ->once()
            ->with('123')
            ->andReturn($document);

        $this->collection->shouldReceive('add')->never();

        FirestoreModel::setGlobalSoftDeletes(true);
        $result = $this->model->delete();
        $this->assertTrue($result);
        $this->assertNotNull($this->model->getAttribute('deletedAt'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
        FirestoreModel::setGlobalSoftDeletes(true);
    }
}

class TestModel extends FirestoreModel
{
    protected string $collection = 'test_models';
    protected array $fillable = ['name', 'email'];
}
