<?php

namespace Bmvc\BAuth\Contracts;

/**
 * Interface pour les fournisseurs d'authentification
 */
interface AuthProviderInterface
{
    /**
     * Authentifier un utilisateur
     */
    public function authenticate(string $identifier, string $password): bool;

    /**
     * Récupérer l'utilisateur courant
     */
    public function getUser(): ?array;

    /**
     * Récupérer un utilisateur par son identifiant
     */
    public function getUserById(mixed $id): ?array;

    /**
     * Récupérer un utilisateur par son email
     */
    public function getUserByEmail(string $email): ?array;

    /**
     * Créer un nouvel utilisateur
     */
    public function createUser(array $userData): ?array;

    /**
     * Mettre à jour un utilisateur
     */
    public function updateUser(mixed $userId, array $data): bool;

    /**
     * Supprimer un utilisateur
     */
    public function deleteUser(mixed $userId): bool;

    /**
     * Vérifier les identifiants
     */
    public function validateCredentials(array $user, string $password): bool;
}
