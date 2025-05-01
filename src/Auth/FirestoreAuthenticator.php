<?php

namespace Frakt24\LaravelPHPFirestore\Auth;

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Frakt24\LaravelPHPFirestore\Exceptions\Authentication\AuthenticationException;

class FirestoreAuthenticator
{
    private $credentials;
    private $scopes;
    private $accessToken;
    private $expiresAt;
    private $httpClient;

    private const TOKEN_LIFETIME = 3600; // 1 hour
    private const FIRESTORE_SCOPE = 'https://www.googleapis.com/auth/datastore';

    public function __construct(FirestoreCredentials $credentials, array $scopes = [])
    {
        $this->credentials = $credentials;
        $this->scopes = array_merge([self::FIRESTORE_SCOPE], $scopes);
        $this->httpClient = new Client();
    }

    /**
     * Get an access token for Firestore
     *
     * @return string Valid access token
     * @throws AuthenticationException
     */
    public function getAccessToken(): string
    {
        if ($this->isTokenValid()) {
            return $this->accessToken;
        }

        return $this->fetchNewAccessToken();
    }

    /**
     * Check if the current token is valid
     *
     * @return bool
     */
    private function isTokenValid(): bool
    {
        if (!$this->accessToken || !$this->expiresAt) {
            return false;
        }

        // Consider token expired if it expires in less than 5 minutes
        return $this->expiresAt > time() + 300;
    }

    /**
     * Fetch a new access token using the service account credentials
     *
     * @return string New access token
     * @throws AuthenticationException
     */
    private function fetchNewAccessToken(): string
    {
        try {
            $now = time();
            
            $payload = [
                'iss' => $this->credentials->getClientEmail(),
                'scope' => implode(' ', $this->scopes),
                'aud' => $this->credentials->getTokenUri(),
                'exp' => $now + self::TOKEN_LIFETIME,
                'iat' => $now
            ];

            $jwt = JWT::encode(
                $payload,
                $this->credentials->getPrivateKey(),
                'RS256',
                $this->credentials->getPrivateKeyId()
            );

            $response = $this->httpClient->post($this->credentials->getTokenUri(), [
                'form_params' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            
            if (!isset($data['access_token'])) {
                throw new AuthenticationException('No access token in response');
            }

            $this->accessToken = $data['access_token'];
            $this->expiresAt = $now + ($data['expires_in'] ?? self::TOKEN_LIFETIME);

            return $this->accessToken;
        } catch (\Exception $e) {
            throw new AuthenticationException(
                'Failed to obtain access token: ' . $e->getMessage(),
                ['original_exception' => get_class($e)]
            );
        }
    }

    /**
     * Get authorization header value
     *
     * @return string
     * @throws AuthenticationException
     */
    public function getAuthorizationHeader(): string
    {
        return 'Bearer ' . $this->getAccessToken();
    }

    /**
     * Get the credentials
     *
     * @return FirestoreCredentials
     */
    public function getCredentials(): FirestoreCredentials
    {
        return $this->credentials;
    }
}
