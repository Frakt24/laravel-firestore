<?php

namespace Frakt24\LaravelPHPFirestore\Auth;

class FirestoreCredentials
{
    private $type;
    private $projectId;
    private $privateKeyId;
    private $privateKey;
    private $clientEmail;
    private $clientId;
    private $authUri;
    private $tokenUri;
    private $authProviderCertUrl;
    private $clientCertUrl;

    public function __construct(array $config)
    {
        $this->type = $config['type'] ?? 'service_account';
        $this->projectId = $config['project_id'] ?? null;
        $this->privateKeyId = $config['private_key_id'] ?? null;
        $this->privateKey = $config['private_key'] ?? null;
        $this->clientEmail = $config['client_email'] ?? null;
        $this->clientId = $config['client_id'] ?? null;
        $this->authUri = $config['auth_uri'] ?? 'https://accounts.google.com/o/oauth2/auth';
        $this->tokenUri = $config['token_uri'] ?? 'https://oauth2.googleapis.com/token';
        $this->authProviderCertUrl = $config['auth_provider_x509_cert_url'] ?? 'https://www.googleapis.com/oauth2/v1/certs';
        $this->clientCertUrl = $config['client_x509_cert_url'] ?? null;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getProjectId(): ?string
    {
        return $this->projectId;
    }

    public function getPrivateKeyId(): ?string
    {
        return $this->privateKeyId;
    }

    public function getPrivateKey(): ?string
    {
        return $this->privateKey;
    }

    public function getClientEmail(): ?string
    {
        return $this->clientEmail;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function getAuthUri(): string
    {
        return $this->authUri;
    }

    public function getTokenUri(): string
    {
        return $this->tokenUri;
    }

    public function getAuthProviderCertUrl(): string
    {
        return $this->authProviderCertUrl;
    }

    public function getClientCertUrl(): ?string
    {
        return $this->clientCertUrl;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'project_id' => $this->projectId,
            'private_key_id' => $this->privateKeyId,
            'private_key' => $this->privateKey,
            'client_email' => $this->clientEmail,
            'client_id' => $this->clientId,
            'auth_uri' => $this->authUri,
            'token_uri' => $this->tokenUri,
            'auth_provider_x509_cert_url' => $this->authProviderCertUrl,
            'client_x509_cert_url' => $this->clientCertUrl
        ];
    }
}
