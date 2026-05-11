<?php

namespace Bmvc\BAuth\Contracts;

/**
 * Interface OAuth2ProviderInterface
 * 
 * Contrat pour les providers OAuth2 (Google, GitHub, Facebook, etc.)
 * Permet l'authentification via des fournisseurs tiers OAuth2
 * 
 * @package Bmvc\BAuth\Contracts
 */
interface OAuth2ProviderInterface
{
    /**
     * Obtenir l'URL d'autorisation OAuth2
     * 
     * @param string $provider Nom du fournisseur (google, github, facebook, etc.)
     * @param string $state État de sécurité CSRF
     * @return string URL de redirection
     */
    public function getAuthorizationUrl(string $provider, string $state): string;

    /**
     * Traiter le callback OAuth2
     * 
     * @param string $provider Nom du fournisseur
     * @param string $code Code d'autorisation
     * @param string $state État pour vérifier la sécurité
     * @return array Données de l'utilisateur
     */
    public function handleCallback(string $provider, string $code, string $state): array;

    /**
     * Obtenir les informations utilisateur via token d'accès
     * 
     * @param string $provider Nom du fournisseur
     * @param string $accessToken Token d'accès
     * @return array Données utilisateur
     */
    public function getUserInfo(string $provider, string $accessToken): array;

    /**
     * Enregistrer un nouveau fournisseur OAuth2
     * 
     * @param string $name Nom du fournisseur
     * @param string $clientId ID client
     * @param string $clientSecret Secret client
     * @param string $redirectUri URI de redirection
     * @return void
     */
    public function registerProvider(
        string $name,
        string $clientId,
        string $clientSecret,
        string $redirectUri
    ): void;

    /**
     * Rafraîchir le token d'accès
     * 
     * @param string $provider Nom du fournisseur
     * @param string $refreshToken Token de rafraîchissement
     * @return array Nouveau token d'accès et expiration
     */
    public function refreshAccessToken(string $provider, string $refreshToken): array;

    /**
     * Révoquer le token d'accès
     * 
     * @param string $provider Nom du fournisseur
     * @param string $accessToken Token d'accès
     * @return bool
     */
    public function revokeAccessToken(string $provider, string $accessToken): bool;
}
