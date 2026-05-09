<?php

namespace Bmvc\BAuth;

/**
 * Configuration pour BAuth
 */
class Config
{
    private array $config = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaults(), $config);
    }

    /**
     * Obtenir la configuration par clé
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

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
     * Définir une configuration
     */
    public function set(string $key, mixed $value): self
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;

        return $this;
    }

    /**
     * Configuration par défaut
     */
    private function getDefaults(): array
    {
        return [
            'jwt' => [
                'secret' => env('AUTH_JWT_SECRET', 'your-secret-key-change-me'),
                'algorithm' => 'HS256',
                'expiresIn' => 3600,
                'refreshTokenExpiresIn' => 7 * 24 * 3600,
            ],
            'password' => [
                'algorithm' => PASSWORD_BCRYPT,
                'options' => [
                    'cost' => 12,
                ]
            ],
            'session' => [
                'name' => 'bauth_session',
                'lifetime' => 7200,
            ],
            'two_factor' => [
                'enabled' => false,
                'window' => 1,
            ]
        ];
    }

    /**
     * Obtenir toute la configuration
     */
    public function all(): array
    {
        return $this->config;
    }
}

/**
 * Fonction utilitaire pour récupérer une variable d'environnement
 */
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}
