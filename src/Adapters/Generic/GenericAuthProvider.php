<?php

namespace Bmvc\BAuth\Adapters\Generic;

use Bmvc\BAuth\Providers\BaseAuthProvider;

/**
 * Fournisseur d'authentification générique utilisant des callbacks
 * Parfait pour les projets PHP génériques
 */
class GenericAuthProvider extends BaseAuthProvider
{
    private \Closure $getUserByIdentifierCallback;
    private \Closure $getUserByEmailCallback;
    private \Closure $getUserByIdCallback;
    private \Closure $createUserCallback;
    private \Closure $updateUserCallback;
    private \Closure $deleteUserCallback;

    public function __construct($config)
    {
        parent::__construct($config);
    }

    /**
     * Définir le callback pour récupérer un utilisateur par identifiant
     */
    public function setGetUserByIdentifierCallback(\Closure $callback): self
    {
        $this->getUserByIdentifierCallback = $callback;
        return $this;
    }

    /**
     * Définir le callback pour récupérer un utilisateur par email
     */
    public function setGetUserByEmailCallback(\Closure $callback): self
    {
        $this->getUserByEmailCallback = $callback;
        return $this;
    }

    /**
     * Définir le callback pour récupérer un utilisateur par ID
     */
    public function setGetUserByIdCallback(\Closure $callback): self
    {
        $this->getUserByIdCallback = $callback;
        return $this;
    }

    /**
     * Définir le callback pour créer un utilisateur
     */
    public function setCreateUserCallback(\Closure $callback): self
    {
        $this->createUserCallback = $callback;
        return $this;
    }

    /**
     * Définir le callback pour mettre à jour un utilisateur
     */
    public function setUpdateUserCallback(\Closure $callback): self
    {
        $this->updateUserCallback = $callback;
        return $this;
    }

    /**
     * Définir le callback pour supprimer un utilisateur
     */
    public function setDeleteUserCallback(\Closure $callback): self
    {
        $this->deleteUserCallback = $callback;
        return $this;
    }

    /**
     * Récupérer un utilisateur par son identifiant
     */
    public function getUserByIdentifier(string $identifier): ?array
    {
        if (!isset($this->getUserByIdentifierCallback)) {
            throw new \RuntimeException('getUserByIdentifier callback not set');
        }

        return ($this->getUserByIdentifierCallback)($identifier);
    }

    /**
     * Récupérer un utilisateur par son email
     */
    public function getUserByEmail(string $email): ?array
    {
        if (!isset($this->getUserByEmailCallback)) {
            throw new \RuntimeException('getUserByEmail callback not set');
        }

        return ($this->getUserByEmailCallback)($email);
    }

    /**
     * Récupérer un utilisateur par son ID
     */
    public function getUserById(mixed $id): ?array
    {
        if (!isset($this->getUserByIdCallback)) {
            throw new \RuntimeException('getUserById callback not set');
        }

        return ($this->getUserByIdCallback)($id);
    }

    /**
     * Créer un utilisateur
     */
    public function createUser(array $userData): ?array
    {
        if (!isset($this->createUserCallback)) {
            throw new \RuntimeException('createUser callback not set');
        }

        $userData['password'] = $this->password->hash($userData['password'] ?? '');

        return ($this->createUserCallback)($userData);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function updateUser(mixed $userId, array $data): bool
    {
        if (!isset($this->updateUserCallback)) {
            throw new \RuntimeException('updateUser callback not set');
        }

        if (isset($data['password'])) {
            $data['password'] = $this->password->hash($data['password']);
        }

        return ($this->updateUserCallback)($userId, $data);
    }

    /**
     * Supprimer un utilisateur
     */
    public function deleteUser(mixed $userId): bool
    {
        if (!isset($this->deleteUserCallback)) {
            throw new \RuntimeException('deleteUser callback not set');
        }

        return ($this->deleteUserCallback)($userId);
    }
}
