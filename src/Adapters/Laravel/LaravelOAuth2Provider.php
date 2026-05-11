<?php

namespace Bmvc\BAuth\Adapters\Laravel;

use Bmvc\BAuth\Providers\BaseOAuth2Provider;
use Illuminate\Database\Eloquent\Model;

/**
 * LaravelOAuth2Provider
 * 
 * Adapter OAuth2 pour Laravel
 * Intègre OAuth2 avec l'Eloquent ORM de Laravel
 * 
 * @package Bmvc\BAuth\Adapters\Laravel
 */
class LaravelOAuth2Provider extends BaseOAuth2Provider
{
    /**
     * Modèle de l'utilisateur
     */
    protected string $userModel;

    /**
     * Modèle des comptes sociaux
     */
    protected string $socialAccountModel;

    /**
     * Constructeur
     */
    public function __construct(string $userModel = '\App\Models\User', string $socialAccountModel = '\App\Models\SocialAccount')
    {
        $this->userModel = $userModel;
        $this->socialAccountModel = $socialAccountModel;

        // Définir les callbacks automatiquement
        $this->setGetUserByEmailCallback(fn($email) => $this->userModel::where('email', $email)->first());
        $this->setCreateUserCallback(fn($data) => $this->userModel::create($data));
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
        $user = $this->userModel::where('email', $email)->first();

        if (!$user) {
            // Créer un nouvel utilisateur
            $user = $this->userModel::create([
                'email' => $email,
                'name' => $userInfo['name'] ?? 'User',
                'avatar' => $userInfo['picture'] ?? null,
                'password' => bcrypt(bin2hex(random_bytes(32))), // Password aléatoire
            ]);
        }

        // Lier le compte social
        $this->socialAccountModel::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider,
            ],
            [
                'external_id' => $userInfo['id'],
                'access_token' => $result['access_token'],
                'refresh_token' => $result['refresh_token'],
                'expires_at' => now()->addSeconds($result['expires_in']),
                'data' => json_encode($userInfo),
            ]
        );

        return array_merge($result, [
            'user' => $user,
        ]);
    }

    /**
     * Obtenir les comptes sociaux d'un utilisateur
     */
    public function getUserSocialAccounts($userId): array
    {
        return $this->socialAccountModel::where('user_id', $userId)->get()->toArray();
    }

    /**
     * Supprimer un compte social
     */
    public function deleteSocialAccount($userId, string $provider): bool
    {
        return $this->socialAccountModel::where('user_id', $userId)
            ->where('provider', $provider)
            ->delete() > 0;
    }
}
