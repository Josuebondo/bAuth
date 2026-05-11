<?php

namespace Bmvc\BAuth\Contracts;

/**
 * Interface WebAuthnProviderInterface
 * 
 * Contrat pour WebAuthn / Passkeys
 * Permet l'authentification sans mot de passe via WebAuthn (FIDO2, U2F, etc.)
 * 
 * @package Bmvc\BAuth\Contracts
 */
interface WebAuthnProviderInterface
{
    /**
     * Démarrer le processus d'enregistrement WebAuthn
     * 
     * @param int|string $userId ID de l'utilisateur
     * @param string $userName Nom d'utilisateur unique
     * @param string $userDisplayName Nom d'affichage de l'utilisateur
     * @return array Données de défi d'enregistrement
     */
    public function startRegistration(
        $userId,
        string $userName,
        string $userDisplayName
    ): array;

    /**
     * Compléter l'enregistrement WebAuthn
     * 
     * @param int|string $userId ID de l'utilisateur
     * @param string $credentialName Nom de l'appareil (ex: "Mon téléphone")
     * @param array $attestationResponse Réponse d'attestation du navigateur
     * @return array Données d'identification stockées
     */
    public function completeRegistration(
        $userId,
        string $credentialName,
        array $attestationResponse
    ): array;

    /**
     * Démarrer le processus d'authentification WebAuthn
     * 
     * @param string $userName Nom d'utilisateur
     * @return array Données de défi d'authentification
     */
    public function startAuthentication(string $userName): array;

    /**
     * Compléter l'authentification WebAuthn
     * 
     * @param string $userName Nom d'utilisateur
     * @param array $assertionResponse Réponse d'assertion du navigateur
     * @return array|null Données de l'utilisateur si succès
     */
    public function completeAuthentication(
        string $userName,
        array $assertionResponse
    ): ?array;

    /**
     * Obtenir tous les appareils WebAuthn enregistrés d'un utilisateur
     * 
     * @param int|string $userId ID de l'utilisateur
     * @return array Liste des appareils enregistrés
     */
    public function getUserCredentials($userId): array;

    /**
     * Supprimer un appareil WebAuthn
     * 
     * @param int|string $userId ID de l'utilisateur
     * @param string $credentialId ID de l'identification
     * @return bool
     */
    public function deleteCredential($userId, string $credentialId): bool;

    /**
     * Renommer un appareil WebAuthn
     * 
     * @param int|string $userId ID de l'utilisateur
     * @param string $credentialId ID de l'identification
     * @param string $newName Nouveau nom de l'appareil
     * @return bool
     */
    public function renameCredential($userId, string $credentialId, string $newName): bool;

    /**
     * Obtenir le statut de résistance à la clone d'un appareil
     * 
     * @param int|string $userId ID de l'utilisateur
     * @param string $credentialId ID de l'identification
     * @return array Informations de résistance à la clone
     */
    public function getCredentialBackupStatus($userId, string $credentialId): array;

    /**
     * Générer des codes de secours pour un utilisateur
     * 
     * @param int|string $userId ID de l'utilisateur
     * @param int $count Nombre de codes à générer
     * @return array Codes de secours générés
     */
    public function generateBackupCodes($userId, int $count = 10): array;

    /**
     * Valider un code de secours
     * 
     * @param int|string $userId ID de l'utilisateur
     * @param string $code Code de secours
     * @return bool
     */
    public function validateBackupCode($userId, string $code): bool;

    /**
     * Obtenir les codes de secours d'un utilisateur
     * 
     * @param int|string $userId ID de l'utilisateur
     * @return array Liste des codes de secours (masqués)
     */
    public function getUserBackupCodes($userId): array;

    /**
     * Vérifier si l'authentification sans mot de passe est configurée pour un utilisateur
     * 
     * @param int|string $userId ID de l'utilisateur
     * @return bool
     */
    public function isPasswordlessEnabled($userId): bool;

    /**
     * Activer/désactiver l'authentification sans mot de passe
     * 
     * @param int|string $userId ID de l'utilisateur
     * @param bool $enabled True pour activer, false pour désactiver
     * @return bool
     */
    public function setPasswordlessEnabled($userId, bool $enabled): bool;
}
