<?php

namespace Bmvc\BAuth\Adapters\BMVC;

use Bmvc\BAuth\Providers\BaseAuthProvider;

/**
 * Implémentation du fournisseur d'authentification pour BMVC
 */
class BmvcAuthProvider extends BaseAuthProvider
{
    private string $table;

    public function __construct(
        $config,
        string $table = 'users'
    ) {
        parent::__construct($config);
        $this->table = $table;
    }

    /**
     * Récupérer un utilisateur par son identifiant
     */
    public function getUserByIdentifier(string $identifier): ?array
    {
        // Implémentation spécifique à BMVC pour récupérer l'utilisateur
        // Par exemple, en utilisant le modèle Eloquent de BMVC
        $user = \App\Models\Utilisateur::ou('username', $identifier)
            ->ou('email', $identifier)
            ->premier();

        return $user ? $user->toArray() : null;
    }

    /**
     * Récupérer un utilisateur par son email
     */
    public function getUserByEmail(string $email): ?array
    {
        $user = \App\Models\Utilisateur::ou('email', $email)->premier();

        return $user ? $user->toArray() : null;
    }

    /**
     * Récupérer un utilisateur par son ID
     */
    public function getUserById(mixed $id): ?array
    {
        $user = \App\Models\Utilisateur::trouver($id);

        return $user ? $user->enTableau() : null;
    }

    /**
     * Créer un utilisateur
     */
    public function createUser(array $userData): ?array
    {
        $user = \App\Models\Utilisateur::create($userData);

        return $user ? $user->enTableau() : null;
    }
    public function updateUser(mixed $id, array $userData): ?array
    {
        $user = \App\Models\Utilisateur::trouver($id);

        if (!$user) {
            return null;
        }

        $user->update($userData);

        return $user->enTableau();
    }
}
