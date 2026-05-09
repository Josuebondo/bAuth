<?php

namespace Bmvc\BAuth;

use Bmvc\BAuth\Contracts\AuthProviderInterface;
use Bmvc\BAuth\Contracts\AuthorizationProviderInterface;
use Bmvc\BAuth\Contracts\SessionProviderInterface;
use Bmvc\BAuth\Contracts\TokenProviderInterface;
use Bmvc\BAuth\Contracts\TwoFactorProviderInterface;
use Bmvc\BAuth\Exceptions\AuthenticationException;
use Bmvc\BAuth\Exceptions\AuthorizationException;
use Bmvc\BAuth\Exceptions\InvalidTokenException;
use Bmvc\BAuth\Providers\BaseAuthorizationProvider;
use Bmvc\BAuth\Providers\BaseAuthProvider;
use Bmvc\BAuth\Providers\BaseTwoFactorProvider;
use Bmvc\BAuth\Providers\JWTProvider;
use Bmvc\BAuth\Providers\SessionProvider;
use Bmvc\BAuth\Exceptions\UserNotFoundException;


/**
 * Classe principale BAuth
 */
class Auth
{
    private ?AuthProviderInterface $authProvider = null;
    private ?SessionProviderInterface $sessionProvider = null;
    private ?TokenProviderInterface $tokenProvider = null;
    private ?AuthorizationProviderInterface $authorizationProvider = null;
    private ?TwoFactorProviderInterface $twoFactorProvider = null;

    public function __construct(private Config $config) {}

    /**
     * Définir le fournisseur d'authentification
     */
    public function setAuthProvider(AuthProviderInterface $provider): self
    {
        $this->authProvider = $provider;
        return $this;
    }

    /**
     * Obtenir le fournisseur d'authentification
     */
    public function getAuthProvider(): AuthProviderInterface
    {
        if (!$this->authProvider) {
            throw new \RuntimeException('Auth provider not set');
        }
        return $this->authProvider;
    }

    /**
     * Définir le fournisseur de sessions
     */
    public function setSessionProvider(SessionProviderInterface $provider): self
    {
        $this->sessionProvider = $provider;
        return $this;
    }

    /**
     * Obtenir le fournisseur de sessions
     */
    public function getSessionProvider(): SessionProviderInterface
    {
        if (!$this->sessionProvider) {
            $this->sessionProvider = new SessionProvider($this->config);
        }
        return $this->sessionProvider;
    }

    /**
     * Définir le fournisseur de tokens
     */
    public function setTokenProvider(TokenProviderInterface $provider): self
    {
        $this->tokenProvider = $provider;
        return $this;
    }

    /**
     * Obtenir le fournisseur de tokens
     */
    public function getTokenProvider(): TokenProviderInterface
    {
        if (!$this->tokenProvider) {
            $this->tokenProvider = new JWTProvider($this->config);
        }
        return $this->tokenProvider;
    }

    /**
     * Définir le fournisseur d'autorisation
     */
    public function setAuthorizationProvider(AuthorizationProviderInterface $provider): self
    {
        $this->authorizationProvider = $provider;
        return $this;
    }

    /**
     * Obtenir le fournisseur d'autorisation
     */
    public function getAuthorizationProvider(): ?AuthorizationProviderInterface
    {
        return $this->authorizationProvider;
    }

    /**
     * Définir le fournisseur 2FA
     */
    public function setTwoFactorProvider(TwoFactorProviderInterface $provider): self
    {
        $this->twoFactorProvider = $provider;
        return $this;
    }

    /**
     * Obtenir le fournisseur 2FA
     */
    public function getTwoFactorProvider(): ?TwoFactorProviderInterface
    {
        return $this->twoFactorProvider;
    }

    /**
     * Authentifier un utilisateur
     */
    public function login(string $identifier, string $password, bool $rememberMe = false): array
    {
        $provider = $this->getAuthProvider();
        $provider->authenticate($identifier, $password);

        $user = $provider->getUser();
        $tokenProvider = $this->getTokenProvider();
        $token = $tokenProvider->generate(['user_id' => $user['id'] ?? $user['user_id']]);

        // Démarrer la session
        $this->getSessionProvider()->start($user, $token);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Vérifier 2FA
     */
    public function verify2FA(string $code): bool
    {
        if (!$this->twoFactorProvider) {
            return true; // 2FA désactivé
        }

        $userId = $this->getSessionProvider()->getUserId();
        if (!$userId) {
            throw new AuthenticationException('No user in session');
        }

        return $this->twoFactorProvider->verify($userId, $code);
    }

    /**
     * Déconnexion
     */
    public function logout(): void
    {
        $this->getSessionProvider()->destroy();
    }

    /**
     * Vérifier si l'utilisateur est authentifié
     */
    public function isAuthenticated(): bool
    {
        return $this->getSessionProvider()->isAuthenticated();
    }

    /**
     * Obtenir l'utilisateur actuel
     */
    public function user(): ?array
    {
        return $this->getSessionProvider()->get('auth_user');
    }

    /**
     * Obtenir l'ID utilisateur actuel
     */
    public function userId(): ?string
    {
        return $this->getSessionProvider()->getUserId();
    }

    /**
     * Vérifier si l'utilisateur a une permission
     */
    public function can(string $permission): bool
    {
        if (!$this->authorizationProvider) {
            return false;
        }

        $userId = $this->userId();
        if (!$userId) {
            return false;
        }

        return $this->authorizationProvider->hasPermission($userId, $permission);
    }

    /**
     * Vérifier si l'utilisateur a un rôle
     */
    public function hasRole(string $role): bool
    {
        if (!$this->authorizationProvider) {
            return false;
        }

        $userId = $this->userId();
        if (!$userId) {
            return false;
        }

        return $this->authorizationProvider->hasRole($userId, $role);
    }

    /**
     * Vérifier si l'utilisateur peut réaliser une action
     */
    public function authorize(string $permission): void
    {
        if (!$this->can($permission)) {
            throw new AuthorizationException("User does not have permission: $permission");
        }
    }

    /**
     * Obtenir le token JWT
     */
    public function token(): ?string
    {
        return $this->getSessionProvider()->get('auth_token');
    }

    /**
     * Vérifier un token JWT
     */
    public function verifyToken(string $token): ?array
    {
        return $this->getTokenProvider()->verify($token);
    }

    /**
     * Renouveler le token
     */
    public function refreshToken(): string
    {
        $token = $this->token();
        if (!$token) {
            throw new AuthenticationException('No token found');
        }

        $newToken = $this->getTokenProvider()->refresh($token);
        $this->getSessionProvider()->put('auth_token', $newToken);

        return $newToken;
    }

    /**
     * Récupérer la configuration
     */
    public function getConfig(): Config
    {
        return $this->config;
    }
}
