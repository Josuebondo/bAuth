<?php

namespace Bmvc\BAuth\Providers;

use Bmvc\BAuth\Config;
use Bmvc\BAuth\Contracts\SessionProviderInterface;

/**
 * Fournisseur de sessions PHP
 */
class SessionProvider implements SessionProviderInterface
{
    private bool $sessionStarted = false;

    public function __construct(private Config $config)
    {
        $this->startSession();
    }

    /**
     * Démarrer une session PHP
     */
    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name($this->config->get('session.name', 'bauth_session'));
            session_start();
            $this->sessionStarted = true;
        }
    }

    /**
     * Démarrer une session utilisateur
     */
    public function start(array $userData, string $token): void
    {
        $_SESSION['auth_user'] = $userData;
        $_SESSION['auth_token'] = $token;
        $_SESSION['authenticated_at'] = time();
    }

    /**
     * Récupérer une valeur de session
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $_SESSION;

        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Définir une valeur dans la session
     */
    public function put(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $session = &$_SESSION;

        foreach ($keys as $k) {
            if (!isset($session[$k])) {
                $session[$k] = [];
            }
            $session = &$session[$k];
        }

        $session = $value;
    }

    /**
     * Supprimer une valeur de session
     */
    public function forget(string $key): void
    {
        $keys = explode('.', $key);
        $session = &$_SESSION;

        foreach ($keys as $i => $k) {
            if (!isset($session[$k])) {
                return;
            }

            if ($i === count($keys) - 1) {
                unset($session[$k]);
            } else {
                $session = &$session[$k];
            }
        }
    }

    /**
     * Détruire la session
     */
    public function destroy(): void
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        if ($this->sessionStarted) {
            session_destroy();
        }
    }

    /**
     * Vérifier si l'utilisateur est authentifié
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION['auth_user']) && !empty($_SESSION['auth_user']);
    }

    /**
     * Récupérer l'ID utilisateur
     */
    public function getUserId(): ?string
    {
        return $_SESSION['auth_user']['id'] ?? null;
    }
}
