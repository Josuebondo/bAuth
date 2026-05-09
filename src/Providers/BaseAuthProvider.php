<?php

namespace BAuth\Providers;

use BAuth\Config;
use BAuth\Contracts\AuthProviderInterface;
use BAuth\Exceptions\AuthenticationException;
use BAuth\Exceptions\UserNotFoundException;
use BAuth\Support\Password;

/**
 * Fournisseur d'authentification par défaut
 * À étendre pour implémenter votre propre logique de base de données
 */
abstract class BaseAuthProvider implements AuthProviderInterface
{
    protected ?array $user = null;
    protected Password $password;

    public function __construct(protected Config $config)
    {
        $this->password = new Password($config);
    }

    /**
     * Authentifier un utilisateur
     */
    public function authenticate(string $identifier, string $password): bool
    {
        // Chercher l'utilisateur par email ou par identifiant
        $user = $this->getUserByEmail($identifier) ?? $this->getUserByIdentifier($identifier);

        if (!$user) {
            throw new UserNotFoundException("User not found");
        }

        if (!$this->validateCredentials($user, $password)) {
            throw new AuthenticationException("Invalid credentials");
        }

        $this->user = $user;
        return true;
    }

    /**
     * Récupérer l'utilisateur courant
     */
    public function getUser(): ?array
    {
        return $this->user;
    }

    /**
     * Vérifier les identifiants
     */
    public function validateCredentials(array $user, string $password): bool
    {
        return $this->password->verify($password, $user['password'] ?? '');
    }

    /**
     * Récupérer un utilisateur par son identifiant (à implémenter)
     */
    abstract public function getUserByIdentifier(string $identifier): ?array;

    /**
     * Récupérer un utilisateur par son email (à implémenter)
     */
    abstract public function getUserByEmail(string $email): ?array;

    /**
     * Récupérer un utilisateur par son ID (à implémenter)
     */
    abstract public function getUserById(mixed $id): ?array;

    /**
     * Créer un utilisateur (à implémenter)
     */
    abstract public function createUser(array $userData): ?array;

    /**
     * Mettre à jour un utilisateur (à implémenter)
     */
    abstract public function updateUser(mixed $userId, array $data): bool;

    /**
     * Supprimer un utilisateur (à implémenter)
     */
    abstract public function deleteUser(mixed $userId): bool;
}
