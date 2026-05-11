<?php

namespace Bmvc\BAuth\Adapters\Symfony;

use Bmvc\BAuth\Providers\BaseMultiSessionProvider;
use Doctrine\ORM\EntityManagerInterface;

/**
 * SymfonyMultiSessionProvider
 * 
 * Adapter Multi-Session pour Symfony
 * Gère les sessions multiples avec Doctrine ORM
 * 
 * @package Bmvc\BAuth\Adapters\Symfony
 */
class SymfonyMultiSessionProvider extends BaseMultiSessionProvider
{
    /**
     * Entity Manager Doctrine
     */
    protected EntityManagerInterface $entityManager;

    /**
     * Classe de l'entité User
     */
    protected string $userEntity;

    /**
     * Classe de l'entité Session
     */
    protected string $sessionEntity;

    /**
     * Constructeur
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        string $userEntity = 'App\Entity\User',
        string $sessionEntity = 'App\Entity\Session'
    ) {
        $this->entityManager = $entityManager;
        $this->userEntity = $userEntity;
        $this->sessionEntity = $sessionEntity;
    }

    /**
     * {@inheritdoc}
     */
    public function createSession(
        $userId,
        string $deviceName,
        string $userAgent,
        string $ipAddress
    ): array {
        $sessionId = bin2hex(random_bytes(32));

        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            throw new \Exception("Utilisateur non trouvé");
        }

        $sessionClass = $this->sessionEntity;
        $session = new $sessionClass();
        $session->setUser($user);
        $session->setSessionId($sessionId);
        $session->setDeviceName($deviceName);
        $session->setUserAgent($userAgent);
        $session->setIpAddress($ipAddress);
        $session->setActive(true);

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'device_name' => $deviceName,
            'created_at' => $session->getCreatedAt(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSession(string $sessionId): ?array
    {
        $record = $this->entityManager
            ->getRepository($this->sessionEntity)
            ->findOneBy(['sessionId' => $sessionId]);

        return $record ? (array) $record : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserSessions($userId): array
    {
        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            return [];
        }

        return array_map(
            fn($s) => (array) $s,
            $this->entityManager
                ->getRepository($this->sessionEntity)
                ->findBy(['user' => $user, 'active' => true])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function terminateSession(string $sessionId): bool
    {
        $record = $this->entityManager
            ->getRepository($this->sessionEntity)
            ->findOneBy(['sessionId' => $sessionId]);

        if (!$record) {
            return false;
        }

        $record->setActive(false);
        $this->entityManager->flush();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function terminateAllUserSessions($userId, ?string $exceptSessionId = null): int
    {
        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            return 0;
        }

        $sessions = $this->entityManager
            ->getRepository($this->sessionEntity)
            ->findBy(['user' => $user, 'active' => true]);

        $count = 0;
        foreach ($sessions as $session) {
            if ($session->getSessionId() !== $exceptSessionId) {
                $session->setActive(false);
                $count++;
            }
        }

        $this->entityManager->flush();

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function isSessionValid(string $sessionId): bool
    {
        $record = $this->entityManager
            ->getRepository($this->sessionEntity)
            ->findOneBy(['sessionId' => $sessionId, 'active' => true]);

        return $record !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function updateSessionActivity(string $sessionId): bool
    {
        $record = $this->entityManager
            ->getRepository($this->sessionEntity)
            ->findOneBy(['sessionId' => $sessionId]);

        if (!$record) {
            return false;
        }

        $record->setLastActivity(new \DateTime());
        $this->entityManager->flush();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getSessionDeviceInfo(string $sessionId): array
    {
        $record = $this->entityManager
            ->getRepository($this->sessionEntity)
            ->findOneBy(['sessionId' => $sessionId]);

        if (!$record) {
            return [];
        }

        return [
            'device_name' => $record->getDeviceName(),
            'user_agent' => $record->getUserAgent(),
            'ip_address' => $record->getIpAddress(),
            'created_at' => $record->getCreatedAt(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isSessionSuspicious(
        string $sessionId,
        string $userAgent,
        string $ipAddress
    ): bool {
        $record = $this->entityManager
            ->getRepository($this->sessionEntity)
            ->findOneBy(['sessionId' => $sessionId]);

        if (!$record) {
            return false;
        }

        if ($record->getUserAgent() !== $userAgent) {
            return true;
        }

        if ($record->getIpAddress() !== $ipAddress) {
            if ((new \DateTime())->diff($record->getCreatedAt())->s < 300) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getInactiveSessions($userId, int $inactivityThresholdSeconds = 3600): array
    {
        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            return [];
        }

        $threshold = new \DateTime('-' . $inactivityThresholdSeconds . ' seconds');

        $sessions = $this->entityManager
            ->getRepository($this->sessionEntity)
            ->findBy(['user' => $user, 'active' => true]);

        $inactive = [];
        foreach ($sessions as $session) {
            if ($session->getLastActivity() < $threshold) {
                $inactive[] = [
                    'session_id' => $session->getSessionId(),
                    'device_name' => $session->getDeviceName(),
                    'inactive_for' => (new \DateTime())->diff($session->getLastActivity())->s,
                ];
            }
        }

        return $inactive;
    }

    /**
     * {@inheritdoc}
     */
    public function cleanupExpiredSessions(): int
    {
        $maxAge = new \DateTime('-30 days');

        $sessions = $this->entityManager
            ->getRepository($this->sessionEntity)
            ->findAll();

        $count = 0;
        foreach ($sessions as $session) {
            if ($session->getCreatedAt() < $maxAge) {
                $session->setActive(false);
                $count++;
            }
        }

        $this->entityManager->flush();

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function limitSimultaneousSessions($userId, int $maxSessions): int
    {
        $user = $this->entityManager->find($this->userEntity, $userId);

        if (!$user) {
            return 0;
        }

        $sessions = $this->entityManager
            ->getRepository($this->sessionEntity)
            ->findBy(['user' => $user, 'active' => true]);

        if (count($sessions) <= $maxSessions) {
            return 0;
        }

        usort($sessions, fn($a, $b) => $a->getCreatedAt() <=> $b->getCreatedAt());

        $count = 0;
        $keep = array_slice($sessions, -$maxSessions);

        foreach ($sessions as $session) {
            if (!in_array($session, $keep)) {
                $session->setActive(false);
                $count++;
            }
        }

        $this->entityManager->flush();

        return $count;
    }
}
