<?php

namespace Bmvc\BAuth\Support;

use Bmvc\BAuth\Config;

class Password
{
    protected static Config $config;

    /**
     * Initialiser la configuration.
     */
    public static function init(Config $config): void
    {
        self::$config = $config;
    }

    /**
     * Hacher un mot de passe.
     */
    public static function hash(string $password): string
    {
        $algo = self::$config->get('password.algorithm');
        $options = self::$config->get('password.options', []);

        return password_hash($password, $algo, $options);
    }

    /**
     * Vérifier un mot de passe.
     */
    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Vérifier si un hash doit être recalculé.
     */
    public static function needsRehash(string $hash): bool
    {
        $algo = self::$config->get('password.algorithm');
        $options = self::$config->get('password.options', []);

        return password_needs_rehash($hash, $algo, $options);
    }

    /**
     * Générer un mot de passe aléatoire.
     */
    public static function generate(int $length = 16): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $password;
    }
}