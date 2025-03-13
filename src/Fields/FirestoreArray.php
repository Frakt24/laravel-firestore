<?php

namespace Frakt24\LaravelPHPFirestore\Fields;

use Frakt24\LaravelPHPFirestore\Contracts\FirestoreDataTypeContract;
use Frakt24\LaravelPHPFirestore\FirestoreDocument;
use Frakt24\LaravelPHPFirestore\Helpers\FirestoreHelper;

class FirestoreArray implements FirestoreDataTypeContract
{
    private $data = [];

    public function __construct($data='')
    {
        if ( !empty($data) ) {
            return $this->setData((array) $data);
        }
    }

    public function add($data)
    {
        array_push($this->data, $data);

        return $this;
    }

    public function setData($data)
    {
        return $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function parseValue()
    {
        $payload = [
            'values' => [],
        ];

        foreach ($this->data as $data) {
            $document = new FirestoreDocument;
            call_user_func_array([$document, 'set'.ucfirst(FirestoreHelper::getType($data))], ['firestore', $data]);
            $payload['values'][] = $document->_getRawField('firestore');
        }

        return $payload;
    }
}
