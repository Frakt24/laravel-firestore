<?php

namespace Frakt24\LaravelPHPFirestore\Auth;

use Psr\SimpleCache\CacheInterface;

class FirestoreTokenCache
{
    private $cache;
    private $prefix;
    private $ttl;

    public function __construct(CacheInterface $cache, string $prefix = 'firestore_token:', int $ttl = 3600)
    {
        $this->cache = $cache;
        $this->prefix = $prefix;
        $this->ttl = $ttl;
    }

    /**
     * Get a cached token
     *
     * @param string $key Cache key
     * @return array|null Token data or null if not found
     */
    public function getToken(string $key): ?array
    {
        $data = $this->cache->get($this->prefix . $key);
        
        if (!$data) {
            return null;
        }

        return [
            'token' => $data['token'],
            'expires_at' => $data['expires_at']
        ];
    }

    /**
     * Store a token in cache
     *
     * @param string $key Cache key
     * @param string $token Access token
     * @param int $expiresAt Token expiration timestamp
     * @return bool Success
     */
    public function setToken(string $key, string $token, int $expiresAt): bool
    {
        return $this->cache->set($this->prefix . $key, [
            'token' => $token,
            'expires_at' => $expiresAt
        ], $this->ttl);
    }

    /**
     * Remove a token from cache
     *
     * @param string $key Cache key
     * @return bool Success
     */
    public function removeToken(string $key): bool
    {
        return $this->cache->delete($this->prefix . $key);
    }
}
