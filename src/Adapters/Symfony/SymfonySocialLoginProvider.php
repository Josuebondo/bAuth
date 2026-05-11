<?php

namespace Bmvc\BAuth\Adapters\Symfony;

use Bmvc\BAuth\Providers\BaseSocialLoginProvider;
use Doctrine\ORM\EntityManagerInterface;

/**
 * SymfonySocialLoginProvider
 * 
 * Adapter Social Login pour Symfony
 * Gère la liaison des comptes sociaux avec Doctrine ORM
 * 
 * @package Bmvc\BAuth\Adapters\Symfony
 */
class SymfonySocialLoginProvider extends BaseSocialLoginProvider
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
     * Classe de l'entité SocialAccount
     */
    protected string $socialAccountEntity;

    /**
     * Constructeur
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        string $userEntity = 'App\Entity\User',
        string $socialAccountEntity = 'App\Entity\SocialAccount'
    ) {
        $this->entityManager = $entityManager;
        $this->userEntity = $userEntity;
        $this->socialAccountEntity = $socialAccountEntity;

        $this->setGetUserCallback(
            fn($userId) => $this->entityManager->find($this->userEntity, $userId)
        );
        $this->setCreateUserCallback(fn($data) => $this->createUser($data));
        $this->setUpdateUserCallback(fn($userId, $data) => $this->updateUser($userId, $data));
    }

    /**
     * {@inheritdoc}
     */
    public function userExists(string $provider, string $externalId): bool
    {
        $socialAccountClass = $this->socialAccountEntity;

        return (bool) $this->entityManager
            ->getRepository($socialAccountClass)
            ->findOneBy(['provider' => $provider, 'externalId' => $externalId]);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserByExternalId(string $provider, string $externalId): ?array
    {
        $socialAccountClass = $this->socialAccountEntity;

        $socialAccount = $this->entityManager
            ->getRepository($socialAccountClass)
            ->findOneBy(['provider' => $provider, 'externalId' => $externalId]);

        if (!$socialAccount) {
            return null;
        }

        return (array) $socialAccount->getUser();
    }

    /**
     * {@inheritdoc}
     */
    public function linkSocialAccount(
        $userId,
        string $provider,
        string $externalId,
        array $data = []
    ): bool {
        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            return false;
        }

        $socialAccountClass = $this->socialAccountEntity;
        $socialAccount = $this->entityManager
            ->getRepository($socialAccountClass)
            ->findOneBy(['user' => $user, 'provider' => $provider]);

        if (!$socialAccount) {
            $socialAccount = new $socialAccountClass();
            $socialAccount->setUser($user);
            $socialAccount->setProvider($provider);
            $this->entityManager->persist($socialAccount);
        }

        $socialAccount->setExternalId($externalId);
        $socialAccount->setData(json_encode($data));

        $this->entityManager->flush();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function unlinkSocialAccount($userId, string $provider): bool
    {
        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            return false;
        }

        $socialAccountClass = $this->socialAccountEntity;
        $socialAccount = $this->entityManager
            ->getRepository($socialAccountClass)
            ->findOneBy(['user' => $user, 'provider' => $provider]);

        if (!$socialAccount) {
            return false;
        }

        $this->entityManager->remove($socialAccount);
        $this->entityManager->flush();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function createUserFromSocial(
        string $provider,
        string $externalId,
        array $data
    ): array {
        $user = $this->createUser([
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? 'User',
            'avatar' => $data['picture'] ?? null,
        ]);

        $this->linkSocialAccount($user->getId(), $provider, $externalId, $data);

        return (array) $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getSocialAccounts($userId): array
    {
        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            return [];
        }

        $socialAccountClass = $this->socialAccountEntity;

        return array_map(function ($account) {
            return [
                'provider' => $account->getProvider(),
                'external_id' => $account->getExternalId(),
                'linked_at' => $account->getCreatedAt(),
            ];
        }, $this->entityManager
            ->getRepository($socialAccountClass)
            ->findBy(['user' => $user]));
    }

    /**
     * {@inheritdoc}
     */
    public function updateSocialAccount($userId, string $provider, array $data): bool
    {
        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            return false;
        }

        $socialAccountClass = $this->socialAccountEntity;
        $socialAccount = $this->entityManager
            ->getRepository($socialAccountClass)
            ->findOneBy(['user' => $user, 'provider' => $provider]);

        if (!$socialAccount) {
            return false;
        }

        $socialAccount->setData(json_encode($data));
        $this->entityManager->flush();

        return true;
    }

    /**
     * Créer un nouvel utilisateur
     */
    protected function createUser(array $data)
    {
        $userClass = $this->userEntity;
        $user = new $userClass();

        if (isset($data['email'])) $user->setEmail($data['email']);
        if (isset($data['name'])) $user->setName($data['name']);
        if (isset($data['avatar'])) $user->setAvatar($data['avatar']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Mettre à jour un utilisateur
     */
    protected function updateUser($userId, array $data)
    {
        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            return false;
        }

        if (isset($data['email'])) $user->setEmail($data['email']);
        if (isset($data['name'])) $user->setName($data['name']);
        if (isset($data['avatar'])) $user->setAvatar($data['avatar']);

        $this->entityManager->flush();

        return $user;
    }
}
