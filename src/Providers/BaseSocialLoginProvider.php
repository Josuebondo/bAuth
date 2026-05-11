<?php

namespace Bmvc\BAuth\Providers;

use Bmvc\BAuth\Contracts\SocialLoginProviderInterface;

/**
 * BaseSocialLoginProvider
 * 
 * Implémentation de base pour la gestion de connexion sociale
 * Lie les comptes sociaux aux utilisateurs
 * 
 * @package Bmvc\BAuth\Providers
 */
class BaseSocialLoginProvider implements SocialLoginProviderInterface
{
    /**
     * Stockage des comptes sociaux
     */
    protected array $socialAccounts = [];

    /**
     * Callback pour obtenir un utilisateur
     */
    protected $getUserCallback;

    /**
     * Callback pour mettre à jour un utilisateur
     */
    protected $updateUserCallback;

    /**
     * Callback pour créer un utilisateur
     */
    protected $createUserCallback;

    /**
     * Définir le callback pour obtenir un utilisateur
     */
    public function setGetUserCallback($callback)
    {
        $this->getUserCallback = $callback;
        return $this;
    }

    /**
     * Définir le callback pour mettre à jour un utilisateur
     */
    public function setUpdateUserCallback($callback)
    {
        $this->updateUserCallback = $callback;
        return $this;
    }

    /**
     * Définir le callback pour créer un utilisateur
     */
    public function setCreateUserCallback($callback)
    {
        $this->createUserCallback = $callback;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function userExists(string $provider, string $externalId): bool
    {
        $key = "{$provider}:{$externalId}";
        return isset($this->socialAccounts[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserByExternalId(string $provider, string $externalId): ?array
    {
        $key = "{$provider}:{$externalId}";

        if (!isset($this->socialAccounts[$key])) {
            return null;
        }

        $userId = $this->socialAccounts[$key]['user_id'];

        if ($this->getUserCallback) {
            return call_user_func($this->getUserCallback, $userId);
        }

        return null;
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
        $key = "{$provider}:{$externalId}";

        $this->socialAccounts[$key] = [
            'user_id' => $userId,
            'provider' => $provider,
            'external_id' => $externalId,
            'data' => $data,
            'linked_at' => time(),
        ];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function unlinkSocialAccount($userId, string $provider): bool
    {
        $keyToRemove = null;

        foreach ($this->socialAccounts as $key => $account) {
            if ($account['user_id'] == $userId && $account['provider'] === $provider) {
                $keyToRemove = $key;
                break;
            }
        }

        if ($keyToRemove) {
            unset($this->socialAccounts[$keyToRemove]);
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function createUserFromSocial(
        string $provider,
        string $externalId,
        array $data
    ): array {
        if (!$this->createUserCallback) {
            throw new \Exception("Callback createUser non défini");
        }

        $userData = [
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? 'User',
            'avatar' => $data['picture'] ?? null,
            'provider' => $provider,
        ];

        $user = call_user_func($this->createUserCallback, $userData);

        // Lier le compte social
        $this->linkSocialAccount($user['id'] ?? $user->id, $provider, $externalId, $data);

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getSocialAccounts($userId): array
    {
        $accounts = [];

        foreach ($this->socialAccounts as $account) {
            if ($account['user_id'] == $userId) {
                $accounts[] = [
                    'provider' => $account['provider'],
                    'external_id' => $account['external_id'],
                    'linked_at' => $account['linked_at'],
                ];
            }
        }

        return $accounts;
    }

    /**
     * {@inheritdoc}
     */
    public function updateSocialAccount($userId, string $provider, array $data): bool
    {
        foreach ($this->socialAccounts as $key => $account) {
            if ($account['user_id'] == $userId && $account['provider'] === $provider) {
                $this->socialAccounts[$key]['data'] = array_merge(
                    $this->socialAccounts[$key]['data'],
                    $data
                );
                return true;
            }
        }

        return false;
    }
}
