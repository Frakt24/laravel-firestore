<?php

namespace Frakt24\LaravelPHPFirestore\Exceptions\Client;

class FieldNotFound extends \Exception
{
    public function __construct($response)
    {
        $message = 'Field ' . $response . ' does not exist.';
        parent::__construct($message);
    }
}
