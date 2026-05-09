<?php

namespace Bmvc\BAuth\Support;

use Bmvc\BAuth\Config;

/**
 * Utilitaire pour le hachage et la vérification des mots de passe
 */
class Password
{
    public function __construct(private Config $config) {}

    /**
     * Hacher un mot de passe
     */
    public function hash(string $password): string
    {
        $algo = $this->config->get('password.algorithm');
        $options = $this->config->get('password.options', []);

        return password_hash($password, $algo, $options);
    }

    /**
     * Vérifier un mot de passe
     */
    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Vérifier si un hash doit être recalculé
     */
    public function needsRehash(string $hash): bool
    {
        $algo = $this->config->get('password.algorithm');
        $options = $this->config->get('password.options', []);

        return password_needs_rehash($hash, $algo, $options);
    }

    /**
     * Générer un mot de passe aléatoire
     */
    public function generate(int $length = 16): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $password;
    }
}
