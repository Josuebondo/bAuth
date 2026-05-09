<?php

namespace Bmvc\BAuth\Adapters\PDO;

use Bmvc\BAuth\Providers\BaseAuthProvider;
use PDO;

/**
 * Implémentation du fournisseur d'authentification avec PDO
 * Compatible avec MySQL, PostgreSQL, SQLite, etc.
 */
class PDOAuthProvider extends BaseAuthProvider
{
    private string $table;

    public function __construct(
        $config,
        private PDO $pdo,
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
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Récupérer un utilisateur par son email
     */
    public function getUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Récupérer un utilisateur par son ID
     */
    public function getUserById(mixed $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Créer un utilisateur
     */
    public function createUser(array $userData): ?array
    {
        $userData['password'] = $this->password->hash($userData['password'] ?? '');
        $userData['created_at'] = date('Y-m-d H:i:s');

        $columns = implode(', ', array_keys($userData));
        $placeholders = implode(', ', array_fill(0, count($userData), '?'));

        $stmt = $this->pdo->prepare("INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})");
        $stmt->execute(array_values($userData));

        $id = $this->pdo->lastInsertId();
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

        $data['updated_at'] = date('Y-m-d H:i:s');

        $updates = [];
        foreach ($data as $key => $value) {
            $updates[] = "{$key} = ?";
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE id = ?";
        $values = array_merge(array_values($data), [$userId]);

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Supprimer un utilisateur
     */
    public function deleteUser(mixed $userId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$userId]);
    }
}
