<?php

namespace Bmvc\BAuth\Adapters\Symfony;

use Bmvc\BAuth\Providers\BaseWebAuthnProvider;
use Doctrine\ORM\EntityManagerInterface;

/**
 * SymfonyWebAuthnProvider
 * 
 * Adapter WebAuthn pour Symfony
 * Gère les Passkeys avec Doctrine ORM
 * 
 * @package Bmvc\BAuth\Adapters\Symfony
 */
class SymfonyWebAuthnProvider extends BaseWebAuthnProvider
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
     * Classe de l'entité Credential
     */
    protected string $credentialEntity;

    /**
     * Classe de l'entité BackupCode
     */
    protected string $backupCodeEntity;

    /**
     * Constructeur
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        string $origin = 'http://localhost',
        string $userEntity = 'App\Entity\User',
        string $credentialEntity = 'App\Entity\WebAuthnCredential',
        string $backupCodeEntity = 'App\Entity\WebAuthnBackupCode'
    ) {
        parent::__construct($origin);
        $this->entityManager = $entityManager;
        $this->userEntity = $userEntity;
        $this->credentialEntity = $credentialEntity;
        $this->backupCodeEntity = $backupCodeEntity;
    }

    /**
     * {@inheritdoc}
     */
    public function completeRegistration(
        $userId,
        string $credentialName,
        array $attestationResponse
    ): array {
        $credentialId = bin2hex(random_bytes(32));

        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            throw new \Exception("Utilisateur non trouvé");
        }

        $credentialClass = $this->credentialEntity;
        $credential = new $credentialClass();
        $credential->setUser($user);
        $credential->setCredentialId($credentialId);
        $credential->setCredentialName($credentialName);
        $credential->setPublicKey($attestationResponse['response']['attestationObject'] ?? null);
        $credential->setSignCount(0);
        $credential->setBackupEligible(json_encode($attestationResponse['response']['transports'] ?? []));
        $credential->setPasswordlessEnabled(true);

        $this->entityManager->persist($credential);
        $this->entityManager->flush();

        return [
            'credential_id' => $credentialId,
            'credential_name' => $credentialName,
            'created_at' => $credential->getCreatedAt(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserCredentials($userId): array
    {
        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            return [];
        }

        return array_map(function ($credential) {
            return [
                'credential_id' => $credential->getCredentialId(),
                'credential_name' => $credential->getCredentialName(),
                'created_at' => $credential->getCreatedAt(),
                'last_used_at' => $credential->getLastUsedAt(),
                'backup_eligible' => json_decode($credential->getBackupEligible(), true),
                'backup_state' => (bool) $credential->isBackupState(),
            ];
        }, $this->entityManager
            ->getRepository($this->credentialEntity)
            ->findBy(['user' => $user]));
    }

    /**
     * {@inheritdoc}
     */
    public function deleteCredential($userId, string $credentialId): bool
    {
        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            return false;
        }

        $credential = $this->entityManager
            ->getRepository($this->credentialEntity)
            ->findOneBy(['user' => $user, 'credentialId' => $credentialId]);

        if (!$credential) {
            return false;
        }

        $this->entityManager->remove($credential);
        $this->entityManager->flush();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function renameCredential($userId, string $credentialId, string $newName): bool
    {
        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            return false;
        }

        $credential = $this->entityManager
            ->getRepository($this->credentialEntity)
            ->findOneBy(['user' => $user, 'credentialId' => $credentialId]);

        if (!$credential) {
            return false;
        }

        $credential->setCredentialName($newName);
        $this->entityManager->flush();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentialBackupStatus($userId, string $credentialId): array
    {
        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            return [];
        }

        $credential = $this->entityManager
            ->getRepository($this->credentialEntity)
            ->findOneBy(['user' => $user, 'credentialId' => $credentialId]);

        if (!$credential) {
            return [];
        }

        return [
            'backup_eligible' => json_decode($credential->getBackupEligible(), true),
            'backup_state' => (bool) $credential->isBackupState(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function generateBackupCodes($userId, int $count = 10): array
    {
        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            throw new \Exception("Utilisateur non trouvé");
        }

        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $code = strtoupper(bin2hex(random_bytes(4)));
            $code = substr($code, 0, 4) . '-' . substr($code, 4, 4);

            $codeHash = hash('sha256', $code);

            $backupCodeClass = $this->backupCodeEntity;
            $backupCode = new $backupCodeClass();
            $backupCode->setUser($user);
            $backupCode->setCodeHash($codeHash);
            $backupCode->setUsed(false);

            $this->entityManager->persist($backupCode);

            $codes[] = $code;
        }

        $this->entityManager->flush();

        return $codes;
    }

    /**
     * {@inheritdoc}
     */
    public function validateBackupCode($userId, string $code): bool
    {
        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            return false;
        }

        $codeHash = hash('sha256', $code);

        $backupCode = $this->entityManager
            ->getRepository($this->backupCodeEntity)
            ->findOneBy(['user' => $user, 'codeHash' => $codeHash, 'used' => false]);

        if (!$backupCode) {
            return false;
        }

        $backupCode->setUsed(true);
        $this->entityManager->flush();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserBackupCodes($userId): array
    {
        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            return [];
        }

        return array_map(function ($code) {
            return [
                'code' => '****-' . substr($code->getCodeHash(), -4),
                'created_at' => $code->getCreatedAt(),
            ];
        }, $this->entityManager
            ->getRepository($this->backupCodeEntity)
            ->findBy(['user' => $user, 'used' => false]));
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordlessEnabled($userId): bool
    {
        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            return false;
        }

        return (bool) $this->entityManager
            ->getRepository($this->credentialEntity)
            ->findOneBy(['user' => $user, 'passwordlessEnabled' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function setPasswordlessEnabled($userId, bool $enabled): bool
    {
        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            return false;
        }

        $credentials = $this->entityManager
            ->getRepository($this->credentialEntity)
            ->findBy(['user' => $user]);

        foreach ($credentials as $credential) {
            $credential->setPasswordlessEnabled($enabled);
        }

        $this->entityManager->flush();

        return true;
    }
}
