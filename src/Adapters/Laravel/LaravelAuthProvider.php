<?php

namespace Bmvc\BAuth\Adapters\Laravel;

use Bmvc\BAuth\Providers\BaseAuthProvider;
use Illuminate\Support\Facades\DB;

/**
 * Implémentation du fournisseur d'authentification pour Laravel
 */
class LaravelAuthProvider extends BaseAuthProvider
{
    private string $table;

    public function __construct($config, string $table = 'users')
    {
        parent::__construct($config);
        $this->table = $table;
    }

    /**
     * Récupérer un utilisateur par son identifiant
     */
    public function getUserByIdentifier(string $identifier): ?array
    {
        $user = DB::table($this->table)
            ->where('username', $identifier)
            ->first();

        return $user ? (array) $user : null;
    }

    /**
     * Récupérer un utilisateur par son email
     */
    public function getUserByEmail(string $email): ?array
    {
        $user = DB::table($this->table)
            ->where('email', $email)
            ->first();

        return $user ? (array) $user : null;
    }

    /**
     * Récupérer un utilisateur par son ID
     */
    public function getUserById(mixed $id): ?array
    {
        $user = DB::table($this->table)
            ->where('id', $id)
            ->first();

        return $user ? (array) $user : null;
    }

    /**
     * Créer un utilisateur
     */
    public function createUser(array $userData): ?array
    {
        $userData['password'] = $this->password->hash($userData['password'] ?? '');

        $id = DB::table($this->table)->insertGetId($userData);

        return $this->getUserById($id);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function updateUser(mixed $userId, array $data): bool
    {
        if (isset($data['password'])) {
            $data['password'] = $this->password->hash($data['password']);
        }

        return DB::table($this->table)
            ->where('id', $userId)
            ->update($data) > 0;
    }

    /**
     * Supprimer un utilisateur
     */
    public function deleteUser(mixed $userId): bool
    {
        return DB::table($this->table)
            ->where('id', $userId)
            ->delete() > 0;
    }
}
