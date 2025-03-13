<?php

namespace Frakt24\LaravelPHPFirestore\Exceptions\Client;

class FieldTypeError extends \Exception
{
    public function __construct($response)
    {
        $message = 'Unexpected field type detected. Received type: '.$response;
        parent::__construct($message);
    }
}
