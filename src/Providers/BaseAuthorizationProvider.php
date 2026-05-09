<?php

namespace Bmvc\BAuth\Providers;

use Bmvc\BAuth\Contracts\AuthorizationProviderInterface;

/**
 * Fournisseur d'autorisation pour les rôles et permissions
 * À étendre pour implémenter votre propre logique de base de données
 */
abstract class BaseAuthorizationProvider implements AuthorizationProviderInterface
{
    /**
     * Vérifier si un utilisateur a un rôle
     */
    public function hasRole(mixed $userId, string $role): bool
    {
        $roles = $this->getRoles($userId);
        return in_array($role, $roles);
    }

    /**
     * Vérifier si un utilisateur a une permission
     */
    public function hasPermission(mixed $userId, string $permission): bool
    {
        $permissions = $this->getPermissions($userId);
        return in_array($permission, $permissions);
    }

    /**
     * Obtenir tous les rôles d'un utilisateur (à implémenter)
     */
    abstract public function getRoles(mixed $userId): array;

    /**
     * Obtenir toutes les permissions d'un utilisateur (à implémenter)
     */
    abstract public function getPermissions(mixed $userId): array;

    /**
     * Assigner un rôle à un utilisateur (à implémenter)
     */
    abstract public function assignRole(mixed $userId, string $role): bool;

    /**
     * Retirer un rôle d'un utilisateur (à implémenter)
     */
    abstract public function removeRole(mixed $userId, string $role): bool;

    /**
     * Assigner une permission à un rôle (à implémenter)
     */
    abstract public function assignPermission(string $role, string $permission): bool;
}
