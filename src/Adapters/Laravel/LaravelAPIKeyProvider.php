<?php

namespace Bmvc\BAuth\Adapters\Laravel;

use Bmvc\BAuth\Providers\BaseAPIKeyProvider;
use Illuminate\Support\Str;

/**
 * LaravelAPIKeyProvider
 * 
 * Adapter API Key pour Laravel
 * Gère les clés API avec la base de données Laravel
 * 
 * @package Bmvc\BAuth\Adapters\Laravel
 */
class LaravelAPIKeyProvider extends BaseAPIKeyProvider
{
    /**
     * Modèle de l'utilisateur
     */
    protected string $userModel;

    /**
     * Modèle des clés API
     */
    protected string $apiKeyModel;

    /**
     * Constructeur
     */
    public function __construct(
        string $userModel = '\App\Models\User',
        string $apiKeyModel = '\App\Models\ApiKey'
    ) {
        $this->userModel = $userModel;
        $this->apiKeyModel = $apiKeyModel;

        $this->setGetUserCallback(fn($userId) => $this->userModel::find($userId)?->toArray());
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
        $apiKey = 'ak_' . Str::random(32);
        $secret = Str::random(32);
        $secretHash = hash('sha256', $secret);

        $expiresAt = $expiresIn ? now()->addSeconds($expiresIn) : null;

        $record = $this->apiKeyModel::create([
            'user_id' => $userId,
            'name' => $name,
            'api_key' => $apiKey,
            'secret_hash' => $secretHash,
            'permissions' => json_encode($permissions),
            'expires_at' => $expiresAt,
        ]);

        return [
            'api_key' => $apiKey,
            'secret' => $secret,
            'name' => $name,
            'created_at' => $record->created_at,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validateApiKey(string $apiKey, string $secret): bool
    {
        $record = $this->apiKeyModel::where('api_key', $apiKey)->first();

        if (!$record) {
            return false;
        }

        if ($record->revoked) {
            return false;
        }

        if ($record->expires_at && $record->expires_at->isPast()) {
            return false;
        }

        $secretHash = hash('sha256', $secret);

        if (!hash_equals($record->secret_hash, $secretHash)) {
            return false;
        }

        // Mettre à jour la dernière utilisation
        $record->update(['last_used_at' => now()]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiKeyInfo(string $apiKey): ?array
    {
        $record = $this->apiKeyModel::where('api_key', $apiKey)->first();

        if (!$record) {
            return null;
        }

        return [
            'name' => $record->name,
            'user_id' => $record->user_id,
            'permissions' => json_decode($record->permissions, true),
            'created_at' => $record->created_at,
            'expires_at' => $record->expires_at,
            'last_used_at' => $record->last_used_at,
            'revoked' => (bool) $record->revoked,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserApiKeys($userId): array
    {
        return $this->apiKeyModel::where('user_id', $userId)
            ->get()
            ->map(fn($key) => [
                'api_key' => $key->api_key,
                'name' => $key->name,
                'permissions' => json_decode($key->permissions, true),
                'created_at' => $key->created_at,
                'expires_at' => $key->expires_at,
                'last_used_at' => $key->last_used_at,
                'revoked' => (bool) $key->revoked,
            ])
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function revokeApiKey(string $apiKey): bool
    {
        return $this->apiKeyModel::where('api_key', $apiKey)
            ->update(['revoked' => true]) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAllUserApiKeys($userId): int
    {
        return $this->apiKeyModel::where('user_id', $userId)
            ->update(['revoked' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function hasPermission(string $apiKey, string $permission): bool
    {
        $record = $this->apiKeyModel::where('api_key', $apiKey)->first();

        if (!$record || $record->revoked) {
            return false;
        }

        if ($record->expires_at && $record->expires_at->isPast()) {
            return false;
        }

        $permissions = json_decode($record->permissions, true) ?? [];

        return in_array($permission, $permissions) || in_array('*', $permissions);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserFromApiKey(string $apiKey): ?array
    {
        $record = $this->apiKeyModel::where('api_key', $apiKey)->first();

        if (!$record) {
            return null;
        }

        return $this->userModel::find($record->user_id)?->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function updateApiKeyPermissions(string $apiKey, array $permissions): bool
    {
        return $this->apiKeyModel::where('api_key', $apiKey)
            ->update(['permissions' => json_encode($permissions)]) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiKeyUsageHistory(string $apiKey, int $limit = 100): array
    {
        $record = $this->apiKeyModel::where('api_key', $apiKey)->first();

        if (!$record) {
            return [];
        }

        return [
            'api_key' => $apiKey,
            'created_at' => $record->created_at,
            'last_used_at' => $record->last_used_at,
        ];
    }
}
