<?php

namespace Bmvc\BAuth\Adapters\Laravel;

use Bmvc\BAuth\Providers\BaseWebAuthnProvider;

/**
 * LaravelWebAuthnProvider
 * 
 * Adapter WebAuthn pour Laravel
 * Gère les Passkeys avec la base de données Laravel
 * 
 * @package Bmvc\BAuth\Adapters\Laravel
 */
class LaravelWebAuthnProvider extends BaseWebAuthnProvider
{
    /**
     * Modèle des credentials
     */
    protected string $credentialModel;

    /**
     * Modèle des codes de secours
     */
    protected string $backupCodeModel;

    /**
     * Constructeur
     */
    public function __construct(
        string $origin = 'http://localhost',
        string $credentialModel = '\App\Models\WebAuthnCredential',
        string $backupCodeModel = '\App\Models\WebAuthnBackupCode'
    ) {
        parent::__construct($origin);
        $this->credentialModel = $credentialModel;
        $this->backupCodeModel = $backupCodeModel;
    }

    /**
     * {@inheritdoc}
     */
    public function completeRegistration(
        $userId,
        string $credentialName,
        array $attestationResponse
    ): array {
        $credentialId = bin2hex(random_bytes(32));

        $record = $this->credentialModel::create([
            'user_id' => $userId,
            'credential_id' => $credentialId,
            'credential_name' => $credentialName,
            'public_key' => $attestationResponse['response']['attestationObject'] ?? null,
            'sign_count' => 0,
            'backup_eligible' => json_encode($attestationResponse['response']['transports'] ?? []),
            'passwordless_enabled' => true,
        ]);

        return [
            'credential_id' => $credentialId,
            'credential_name' => $credentialName,
            'created_at' => $record->created_at,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserCredentials($userId): array
    {
        return $this->credentialModel::where('user_id', $userId)
            ->get()
            ->map(fn($credential) => [
                'credential_id' => $credential->credential_id,
                'credential_name' => $credential->credential_name,
                'created_at' => $credential->created_at,
                'last_used_at' => $credential->last_used_at,
                'backup_eligible' => json_decode($credential->backup_eligible, true),
                'backup_state' => (bool) $credential->backup_state,
            ])
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteCredential($userId, string $credentialId): bool
    {
        return $this->credentialModel::where('user_id', $userId)
            ->where('credential_id', $credentialId)
            ->delete() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function renameCredential($userId, string $credentialId, string $newName): bool
    {
        return $this->credentialModel::where('user_id', $userId)
            ->where('credential_id', $credentialId)
            ->update(['credential_name' => $newName]) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentialBackupStatus($userId, string $credentialId): array
    {
        $credential = $this->credentialModel::where('user_id', $userId)
            ->where('credential_id', $credentialId)
            ->first();

        if (!$credential) {
            return [];
        }

        return [
            'backup_eligible' => json_decode($credential->backup_eligible, true),
            'backup_state' => (bool) $credential->backup_state,
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

            $this->backupCodeModel::create([
                'user_id' => $userId,
                'code_hash' => $codeHash,
                'used' => false,
            ]);

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

        $backupCode = $this->backupCodeModel::where('user_id', $userId)
            ->where('code_hash', $codeHash)
            ->where('used', false)
            ->first();

        if (!$backupCode) {
            return false;
        }

        $backupCode->update(['used' => true]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserBackupCodes($userId): array
    {
        return $this->backupCodeModel::where('user_id', $userId)
            ->where('used', false)
            ->get()
            ->map(fn($code) => [
                'code' => '****-' . substr($code->code_hash, -4),
                'created_at' => $code->created_at,
            ])
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordlessEnabled($userId): bool
    {
        return $this->credentialModel::where('user_id', $userId)
            ->where('passwordless_enabled', true)
            ->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function setPasswordlessEnabled($userId, bool $enabled): bool
    {
        return $this->credentialModel::where('user_id', $userId)
            ->update(['passwordless_enabled' => $enabled]) > 0;
    }
}
