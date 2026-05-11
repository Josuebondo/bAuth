<?php

namespace Bmvc\BAuth\Contracts;

/**
 * Interface MultiSessionProviderInterface
 * 
 * Contrat pour la gestion des sessions multiples
 * Permet à un utilisateur d'avoir plusieurs sessions actives simultanément
 * 
 * @package Bmvc\BAuth\Contracts
 */
interface MultiSessionProviderInterface
{
    /**
     * Créer une nouvelle session pour un utilisateur
     * 
     * @param int|string $userId ID de l'utilisateur
     * @param string $deviceName Nom du dispositif
     * @param string $userAgent User-Agent du navigateur
     * @param string $ipAddress Adresse IP du client
     * @return array Données de la session créée
     */
    public function createSession(
        $userId,
        string $deviceName,
        string $userAgent,
        string $ipAddress
    ): array;

    /**
     * Obtenir une session par son ID
     * 
     * @param string $sessionId ID de la session
     * @return array|null Données de la session
     */
    public function getSession(string $sessionId): ?array;

    /**
     * Obtenir toutes les sessions actives d'un utilisateur
     * 
     * @param int|string $userId ID de l'utilisateur
     * @return array Liste des sessions actives
     */
    public function getUserSessions($userId): array;

    /**
     * Terminer une session spécifique
     * 
     * @param string $sessionId ID de la session
     * @return bool
     */
    public function terminateSession(string $sessionId): bool;

    /**
     * Terminer toutes les sessions d'un utilisateur
     * 
     * @param int|string $userId ID de l'utilisateur
     * @param string|null $exceptSessionId ID de session à garder active
     * @return int Nombre de sessions terminées
     */
    public function terminateAllUserSessions($userId, ?string $exceptSessionId = null): int;

    /**
     * Vérifier si une session est valide
     * 
     * @param string $sessionId ID de la session
     * @return bool
     */
    public function isSessionValid(string $sessionId): bool;

    /**
     * Mettre à jour la dernière activité d'une session
     * 
     * @param string $sessionId ID de la session
     * @return bool
     */
    public function updateSessionActivity(string $sessionId): bool;

    /**
     * Obtenir les informations du dispositif d'une session
     * 
     * @param string $sessionId ID de la session
     * @return array Informations du dispositif
     */
    public function getSessionDeviceInfo(string $sessionId): array;

    /**
     * Détecter une session suspecte
     * 
     * @param string $sessionId ID de la session
     * @param string $userAgent Nouveau User-Agent à comparer
     * @param string $ipAddress Nouvelle adresse IP à comparer
     * @return bool True si la session semble suspecte
     */
    public function isSessionSuspicious(
        string $sessionId,
        string $userAgent,
        string $ipAddress
    ): bool;

    /**
     * Obtenir les sessions inactives
     * 
     * @param int|string $userId ID de l'utilisateur
     * @param int $inactivityThresholdSeconds Seuil d'inactivité en secondes
     * @return array Liste des sessions inactives
     */
    public function getInactiveSessions($userId, int $inactivityThresholdSeconds = 3600): array;

    /**
     * Nettoyer les sessions expirées
     * 
     * @return int Nombre de sessions supprimées
     */
    public function cleanupExpiredSessions(): int;

    /**
     * Limiter le nombre de sessions simultanées par utilisateur
     * 
     * @param int|string $userId ID de l'utilisateur
     * @param int $maxSessions Nombre maximum de sessions
     * @return int Nombre de sessions terminées
     */
    public function limitSimultaneousSessions($userId, int $maxSessions): int;
}
