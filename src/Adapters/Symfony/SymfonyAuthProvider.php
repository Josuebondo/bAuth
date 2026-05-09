<?php

namespace Bmvc\BAuth\Adapters\Symfony;

use Bmvc\BAuth\Providers\BaseAuthProvider;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation du fournisseur d'authentification pour Symfony
 */
class SymfonyAuthProvider extends BaseAuthProvider
{
    private string $entityClass;

    public function __construct(
        $config,
        private EntityManagerInterface $entityManager,
        string $entityClass = 'App\\Entity\\User'
    ) {
        parent::__construct($config);
        $this->entityClass = $entityClass;
    }

    /**
     * Récupérer un utilisateur par son identifiant
     */
    public function getUserByIdentifier(string $identifier): ?array
    {
        $user = $this->entityManager
            ->getRepository($this->entityClass)
            ->findOneBy(['username' => $identifier]);

        return $user ? $this->userToArray($user) : null;
    }

    /**
     * Récupérer un utilisateur par son email
     */
    public function getUserByEmail(string $email): ?array
    {
        $user = $this->entityManager
            ->getRepository($this->entityClass)
            ->findOneBy(['email' => $email]);

        return $user ? $this->userToArray($user) : null;
    }

    /**
     * Récupérer un utilisateur par son ID
     */
    public function getUserById(mixed $id): ?array
    {
        $user = $this->entityManager
            ->getRepository($this->entityClass)
            ->find($id);

        return $user ? $this->userToArray($user) : null;
    }

    /**
     * Créer un utilisateur
     */
    public function createUser(array $userData): ?array
    {
        $userClass = $this->entityClass;
        $user = new $userClass();

        foreach ($userData as $key => $value) {
            $method = 'set' . ucfirst($key);
            if ($key === 'password') {
                $value = $this->password->hash($value);
            }
            if (method_exists($user, $method)) {
                $user->$method($value);
            }
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->userToArray($user);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function updateUser(mixed $userId, array $data): bool
    {
        $user = $this->entityManager
            ->getRepository($this->entityClass)
            ->find($userId);

        if (!$user) {
            return false;
        }

        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($key);
            if ($key === 'password') {
                $value = $this->password->hash($value);
            }
            if (method_exists($user, $method)) {
                $user->$method($value);
            }
        }

        $this->entityManager->flush();

        return true;
    }

    /**
     * Supprimer un utilisateur
     */
    public function deleteUser(mixed $userId): bool
    {
        $user = $this->entityManager
            ->getRepository($this->entityClass)
            ->find($userId);

        if (!$user) {
            return false;
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Convertir une entité utilisateur en tableau
     */
    private function userToArray(object $user): array
    {
        $reflection = new \ReflectionClass($user);
        $data = [];

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $key = $property->getName();
            // Exclure le password dans la réponse
            if ($key !== 'password') {
                $data[$key] = $property->getValue($user);
            }
        }

        return $data;
    }
}
