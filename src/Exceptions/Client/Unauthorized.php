<?php

namespace Frakt24\LaravelPHPFirestore\Exceptions\Client;

class Unauthorized extends \Exception
{
    public function __construct($response)
    {
        $message = 'Authentication with the API key failed. Make sure you are using the correct API key. Response: '.$response;
        parent::__construct($message);
    }
}
