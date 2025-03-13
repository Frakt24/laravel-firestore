<?php
namespace Frakt24\LaravelPHPFirestore\Authentication;

use Exception;
use Frakt24\LaravelPHPFirestore\Exceptions\Client\Conflict;
use Frakt24\LaravelPHPFirestore\Exceptions\Client\Forbidden;
use Frakt24\LaravelPHPFirestore\Exceptions\Client\NotFound;
use Frakt24\LaravelPHPFirestore\Exceptions\Client\Unauthorized;
use Frakt24\LaravelPHPFirestore\Exceptions\Server\InternalServerError;
use Frakt24\LaravelPHPFirestore\Exceptions\UnhandledRequestError;
use GuzzleHttp\Exception\BadResponseException;
use MrShan0\PHPFirestore\Exceptions\Client\BadRequest;
use MrShan0\PHPFirestore\FirestoreClient;
use MrShan0\PHPFirestore\Handlers\RequestErrorHandler;
use MrShan0\PHPFirestore\Helpers\FirestoreHelper;

class FirestoreAuthentication
{
    /**
     * Firestore REST API Base URL
     *
     * @var string
     */
    private $authRoot = 'https://www.googleapis.com/identitytoolkit/v3/relyingparty/';

    /**
     * Firestore Client object
     *
     * @var \Frakt24\LaravelPHPFirestore\FirestoreClient
     */
    private $client;

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
    private function constructUrl($resource)
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
    public function setCustomToken($token)
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
    public function getAuthToken()
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
     * @return object
     */
    public function signInEmailPassword($email, $password, $setToken = true)
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
    public function signInAnonymously($setToken = true)
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
     * @return object
     */
    private function authRequest($method, $resource, array $options = [])
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
     * @throws \Frakt24\LaravelPHPFirestore\Exceptions\Client\BadRequest
     * @throws Unauthorized
     * @throws Forbidden
     * @throws NotFound
     * @throws Conflict
     * @throws InternalServerError
     * @throws UnhandledRequestError
     */
    private function handleError(Exception $exception)
    {
        $handler = new RequestErrorHandler($exception);
        $handler->handleError();
    }
}
