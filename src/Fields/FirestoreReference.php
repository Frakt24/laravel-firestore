<?php

namespace Frakt24\LaravelPHPFirestore\Fields;

use Frakt24\LaravelPHPFirestore\Contracts\FirestoreDataTypeContract;
use Frakt24\LaravelPHPFirestore\FirestoreClient;
use Frakt24\LaravelPHPFirestore\Helpers\FirestoreHelper;

class FirestoreReference implements FirestoreDataTypeContract
{
    private $data;
    private $databaseResource;

    public function __construct($data='', $databaseResource = null)
    {
        $this->databaseResource = $databaseResource;

        return $this->setData($data);
    }

    public function setData($data)
    {
        $this->data = FirestoreHelper::normalizeCollection($data);
    }

    public function getData()
    {
        return $this->data;
    }

    public function parseValue()
    {
        $value =
            'projects/' .
            FirestoreClient::getConfig('projectId') .
            '/databases/' .
            FirestoreClient::getConfig('database') .
            '/documents/' .
            $this->getData();

        return $value;
    }

    public function fetch()
    {
        return $this->databaseResource->getDocument($this->getData());
    }
}
