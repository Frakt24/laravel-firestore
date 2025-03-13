<?php

namespace Frakt24\LaravelPHPFirestore\Exceptions\Client;

class InvalidPathProvided extends \Exception
{
    public function __construct($response)
    {
        $message = 'The path you have defined is invalid. Path: '.$response;
        parent::__construct($message);
    }
}
