<?php

namespace Bmvc\BAuth\Adapters\Symfony;

use Bmvc\BAuth\Providers\BaseOAuth2Provider;
use Doctrine\ORM\EntityManagerInterface;

/**
 * SymfonyOAuth2Provider
 * 
 * Adapter OAuth2 pour Symfony
 * Intègre OAuth2 avec Doctrine ORM
 * 
 * @package Bmvc\BAuth\Adapters\Symfony
 */
class SymfonyOAuth2Provider extends BaseOAuth2Provider
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

        $this->setGetUserByEmailCallback(
            fn($email) => $this->entityManager
                ->getRepository($this->userEntity)
                ->findOneBy(['email' => $email])
        );

        $this->setCreateUserCallback(fn($data) => $this->createUser($data));
    }

    /**
     * {@inheritdoc}
     */
    public function handleCallback(string $provider, string $code, string $state): array
    {
        $result = parent::handleCallback($provider, $code, $state);

        $userInfo = $result['user_info'];
        $email = $userInfo['email'];

        // Chercher l'utilisateur existant
        $user = $this->entityManager
            ->getRepository($this->userEntity)
            ->findOneBy(['email' => $email]);

        if (!$user) {
            // Créer un nouvel utilisateur
            $userClass = $this->userEntity;
            $user = new $userClass();
            $user->setEmail($email);
            $user->setName($userInfo['name'] ?? 'User');
            $user->setAvatar($userInfo['picture'] ?? null);
            $user->setPassword(bin2hex(random_bytes(32)));

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        // Lier le compte social
        $socialAccountClass = $this->socialAccountEntity;
        $socialAccount = $this->entityManager
            ->getRepository($socialAccountClass)
            ->findOneBy([
                'user' => $user,
                'provider' => $provider,
            ]);

        if (!$socialAccount) {
            $socialAccount = new $socialAccountClass();
            $socialAccount->setUser($user);
            $socialAccount->setProvider($provider);
        }

        $socialAccount->setExternalId($userInfo['id']);
        $socialAccount->setAccessToken($result['access_token']);
        $socialAccount->setRefreshToken($result['refresh_token']);
        $socialAccount->setExpiresAt(
            new \DateTime('+' . $result['expires_in'] . ' seconds')
        );
        $socialAccount->setData(json_encode($userInfo));

        $this->entityManager->persist($socialAccount);
        $this->entityManager->flush();

        return array_merge($result, [
            'user' => $user,
        ]);
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
        if (isset($data['password'])) $user->setPassword($data['password']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Obtenir les comptes sociaux d'un utilisateur
     */
    public function getUserSocialAccounts($user)
    {
        $socialAccountClass = $this->socialAccountEntity;

        return $this->entityManager
            ->getRepository($socialAccountClass)
            ->findBy(['user' => $user]);
    }

    /**
     * Supprimer un compte social
     */
    public function deleteSocialAccount($user, string $provider): bool
    {
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
}
