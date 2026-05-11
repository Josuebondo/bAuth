<?php

namespace Bmvc\BAuth\Contracts;

/**
 * Interface APIKeyProviderInterface
 * 
 * Contrat pour la gestion des clés API
 * Permet l'authentification via clés API pour les applications tierces
 * 
 * @package Bmvc\BAuth\Contracts
 */
interface APIKeyProviderInterface
{
    /**
     * Générer une nouvelle clé API
     * 
     * @param int|string $userId ID de l'utilisateur
     * @param string $name Nom de la clé API
     * @param array $permissions Permissions associées à la clé
     * @param int|null $expiresIn Durée d'expiration en secondes (null = pas d'expiration)
     * @return array Clé API générée avec le secret
     */
    public function generateApiKey(
        $userId,
        string $name,
        array $permissions = [],
        ?int $expiresIn = null
    ): array;

    /**
     * Valider une clé API
     * 
     * @param string $apiKey Clé API
     * @param string $secret Secret de la clé
     * @return bool
     */
    public function validateApiKey(string $apiKey, string $secret): bool;

    /**
     * Obtenir les informations d'une clé API
     * 
     * @param string $apiKey Clé API
     * @return array|null Informations de la clé
     */
    public function getApiKeyInfo(string $apiKey): ?array;

    /**
     * Obtenir toutes les clés API d'un utilisateur
     * 
     * @param int|string $userId ID de l'utilisateur
     * @return array Liste des clés API
     */
    public function getUserApiKeys($userId): array;

    /**
     * Révoquer une clé API
     * 
     * @param string $apiKey Clé API à révoquer
     * @return bool
     */
    public function revokeApiKey(string $apiKey): bool;

    /**
     * Révoquer toutes les clés API d'un utilisateur
     * 
     * @param int|string $userId ID de l'utilisateur
     * @return int Nombre de clés révoquées
     */
    public function revokeAllUserApiKeys($userId): int;

    /**
     * Vérifier si une clé API a une permission spécifique
     * 
     * @param string $apiKey Clé API
     * @param string $permission Permission à vérifier
     * @return bool
     */
    public function hasPermission(string $apiKey, string $permission): bool;

    /**
     * Obtenir l'utilisateur associé à une clé API
     * 
     * @param string $apiKey Clé API
     * @return array|null Données de l'utilisateur
     */
    public function getUserFromApiKey(string $apiKey): ?array;

    /**
     * Mettre à jour les permissions d'une clé API
     * 
     * @param string $apiKey Clé API
     * @param array $permissions Nouvelles permissions
     * @return bool
     */
    public function updateApiKeyPermissions(string $apiKey, array $permissions): bool;

    /**
     * Obtenir l'historique d'utilisation d'une clé API
     * 
     * @param string $apiKey Clé API
     * @param int $limit Nombre maximum de résultats
     * @return array Historique d'utilisation
     */
    public function getApiKeyUsageHistory(string $apiKey, int $limit = 100): array;
}
