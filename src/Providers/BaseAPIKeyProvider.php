<?php

namespace Bmvc\BAuth\Providers;

use Bmvc\BAuth\Contracts\APIKeyProviderInterface;
use Exception;

/**
 * BaseAPIKeyProvider
 * 
 * Implémentation de base pour la gestion des clés API
 * 
 * @package Bmvc\BAuth\Providers
 */
class BaseAPIKeyProvider implements APIKeyProviderInterface
{
    /**
     * Stockage des clés API
     */
    protected array $apiKeys = [];

    /**
     * Historique d'utilisation des clés
     */
    protected array $usageHistory = [];

    /**
     * Callback pour obtenir un utilisateur
     */
    protected $getUserCallback;

    /**
     * Définir le callback pour obtenir un utilisateur
     */
    public function setGetUserCallback($callback)
    {
        $this->getUserCallback = $callback;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function generateApiKey(
        $userId,
        string $name,
        array $permissions = [],
        ?int $expiresIn = null
    ): array {
        // Générer une clé aléatoire unique
        $apiKey = 'ak_' . bin2hex(random_bytes(32));
        $secret = bin2hex(random_bytes(32));

        // Hash du secret
        $secretHash = hash('sha256', $secret);

        $expiresAt = $expiresIn ? time() + $expiresIn : null;

        $this->apiKeys[$apiKey] = [
            'user_id' => $userId,
            'name' => $name,
            'secret_hash' => $secretHash,
            'permissions' => $permissions,
            'created_at' => time(),
            'expires_at' => $expiresAt,
            'last_used_at' => null,
            'revoked' => false,
        ];

        return [
            'api_key' => $apiKey,
            'secret' => $secret,
            'name' => $name,
            'created_at' => time(),
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validateApiKey(string $apiKey, string $secret): bool
    {
        if (!isset($this->apiKeys[$apiKey])) {
            return false;
        }

        $key = $this->apiKeys[$apiKey];

        // Vérifier si la clé est révoquée
        if ($key['revoked']) {
            return false;
        }

        // Vérifier si la clé a expiré
        if ($key['expires_at'] && $key['expires_at'] < time()) {
            return false;
        }

        // Vérifier le secret
        $secretHash = hash('sha256', $secret);

        if (!hash_equals($key['secret_hash'], $secretHash)) {
            return false;
        }

        // Mettre à jour la dernière utilisation
        $this->apiKeys[$apiKey]['last_used_at'] = time();

        // Enregistrer l'utilisation
        if (!isset($this->usageHistory[$apiKey])) {
            $this->usageHistory[$apiKey] = [];
        }

        $this->usageHistory[$apiKey][] = [
            'used_at' => time(),
        ];

        // Garder seulement les 100 dernières utilisations
        if (count($this->usageHistory[$apiKey]) > 100) {
            array_shift($this->usageHistory[$apiKey]);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiKeyInfo(string $apiKey): ?array
    {
        if (!isset($this->apiKeys[$apiKey])) {
            return null;
        }

        $key = $this->apiKeys[$apiKey];

        return [
            'name' => $key['name'],
            'user_id' => $key['user_id'],
            'permissions' => $key['permissions'],
            'created_at' => $key['created_at'],
            'expires_at' => $key['expires_at'],
            'last_used_at' => $key['last_used_at'],
            'revoked' => $key['revoked'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserApiKeys($userId): array
    {
        $keys = [];

        foreach ($this->apiKeys as $apiKey => $key) {
            if ($key['user_id'] == $userId) {
                $keys[] = [
                    'api_key' => $apiKey,
                    'name' => $key['name'],
                    'permissions' => $key['permissions'],
                    'created_at' => $key['created_at'],
                    'expires_at' => $key['expires_at'],
                    'last_used_at' => $key['last_used_at'],
                    'revoked' => $key['revoked'],
                ];
            }
        }

        return $keys;
    }

    /**
     * {@inheritdoc}
     */
    public function revokeApiKey(string $apiKey): bool
    {
        if (!isset($this->apiKeys[$apiKey])) {
            return false;
        }

        $this->apiKeys[$apiKey]['revoked'] = true;
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAllUserApiKeys($userId): int
    {
        $count = 0;

        foreach ($this->apiKeys as $apiKey => $key) {
            if ($key['user_id'] == $userId) {
                $this->apiKeys[$apiKey]['revoked'] = true;
                $count++;
            }
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPermission(string $apiKey, string $permission): bool
    {
        if (!isset($this->apiKeys[$apiKey])) {
            return false;
        }

        $key = $this->apiKeys[$apiKey];

        if ($key['revoked']) {
            return false;
        }

        if ($key['expires_at'] && $key['expires_at'] < time()) {
            return false;
        }

        return in_array($permission, $key['permissions']) ||
            in_array('*', $key['permissions']);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserFromApiKey(string $apiKey): ?array
    {
        if (!isset($this->apiKeys[$apiKey])) {
            return null;
        }

        $userId = $this->apiKeys[$apiKey]['user_id'];

        if ($this->getUserCallback) {
            return call_user_func($this->getUserCallback, $userId);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function updateApiKeyPermissions(string $apiKey, array $permissions): bool
    {
        if (!isset($this->apiKeys[$apiKey])) {
            return false;
        }

        $this->apiKeys[$apiKey]['permissions'] = $permissions;
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiKeyUsageHistory(string $apiKey, int $limit = 100): array
    {
        if (!isset($this->usageHistory[$apiKey])) {
            return [];
        }

        return array_slice($this->usageHistory[$apiKey], -$limit);
    }
}
