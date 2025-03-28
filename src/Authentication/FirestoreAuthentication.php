<?php
namespace Frakt24\LaravelPHPFirestore\Authentication;

use Exception;
use Frakt24\LaravelPHPFirestore\Exceptions\Client\BadRequest;
use Frakt24\LaravelPHPFirestore\Exceptions\Client\Conflict;
use Frakt24\LaravelPHPFirestore\Exceptions\Client\Forbidden;
use Frakt24\LaravelPHPFirestore\Exceptions\Client\NotFound;
use Frakt24\LaravelPHPFirestore\Exceptions\Client\Unauthorized;
use Frakt24\LaravelPHPFirestore\Exceptions\Server\InternalServerError;
use Frakt24\LaravelPHPFirestore\Exceptions\UnhandledRequestError;
use Frakt24\LaravelPHPFirestore\FirestoreClient;
use Frakt24\LaravelPHPFirestore\Handlers\RequestErrorHandler;
use Frakt24\LaravelPHPFirestore\Helpers\FirestoreHelper;
use GuzzleHttp\Exception\BadResponseException;

class FirestoreAuthentication
{
    /**
     * Firestore REST API Base URL
     *
     * @var string
     */
    private string $authRoot = 'https://www.googleapis.com/identitytoolkit/v3/relyingparty/';

    /**
     * Firestore Client object
     *
     * @var FirestoreClient
     */
    private FirestoreClient $client;

    public function __construct(FirestoreClient $client)
    {
        $this->client = $client;
    }

    /**
     * Return absolute url to make request
     *
     * @param string $resource
     *
     * @return string
     */
    private function constructUrl(string $resource): string
    {
        return $this->authRoot . $resource . '?key=' . $this->client->getApiKey();
    }

    /**
     * You can call this method and set token if you've already generate token
     *
     * @param string $token
     *
     * @return array
     */
    public function setCustomToken(string $token): array
    {
        return $this->client->setOption('headers', [
            'Authorization' => 'Bearer ' . $token,
        ]);
    }

    /**
     * Extract auth token from client
     *
     * @return null|string
     */
    public function getAuthToken(): ?string
    {
        $authHeader = $this->client->getOption('headers');

        if (!$authHeader) {
            return null;
        }

        return substr(reset($authHeader), 7);
    }

    /**
     * Login with email and password into Firebase Authentication
     *
     * @param string $email
     * @param string $password
     * @param boolean $setToken
     *
     * @return object|array
     */
    public function signInEmailPassword(string $email, string $password, bool $setToken = true): object|array
    {
        $response = $this->authRequest('POST', 'verifyPassword', [
            'form_params' => [
                'email' => $email,
                'password' => $password,
                'returnSecureToken' => 'true',
            ]
        ]);

        if ($setToken) {
            if (is_array($response) && !array_key_exists('idToken', $response)) {
                throw new BadRequest('idToken not found!');
            }

            $this->setCustomToken($response['idToken']);
        }

        return $response;
    }

    /**
     * Login with anonymously into Firebase Authentication
     *
     * @param boolean $setToken
     *
     * @return object
     */
    public function signInAnonymously(bool $setToken = true): object
    {
        $response = $this->authRequest('POST', 'signupNewUser', [
            'form_params' => [
                'returnSecureToken' => 'true',
            ]
        ]);

        if ($setToken) {
            if (is_array($response) && !array_key_exists('idToken', $response)) {
                throw new BadRequest('idToken not found!');
            }

            $this->setCustomToken($response['idToken']);
        }

        return $response;
    }

    /**
     * Responsible to make request to firebase authentication mechanism (rest-api)
     *
     * @param string $method
     * @param string $resource
     * @param array $options
     *
     * @return object|array
     */
    private function authRequest(string $method, string $resource, array $options = []): object|array
    {
        try {
            $options = array_merge($this->client->getOptions(), $options);

            // Unset authorization if set mistakenly
            if (isset($options['headers']['Authorization'])) {
                unset($options['headers']['Authorization']);
            }

            $this->client->setLastResponse(
                $this->client->getHttpClient()->request($method, $this->constructUrl($resource), $options)
            );

            return FirestoreHelper::decode((string) $this->client->getLastResponse()->getBody());
        } catch (BadResponseException $exception) {
            $this->client->setLastResponse($exception->getResponse());
            $this->handleError($exception);
        }
    }

    /**
     *  Throw our own custom handler for errors.
     *
     * @param BadResponseException $exception
     *
     * @throws BadRequest
     * @throws Unauthorized
     * @throws Forbidden
     * @throws NotFound
     * @throws Conflict
     * @throws InternalServerError
     * @throws UnhandledRequestError
     */
    private function handleError(Exception $exception): void
    {
        $handler = new RequestErrorHandler($exception);
        $handler->handleError();
    }
}
