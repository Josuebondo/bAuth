<?php

namespace Bmvc\BAuth\Providers;

use Bmvc\BAuth\Contracts\TwoFactorProviderInterface;

/**
 * Fournisseur d'authentification à deux facteurs
 * À étendre pour implémenter votre propre logique
 */
abstract class BaseTwoFactorProvider implements TwoFactorProviderInterface
{
    /**
     * Générer un code TOTP (Time-based One-Time Password)
     */
    public function generate(mixed $userId): string
    {
        $secret = $this->getSecret($userId);
        if (!$secret) {
            return '';
        }

        return $this->generateTOTP($secret);
    }

    /**
     * Vérifier un code TOTP
     */
    public function verify(mixed $userId, string $code): bool
    {
        if (!$this->isEnabled($userId)) {
            return false;
        }

        $secret = $this->getSecret($userId);
        if (!$secret) {
            return false;
        }

        // Vérifier le code actuel et les codes précédents/suivants pour la fenêtre
        $currentTime = floor(time() / 30);
        $window = 1;

        for ($i = -$window; $i <= $window; $i++) {
            $totp = $this->generateTOTP($secret, $currentTime + $i);
            if (hash_equals($code, $totp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Générer un TOTP basé sur le temps
     */
    protected function generateTOTP(string $secret, int $time = null): string
    {
        $time = $time ?? floor(time() / 30);
        $base32Alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binaryString = '';

        // Décoder le secret en base32
        for ($i = 0; $i < strlen($secret); $i++) {
            $char = $secret[$i];
            $value = strpos($base32Alphabet, $char);
            if ($value === false) {
                continue;
            }
            $binaryString .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
        }

        // Extraire les bits pertinents
        $binaryString = substr($binaryString, 0, 64);
        $timeBytes = '';

        for ($i = 0; $i < 8; $i++) {
            $timeBytes .= chr(($time >> ((7 - $i) * 8)) & 0xff);
        }

        // Générer le HMAC
        $hmac = hash_hmac('sha1', $timeBytes, $this->base32Decode($secret), true);

        $offset = ord($hmac[19]) & 0xf;
        $code = (
            ((ord($hmac[$offset]) & 0x7f) << 24) |
            ((ord($hmac[$offset + 1]) & 0xff) << 16) |
            ((ord($hmac[$offset + 2]) & 0xff) << 8) |
            (ord($hmac[$offset + 3]) & 0xff)
        ) % 1000000;

        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Décoder une chaîne en base32
     */
    protected function base32Decode(string $input): string
    {
        $base32Alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;

        for ($i = 0; $i < strlen($input); $i++) {
            $v <<= 5;
            $v += strpos($base32Alphabet, $input[$i]);
            $vbits += 5;

            if ($vbits >= 8) {
                $vbits -= 8;
                $output .= chr(($v >> $vbits) & 255);
                $v &= ((1 << $vbits) - 1);
            }
        }

        return $output;
    }

    /**
     * Obtenir le secret pour un utilisateur (à implémenter)
     */
    abstract protected function getSecret(mixed $userId): ?string;

    /**
     * Activer 2FA pour un utilisateur (à implémenter)
     */
    abstract public function enable(mixed $userId): array;

    /**
     * Désactiver 2FA pour un utilisateur (à implémenter)
     */
    abstract public function disable(mixed $userId): bool;

    /**
     * Vérifier si 2FA est activé (à implémenter)
     */
    abstract public function isEnabled(mixed $userId): bool;
}
