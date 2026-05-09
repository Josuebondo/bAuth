<?php

namespace Bmvc\BAuth\Contracts;

/**
 * Interface pour la gestion des sessions
 */
interface SessionProviderInterface
{
    /**
     * Démarrer une session
     */
    public function start(array $userData, string $token): void;

    /**
     * Récupérer les données de session
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Définir une valeur dans la session
     */
    public function put(string $key, mixed $value): void;

    /**
     * Supprimer une valeur de session
     */
    public function forget(string $key): void;

    /**
     * Détruire la session
     */
    public function destroy(): void;

    /**
     * Vérifier si l'utilisateur est authentifié
     */
    public function isAuthenticated(): bool;

    /**
     * Récupérer l'ID utilisateur
     */
    public function getUserId(): ?string;
}
