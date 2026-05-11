<?php

namespace Bmvc\BAuth\Providers;

use Bmvc\BAuth\Contracts\OAuth2ProviderInterface;
use Exception;

/**
 * BaseOAuth2Provider
 * 
 * Implémentation de base pour OAuth2
 * Gère l'authentification via les fournisseurs OAuth2
 * 
 * @package Bmvc\BAuth\Providers
 */
class BaseOAuth2Provider implements OAuth2ProviderInterface
{
    /**
     * Configurations des fournisseurs
     */
    protected array $providers = [];

    /**
     * Stockage des états CSRF
     */
    protected array $states = [];

    /**
     * Callback pour obtenir un utilisateur par email
     */
    protected $getUserByEmailCallback;

    /**
     * Callback pour créer un utilisateur
     */
    protected $createUserCallback;

    /**
     * Callback pour mettre à jour un utilisateur
     */
    protected $updateUserCallback;

    /**
     * Définir le callback pour obtenir un utilisateur par email
     */
    public function setGetUserByEmailCallback($callback)
    {
        $this->getUserByEmailCallback = $callback;
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
     * Définir le callback pour mettre à jour un utilisateur
     */
    public function setUpdateUserCallback($callback)
    {
        $this->updateUserCallback = $callback;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function registerProvider(
        string $name,
        string $clientId,
        string $clientSecret,
        string $redirectUri
    ): void {
        $this->providers[$name] = [
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri' => $redirectUri,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationUrl(string $provider, string $state): string
    {
        if (!isset($this->providers[$provider])) {
            throw new Exception("Fournisseur OAuth2 '$provider' non configuré");
        }

        // Stocker l'état pour validation lors du callback
        $this->states[$state] = [
            'provider' => $provider,
            'created_at' => time(),
        ];

        $config = $this->providers[$provider];
        $authorizationUrl = $this->getProviderAuthorizationUrl($provider);

        $params = [
            'client_id' => $config['clientId'],
            'redirect_uri' => $config['redirectUri'],
            'response_type' => 'code',
            'scope' => $this->getProviderScopes($provider),
            'state' => $state,
        ];

        return $authorizationUrl . '?' . http_build_query($params);
    }

    /**
     * {@inheritdoc}
     */
    public function handleCallback(string $provider, string $code, string $state): array
    {
        if (!isset($this->providers[$provider])) {
            throw new Exception("Fournisseur OAuth2 '$provider' non configuré");
        }

        // Valider l'état
        if (!isset($this->states[$state])) {
            throw new Exception("État CSRF invalide");
        }

        if ($this->states[$state]['provider'] !== $provider) {
            throw new Exception("Le fournisseur ne correspond pas à l'état");
        }

        // Vérifier l'âge de l'état (max 10 minutes)
        if (time() - $this->states[$state]['created_at'] > 600) {
            throw new Exception("État CSRF expiré");
        }

        // Récupérer le token d'accès
        $accessTokenData = $this->exchangeCodeForToken($provider, $code);

        // Récupérer les informations de l'utilisateur
        $userInfo = $this->getUserInfo($provider, $accessTokenData['access_token']);

        // Nettoyer l'état
        unset($this->states[$state]);

        return [
            'user_info' => $userInfo,
            'access_token' => $accessTokenData['access_token'],
            'refresh_token' => $accessTokenData['refresh_token'] ?? null,
            'expires_in' => $accessTokenData['expires_in'] ?? 3600,
            'token_type' => $accessTokenData['token_type'] ?? 'Bearer',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserInfo(string $provider, string $accessToken): array
    {
        $userInfoUrl = $this->getProviderUserInfoUrl($provider);

        $response = $this->makeHttpRequest('GET', $userInfoUrl, [
            'Authorization' => 'Bearer ' . $accessToken,
        ]);

        return $this->normalizeUserInfo($provider, json_decode($response, true));
    }

    /**
     * {@inheritdoc}
     */
    public function refreshAccessToken(string $provider, string $refreshToken): array
    {
        if (!isset($this->providers[$provider])) {
            throw new Exception("Fournisseur OAuth2 '$provider' non configuré");
        }

        $config = $this->providers[$provider];
        $tokenUrl = $this->getProviderTokenUrl($provider);

        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $config['clientId'],
            'client_secret' => $config['clientSecret'],
        ];

        $response = $this->makeHttpRequest(
            'POST',
            $tokenUrl,
            [],
            http_build_query($data)
        );

        $data = json_decode($response, true);

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $refreshToken,
            'expires_in' => $data['expires_in'] ?? 3600,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAccessToken(string $provider, string $accessToken): bool
    {
        $revokeUrl = $this->getProviderRevokeUrl($provider);

        if (!$revokeUrl) {
            return false;
        }

        $config = $this->providers[$provider];

        $data = [
            'token' => $accessToken,
            'client_id' => $config['clientId'],
            'client_secret' => $config['clientSecret'],
        ];

        try {
            $this->makeHttpRequest(
                'POST',
                $revokeUrl,
                [],
                http_build_query($data)
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Échanger un code d'autorisation pour un token d'accès
     */
    protected function exchangeCodeForToken(string $provider, string $code): array
    {
        $config = $this->providers[$provider];
        $tokenUrl = $this->getProviderTokenUrl($provider);

        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $config['redirectUri'],
            'client_id' => $config['clientId'],
            'client_secret' => $config['clientSecret'],
        ];

        $response = $this->makeHttpRequest(
            'POST',
            $tokenUrl,
            [],
            http_build_query($data)
        );

        return json_decode($response, true);
    }

    /**
     * Faire une requête HTTP
     */
    protected function makeHttpRequest(
        string $method,
        string $url,
        array $headers = [],
        ?string $body = null
    ): string {
        $options = [
            'http' => [
                'method' => $method,
                'header' => array_map(
                    fn($k, $v) => "$k: $v",
                    array_keys($headers),
                    $headers
                ),
                'ignore_errors' => true,
            ],
        ];

        if ($body) {
            $options['http']['content'] = $body;
            if (!isset($headers['Content-Type'])) {
                $options['http']['header'][] = 'Content-Type: application/x-www-form-urlencoded';
            }
        }

        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            throw new Exception("Erreur lors de la requête HTTP vers $url");
        }

        return $result;
    }

    /**
     * Obtenir l'URL d'autorisation du fournisseur
     */
    protected function getProviderAuthorizationUrl(string $provider): string
    {
        $urls = [
            'google' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'github' => 'https://github.com/login/oauth/authorize',
            'facebook' => 'https://www.facebook.com/v18.0/dialog/oauth',
            'microsoft' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
        ];

        return $urls[$provider] ?? throw new Exception("Fournisseur '$provider' non supporté");
    }

    /**
     * Obtenir l'URL du token du fournisseur
     */
    protected function getProviderTokenUrl(string $provider): string
    {
        $urls = [
            'google' => 'https://oauth2.googleapis.com/token',
            'github' => 'https://github.com/login/oauth/access_token',
            'facebook' => 'https://graph.facebook.com/v18.0/oauth/access_token',
            'microsoft' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
        ];

        return $urls[$provider] ?? throw new Exception("Fournisseur '$provider' non supporté");
    }

    /**
     * Obtenir l'URL des informations utilisateur du fournisseur
     */
    protected function getProviderUserInfoUrl(string $provider): string
    {
        $urls = [
            'google' => 'https://www.googleapis.com/oauth2/v2/userinfo',
            'github' => 'https://api.github.com/user',
            'facebook' => 'https://graph.facebook.com/me?fields=id,name,email,picture',
            'microsoft' => 'https://graph.microsoft.com/v1.0/me',
        ];

        return $urls[$provider] ?? throw new Exception("Fournisseur '$provider' non supporté");
    }

    /**
     * Obtenir l'URL de révocation du token du fournisseur
     */
    protected function getProviderRevokeUrl(string $provider): ?string
    {
        $urls = [
            'google' => 'https://oauth2.googleapis.com/revoke',
            'github' => null, // GitHub ne supporte pas la révocation
            'facebook' => null,
            'microsoft' => 'https://login.microsoftonline.com/common/oauth2/v2.0/logout',
        ];

        return $urls[$provider] ?? null;
    }

    /**
     * Obtenir les scopes du fournisseur
     */
    protected function getProviderScopes(string $provider): string
    {
        $scopes = [
            'google' => 'openid email profile',
            'github' => 'user:email',
            'facebook' => 'email public_profile',
            'microsoft' => 'openid email profile',
        ];

        return $scopes[$provider] ?? 'openid email profile';
    }

    /**
     * Normaliser les informations utilisateur du fournisseur
     */
    protected function normalizeUserInfo(string $provider, array $data): array
    {
        $mapping = [
            'google' => [
                'id' => 'id',
                'email' => 'email',
                'name' => 'name',
                'picture' => 'picture',
            ],
            'github' => [
                'id' => 'id',
                'email' => 'email',
                'name' => 'name',
                'picture' => 'avatar_url',
            ],
            'facebook' => [
                'id' => 'id',
                'email' => 'email',
                'name' => 'name',
                'picture' => 'picture.data.url',
            ],
            'microsoft' => [
                'id' => 'id',
                'email' => 'mail',
                'name' => 'displayName',
            ],
        ];

        $map = $mapping[$provider] ?? $mapping['google'];
        $normalized = [];

        foreach ($map as $key => $field) {
            $value = $data;
            foreach (explode('.', $field) as $part) {
                $value = $value[$part] ?? null;
                if ($value === null) break;
            }
            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
