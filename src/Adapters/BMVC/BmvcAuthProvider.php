<?php

namespace Bmvc\BAuth\Adapters\BMVC;

use Bmvc\BAuth\Providers\BaseAuthProvider;

/**
 * Implémentation du fournisseur d'authentification pour BMVC
 */
class BmvcAuthProvider extends BaseAuthProvider
{
    protected string $table;

    public function __construct(
        $config,
        $table = 'users',
    ) {
        $this->table = $table;
        parent::__construct($config);
    }

    protected function modelClass(): string
    {
        return '\\App\\Modeles\\' . $this->table;
    }

    /**
     * Récupérer un utilisateur par son identifiant
     */
    public function getUserByIdentifier(string $identifier): ?array
    {
        $modelClass = $this->modelClass();
        $user = $modelClass::ou('username', $identifier)
            ->ou('email', $identifier)
            ->premier();

        return $user ? $user->enTableau() : null;
    }

    /**
     * Récupérer un utilisateur par son email
     */
    public function getUserByEmail(string $email): ?array
    {
        $modelClass = $this->modelClass();
        $user = $modelClass::ou('email', $email)->premier();

        return $user ? $user->enTableau() : null;
    }

    /**
     * Récupérer un utilisateur par son ID
     */
    public function getUserById(mixed $id): ?array
    {
        $modelClass = $this->modelClass();
        $user = $modelClass::trouver($id);

        return $user ? $user->enTableau() : null;
    }

    /**
     * Créer un utilisateur
     */
    public function createUser(array $userData): ?array
    {
        $modelClass = $this->modelClass();
        $user = $modelClass::creer($userData);

        return $user ? $user->enTableau() : null;
    }

    public function updateUser(mixed $id, array $userData): bool
    {
        $modelClass = $this->modelClass();
        $user = $modelClass::trouver($id);
        if (!$user) {
            return false;
        }
        $user->remplir($userData);
        if ($user->sauvegarder()) {
            return true;
        }
        return false;
    }

    public function deleteUser(mixed $id): bool
    {
        $modelClass = $this->modelClass();
        $user = $modelClass::trouver($id);
        if (!$user) {
            return false;
        }
        return $user->supprimer();
    }
}
