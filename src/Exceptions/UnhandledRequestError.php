<?php

namespace Frakt24\LaravelPHPFirestore\Exceptions;

class UnhandledRequestError extends \Exception
{
    public function __construct($code, $response)
    {
        $message = 'The request failed with the error: '.$code.'.  Response: '.$response;
        parent::__construct($message, $code);
    }
}
