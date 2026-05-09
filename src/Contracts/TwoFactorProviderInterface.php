<?php

namespace Bmvc\BAuth\Contracts;

/**
 * Interface pour l'authentification à deux facteurs (2FA)
 */
interface TwoFactorProviderInterface
{
    /**
     * Générer un code 2FA
     */
    public function generate(mixed $userId): string;

    /**
     * Vérifier un code 2FA
     */
    public function verify(mixed $userId, string $code): bool;

    /**
     * Activer 2FA pour un utilisateur
     */
    public function enable(mixed $userId): array; // Retourne le secret et les codes de récupération

    /**
     * Désactiver 2FA pour un utilisateur
     */
    public function disable(mixed $userId): bool;

    /**
     * Vérifier si 2FA est activé
     */
    public function isEnabled(mixed $userId): bool;
}
