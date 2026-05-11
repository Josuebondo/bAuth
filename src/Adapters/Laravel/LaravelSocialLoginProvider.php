<?php

namespace Bmvc\BAuth\Adapters\Laravel;

use Bmvc\BAuth\Providers\BaseSocialLoginProvider;

/**
 * LaravelSocialLoginProvider
 * 
 * Adapter Social Login pour Laravel
 * Gère la liaison des comptes sociaux avec Laravel Eloquent
 * 
 * @package Bmvc\BAuth\Adapters\Laravel
 */
class LaravelSocialLoginProvider extends BaseSocialLoginProvider
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
    public function __construct(
        string $userModel = '\App\Models\User',
        string $socialAccountModel = '\App\Models\SocialAccount'
    ) {
        $this->userModel = $userModel;
        $this->socialAccountModel = $socialAccountModel;

        $this->setGetUserCallback(fn($userId) => $this->userModel::find($userId));
        $this->setCreateUserCallback(fn($data) => $this->userModel::create($data));
        $this->setUpdateUserCallback(fn($userId, $data) => $this->userModel::find($userId)->update($data));
    }

    /**
     * {@inheritdoc}
     */
    public function userExists(string $provider, string $externalId): bool
    {
        return $this->socialAccountModel::where('provider', $provider)
            ->where('external_id', $externalId)
            ->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function getUserByExternalId(string $provider, string $externalId): ?array
    {
        $socialAccount = $this->socialAccountModel::where('provider', $provider)
            ->where('external_id', $externalId)
            ->first();

        if (!$socialAccount) {
            return null;
        }

        return $this->userModel::find($socialAccount->user_id)?->toArray();
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
        $this->socialAccountModel::updateOrCreate(
            [
                'user_id' => $userId,
                'provider' => $provider,
            ],
            [
                'external_id' => $externalId,
                'data' => json_encode($data),
            ]
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function unlinkSocialAccount($userId, string $provider): bool
    {
        return $this->socialAccountModel::where('user_id', $userId)
            ->where('provider', $provider)
            ->delete() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function createUserFromSocial(
        string $provider,
        string $externalId,
        array $data
    ): array {
        $userData = [
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? 'User',
            'avatar' => $data['picture'] ?? null,
        ];

        $user = $this->userModel::create($userData);

        $this->linkSocialAccount($user->id, $provider, $externalId, $data);

        return $user->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getSocialAccounts($userId): array
    {
        return $this->socialAccountModel::where('user_id', $userId)
            ->get()
            ->map(fn($account) => [
                'provider' => $account->provider,
                'external_id' => $account->external_id,
                'linked_at' => $account->created_at,
            ])
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function updateSocialAccount($userId, string $provider, array $data): bool
    {
        return $this->socialAccountModel::where('user_id', $userId)
            ->where('provider', $provider)
            ->update(['data' => json_encode($data)]) > 0;
    }
}
