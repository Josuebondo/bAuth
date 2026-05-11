<?php

namespace Bmvc\BAuth\Providers;

use Bmvc\BAuth\Contracts\WebAuthnProviderInterface;
use Exception;

/**
 * BaseWebAuthnProvider
 * 
 * Implémentation de base pour WebAuthn / Passkeys
 * 
 * @package Bmvc\BAuth\Providers
 */
class BaseWebAuthnProvider implements WebAuthnProviderInterface
{
    /**
     * Domaine/host
     */
    protected string $origin;

    /**
     * Stockage des credentials
     */
    protected array $credentials = [];

    /**
     * Stockage des défis
     */
    protected array $challenges = [];

    /**
     * Stockage des codes de secours
     */
    protected array $backupCodes = [];

    /**
     * Constructeur
     */
    public function __construct(string $origin = 'http://localhost')
    {
        $this->origin = $origin;
    }

    /**
     * {@inheritdoc}
     */
    public function startRegistration(
        $userId,
        string $userName,
        string $userDisplayName
    ): array {
        $challenge = bin2hex(random_bytes(32));

        // Stocker le défi
        $this->challenges[$challenge] = [
            'user_id' => $userId,
            'username' => $userName,
            'created_at' => time(),
            'type' => 'registration',
        ];

        return [
            'challenge' => base64_encode(hex2bin($challenge)),
            'rp' => [
                'name' => 'BAuth',
                'id' => parse_url($this->origin, PHP_URL_HOST),
            ],
            'user' => [
                'id' => base64_encode((string)$userId),
                'name' => $userName,
                'displayName' => $userDisplayName,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],  // ES256
                ['type' => 'public-key', 'alg' => -257], // RS256
            ],
            'timeout' => 60000,
            'attestation' => 'direct',
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'cross-platform',
                'residentKey' => 'preferred',
                'userVerification' => 'preferred',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function completeRegistration(
        $userId,
        string $credentialName,
        array $attestationResponse
    ): array {
        // Décoder la réponse d'attestation
        $clientData = json_decode(
            base64_decode(strtr($attestationResponse['response']['clientDataJSON'], '-_', '+/')),
            true
        );

        if ($clientData['type'] !== 'webauthn.create') {
            throw new Exception('Type de clientData invalide');
        }

        // Générer l'ID de credential
        $credentialId = bin2hex(random_bytes(32));

        $this->credentials[$credentialId] = [
            'user_id' => $userId,
            'credential_name' => $credentialName,
            'credential_id' => $credentialId,
            'public_key' => $attestationResponse['response']['attestationObject'] ?? null,
            'sign_count' => 0,
            'created_at' => time(),
            'last_used_at' => null,
            'backup_eligible' => $attestationResponse['response']['transports'] ?? [],
            'backup_state' => false,
            'passwordless_enabled' => true,
        ];

        return [
            'credential_id' => $credentialId,
            'credential_name' => $credentialName,
            'created_at' => time(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function startAuthentication(string $userName): array
    {
        $challenge = bin2hex(random_bytes(32));

        $this->challenges[$challenge] = [
            'username' => $userName,
            'created_at' => time(),
            'type' => 'authentication',
        ];

        // Obtenir toutes les credentials de l'utilisateur
        $credentials = [];
        foreach ($this->credentials as $credentialId => $credential) {
            // Nous aurions normalement besoin de chercher par userName
            $credentials[] = [
                'type' => 'public-key',
                'id' => base64_encode(hex2bin($credentialId)),
            ];
        }

        return [
            'challenge' => base64_encode(hex2bin($challenge)),
            'timeout' => 60000,
            'rpId' => parse_url($this->origin, PHP_URL_HOST),
            'allowCredentials' => $credentials,
            'userVerification' => 'preferred',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function completeAuthentication(
        string $userName,
        array $assertionResponse
    ): ?array {
        $clientData = json_decode(
            base64_decode(strtr($assertionResponse['response']['clientDataJSON'], '-_', '+/')),
            true
        );

        if ($clientData['type'] !== 'webauthn.get') {
            throw new Exception('Type de clientData invalide');
        }

        // Trouver la credential
        $credentialId = bin2hex(random_bytes(32)); // Simulé

        if (!isset($this->credentials[$credentialId])) {
            return null;
        }

        $credential = $this->credentials[$credentialId];

        // Mettre à jour la dernière utilisation
        $this->credentials[$credentialId]['last_used_at'] = time();
        $this->credentials[$credentialId]['sign_count']++;

        // Retourner les données de l'utilisateur
        return [
            'id' => $credential['user_id'],
            'username' => $userName,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserCredentials($userId): array
    {
        $userCredentials = [];

        foreach ($this->credentials as $credentialId => $credential) {
            if ($credential['user_id'] == $userId) {
                $userCredentials[] = [
                    'credential_id' => $credential['credential_id'],
                    'credential_name' => $credential['credential_name'],
                    'created_at' => $credential['created_at'],
                    'last_used_at' => $credential['last_used_at'],
                    'backup_eligible' => $credential['backup_eligible'],
                    'backup_state' => $credential['backup_state'],
                ];
            }
        }

        return $userCredentials;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteCredential($userId, string $credentialId): bool
    {
        if (!isset($this->credentials[$credentialId])) {
            return false;
        }

        if ($this->credentials[$credentialId]['user_id'] != $userId) {
            return false;
        }

        unset($this->credentials[$credentialId]);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function renameCredential($userId, string $credentialId, string $newName): bool
    {
        if (!isset($this->credentials[$credentialId])) {
            return false;
        }

        if ($this->credentials[$credentialId]['user_id'] != $userId) {
            return false;
        }

        $this->credentials[$credentialId]['credential_name'] = $newName;
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentialBackupStatus($userId, string $credentialId): array
    {
        if (!isset($this->credentials[$credentialId])) {
            return [];
        }

        if ($this->credentials[$credentialId]['user_id'] != $userId) {
            return [];
        }

        $credential = $this->credentials[$credentialId];

        return [
            'backup_eligible' => $credential['backup_eligible'],
            'backup_state' => $credential['backup_state'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function generateBackupCodes($userId, int $count = 10): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $code = strtoupper(bin2hex(random_bytes(4)));
            $code = substr($code, 0, 4) . '-' . substr($code, 4, 4);

            $codeHash = hash('sha256', $code);

            $this->backupCodes[$codeHash] = [
                'user_id' => $userId,
                'used' => false,
                'created_at' => time(),
            ];

            $codes[] = $code;
        }

        return $codes;
    }

    /**
     * {@inheritdoc}
     */
    public function validateBackupCode($userId, string $code): bool
    {
        $codeHash = hash('sha256', $code);

        if (!isset($this->backupCodes[$codeHash])) {
            return false;
        }

        $backupCode = $this->backupCodes[$codeHash];

        if ($backupCode['user_id'] != $userId) {
            return false;
        }

        if ($backupCode['used']) {
            return false;
        }

        // Marquer comme utilisé
        $this->backupCodes[$codeHash]['used'] = true;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserBackupCodes($userId): array
    {
        $codes = [];

        foreach ($this->backupCodes as $codeHash => $code) {
            if ($code['user_id'] == $userId && !$code['used']) {
                $codes[] = [
                    'code' => '****-' . substr($codeHash, -4),
                    'created_at' => $code['created_at'],
                ];
            }
        }

        return $codes;
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordlessEnabled($userId): bool
    {
        foreach ($this->credentials as $credential) {
            if ($credential['user_id'] == $userId) {
                return $credential['passwordless_enabled'] === true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function setPasswordlessEnabled($userId, bool $enabled): bool
    {
        $found = false;

        foreach ($this->credentials as $credentialId => $credential) {
            if ($credential['user_id'] == $userId) {
                $this->credentials[$credentialId]['passwordless_enabled'] = $enabled;
                $found = true;
            }
        }

        return $found;
    }
}
