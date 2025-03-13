<?php

namespace Frakt24\LaravelPHPFirestore\Handlers;

use Frakt24\LaravelPHPFirestore\Exceptions\Client\BadRequest;
use Frakt24\LaravelPHPFirestore\Exceptions\Client\Conflict;
use Frakt24\LaravelPHPFirestore\Exceptions\Client\Forbidden;
use Frakt24\LaravelPHPFirestore\Exceptions\Client\NotFound;
use Frakt24\LaravelPHPFirestore\Exceptions\Client\Unauthorized;
use Frakt24\LaravelPHPFirestore\Exceptions\Server\InternalServerError;
use Frakt24\LaravelPHPFirestore\Exceptions\UnhandledRequestError;

class RequestErrorHandler
{
    private $exception;
    private $body;

    public function __construct($exception)
    {
        $this->exception = $exception;
        $this->body = $exception->getResponse()->getBody();
    }

    public function handleError()
    {
        $errorCode = $this->exception->getResponse()->getStatusCode();

        if (method_exists($this, 'handle'.$errorCode)) {
            $this->{'handle'.$errorCode}();
        }

        $this->handleUnknown();
    }

    protected function handle400()
    {
        throw new BadRequest($this->body);
    }

    protected function handle401()
    {
        throw new Unauthorized($this->body);
    }

    protected function handle403()
    {
        throw new Forbidden($this->body);
    }

    protected function handle404()
    {
        throw new NotFound($this->body);
    }

    protected function handle409()
    {
        throw new Conflict($this->body);
    }

    protected function handle500()
    {
        throw new InternalServerError($this->body);
    }

    private function handleUnknown()
    {
        throw new UnhandledRequestError($this->exception->getResponse()->getStatusCode(), $this->body);
    }
}
