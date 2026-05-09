<?php

namespace Bmvc\BAuth\Contracts;

/**
 * Interface pour la gestion des rôles et permissions
 */
interface AuthorizationProviderInterface
{
    /**
     * Vérifier si un utilisateur a un rôle
     */
    public function hasRole(mixed $userId, string $role): bool;

    /**
     * Vérifier si un utilisateur a une permission
     */
    public function hasPermission(mixed $userId, string $permission): bool;

    /**
     * Assigner un rôle à un utilisateur
     */
    public function assignRole(mixed $userId, string $role): bool;

    /**
     * Retirer un rôle d'un utilisateur
     */
    public function removeRole(mixed $userId, string $role): bool;

    /**
     * Assigner une permission à un rôle
     */
    public function assignPermission(string $role, string $permission): bool;

    /**
     * Obtenir tous les rôles d'un utilisateur
     */
    public function getRoles(mixed $userId): array;

    /**
     * Obtenir toutes les permissions d'un utilisateur
     */
    public function getPermissions(mixed $userId): array;
}
