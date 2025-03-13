<?php

namespace Frakt24\LaravelPHPFirestore\Fields;

use Frakt24\LaravelPHPFirestore\Contracts\FirestoreDataTypeContract;
use Frakt24\LaravelPHPFirestore\FirestoreDocument;
use Frakt24\LaravelPHPFirestore\Helpers\FirestoreHelper;

class FirestoreObject implements FirestoreDataTypeContract
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
        return $this->normalize($this->data);
    }

    public function parseValue()
    {
        $payload = [
            'fields' => [],
        ];

        foreach ($this->data as $key => $data) {
            $document = new FirestoreDocument;
            call_user_func_array([$document, 'set'.ucfirst(FirestoreHelper::getType($data))], ['firestore', $data]);
            $payload['fields'][$key] = $document->_getRawField('firestore');
        }

        return $payload;
    }

    public function set($field, $value)
    {
        return $this->data[$field] = $value;
    }

    protected function normalize(array $data)
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            if (is_numeric($key) && is_array($value)) {
                $hasAssociativeKey = false;
                foreach ($value as $subKey => $subValue) {
                    if (!is_numeric($subKey)) {
                        $hasAssociativeKey = true;
                        break;
                    }
                }
                if ($hasAssociativeKey) {
                    $normalized = array_merge($normalized, $this->normalize($value));
                    continue;
                }
            }

            if (is_array($value)) {
                $normalized[$key] = $this->normalize($value);
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
