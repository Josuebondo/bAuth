<?php

namespace Bmvc\BAuth\Contracts;

/**
 * Interface SocialLoginProviderInterface
 * 
 * Contrat pour les fournisseurs de connexion sociale
 * Gère l'authentification via les réseaux sociaux
 * 
 * @package Bmvc\BAuth\Contracts
 */
interface SocialLoginProviderInterface
{
    /**
     * Vérifier si un utilisateur existe via un compte social
     * 
     * @param string $provider Nom du fournisseur (google, github, facebook, etc.)
     * @param string $externalId ID externe du fournisseur
     * @return bool
     */
    public function userExists(string $provider, string $externalId): bool;

    /**
     * Obtenir un utilisateur via un compte social
     * 
     * @param string $provider Nom du fournisseur
     * @param string $externalId ID externe du fournisseur
     * @return array|null Données de l'utilisateur
     */
    public function getUserByExternalId(string $provider, string $externalId): ?array;

    /**
     * Lier un compte social à un utilisateur existant
     * 
     * @param int|string $userId ID de l'utilisateur
     * @param string $provider Nom du fournisseur
     * @param string $externalId ID externe du fournisseur
     * @param array $data Données additionnelles du compte social
     * @return bool
     */
    public function linkSocialAccount(
        $userId,
        string $provider,
        string $externalId,
        array $data = []
    ): bool;

    /**
     * Délier un compte social d'un utilisateur
     * 
     * @param int|string $userId ID de l'utilisateur
     * @param string $provider Nom du fournisseur
     * @return bool
     */
    public function unlinkSocialAccount($userId, string $provider): bool;

    /**
     * Créer un nouvel utilisateur via un compte social
     * 
     * @param string $provider Nom du fournisseur
     * @param string $externalId ID externe du fournisseur
     * @param array $data Données du profil social (email, name, picture, etc.)
     * @return array Données de l'utilisateur créé
     */
    public function createUserFromSocial(
        string $provider,
        string $externalId,
        array $data
    ): array;

    /**
     * Obtenir tous les comptes sociaux liés à un utilisateur
     * 
     * @param int|string $userId ID de l'utilisateur
     * @return array Liste des comptes sociaux liés
     */
    public function getSocialAccounts($userId): array;

    /**
     * Mettre à jour les données du compte social
     * 
     * @param int|string $userId ID de l'utilisateur
     * @param string $provider Nom du fournisseur
     * @param array $data Données à mettre à jour
     * @return bool
     */
    public function updateSocialAccount($userId, string $provider, array $data): bool;
}
