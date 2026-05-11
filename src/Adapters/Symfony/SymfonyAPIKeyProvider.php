<?php

namespace Bmvc\BAuth\Adapters\Symfony;

use Bmvc\BAuth\Providers\BaseAPIKeyProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * SymfonyAPIKeyProvider
 * 
 * Adapter API Key pour Symfony
 * Gère les clés API avec Doctrine ORM
 * 
 * @package Bmvc\BAuth\Adapters\Symfony
 */
class SymfonyAPIKeyProvider extends BaseAPIKeyProvider
{
    /**
     * Entity Manager Doctrine
     */
    protected EntityManagerInterface $entityManager;

    /**
     * Classe de l'entité User
     */
    protected string $userEntity;

    /**
     * Classe de l'entité ApiKey
     */
    protected string $apiKeyEntity;

    /**
     * Constructeur
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        string $userEntity = 'App\Entity\User',
        string $apiKeyEntity = 'App\Entity\ApiKey'
    ) {
        $this->entityManager = $entityManager;
        $this->userEntity = $userEntity;
        $this->apiKeyEntity = $apiKeyEntity;

        $this->setGetUserCallback(
            fn($userId) => $this->entityManager->find($this->userEntity, $userId)
        );
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
        $apiKey = 'ak_' . bin2hex(random_bytes(32));
        $secret = bin2hex(random_bytes(32));
        $secretHash = hash('sha256', $secret);

        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            throw new \Exception("Utilisateur non trouvé");
        }

        $apiKeyClass = $this->apiKeyEntity;
        $record = new $apiKeyClass();
        $record->setUser($user);
        $record->setName($name);
        $record->setApiKey($apiKey);
        $record->setSecretHash($secretHash);
        $record->setPermissions(json_encode($permissions));

        if ($expiresIn) {
            $record->setExpiresAt(new \DateTime('+' . $expiresIn . ' seconds'));
        }

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return [
            'api_key' => $apiKey,
            'secret' => $secret,
            'name' => $name,
            'created_at' => $record->getCreatedAt(),
            'expires_at' => $record->getExpiresAt(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validateApiKey(string $apiKey, string $secret): bool
    {
        $record = $this->entityManager
            ->getRepository($this->apiKeyEntity)
            ->findOneBy(['apiKey' => $apiKey]);

        if (!$record || $record->isRevoked()) {
            return false;
        }

        if ($record->getExpiresAt() && $record->getExpiresAt()->isPast()) {
            return false;
        }

        $secretHash = hash('sha256', $secret);

        if (!hash_equals($record->getSecretHash(), $secretHash)) {
            return false;
        }

        $record->setLastUsedAt(new \DateTime());
        $this->entityManager->flush();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiKeyInfo(string $apiKey): ?array
    {
        $record = $this->entityManager
            ->getRepository($this->apiKeyEntity)
            ->findOneBy(['apiKey' => $apiKey]);

        if (!$record) {
            return null;
        }

        return [
            'name' => $record->getName(),
            'user_id' => $record->getUser()->getId(),
            'permissions' => json_decode($record->getPermissions(), true),
            'created_at' => $record->getCreatedAt(),
            'expires_at' => $record->getExpiresAt(),
            'last_used_at' => $record->getLastUsedAt(),
            'revoked' => $record->isRevoked(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserApiKeys($userId): array
    {
        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            return [];
        }

        return array_map(function ($key) {
            return [
                'api_key' => $key->getApiKey(),
                'name' => $key->getName(),
                'permissions' => json_decode($key->getPermissions(), true),
                'created_at' => $key->getCreatedAt(),
                'expires_at' => $key->getExpiresAt(),
                'last_used_at' => $key->getLastUsedAt(),
                'revoked' => $key->isRevoked(),
            ];
        }, $this->entityManager
            ->getRepository($this->apiKeyEntity)
            ->findBy(['user' => $user]));
    }

    /**
     * {@inheritdoc}
     */
    public function revokeApiKey(string $apiKey): bool
    {
        $record = $this->entityManager
            ->getRepository($this->apiKeyEntity)
            ->findOneBy(['apiKey' => $apiKey]);

        if (!$record) {
            return false;
        }

        $record->setRevoked(true);
        $this->entityManager->flush();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAllUserApiKeys($userId): int
    {
        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            return 0;
        }

        $keys = $this->entityManager
            ->getRepository($this->apiKeyEntity)
            ->findBy(['user' => $user]);

        $count = 0;
        foreach ($keys as $key) {
            $key->setRevoked(true);
            $count++;
        }

        $this->entityManager->flush();

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPermission(string $apiKey, string $permission): bool
    {
        $record = $this->entityManager
            ->getRepository($this->apiKeyEntity)
            ->findOneBy(['apiKey' => $apiKey]);

        if (!$record || $record->isRevoked()) {
            return false;
        }

        if ($record->getExpiresAt() && $record->getExpiresAt()->isPast()) {
            return false;
        }

        $permissions = json_decode($record->getPermissions(), true) ?? [];

        return in_array($permission, $permissions) || in_array('*', $permissions);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserFromApiKey(string $apiKey): ?array
    {
        $record = $this->entityManager
            ->getRepository($this->apiKeyEntity)
            ->findOneBy(['apiKey' => $apiKey]);

        if (!$record) {
            return null;
        }

        return (array) $record->getUser();
    }

    /**
     * {@inheritdoc}
     */
    public function updateApiKeyPermissions(string $apiKey, array $permissions): bool
    {
        $record = $this->entityManager
            ->getRepository($this->apiKeyEntity)
            ->findOneBy(['apiKey' => $apiKey]);

        if (!$record) {
            return false;
        }

        $record->setPermissions(json_encode($permissions));
        $this->entityManager->flush();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiKeyUsageHistory(string $apiKey, int $limit = 100): array
    {
        $record = $this->entityManager
            ->getRepository($this->apiKeyEntity)
            ->findOneBy(['apiKey' => $apiKey]);

        if (!$record) {
            return [];
        }

        return [
            'api_key' => $apiKey,
            'created_at' => $record->getCreatedAt(),
            'last_used_at' => $record->getLastUsedAt(),
        ];
    }
}
