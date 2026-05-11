<?php

namespace Bmvc\BAuth\Providers;

use Bmvc\BAuth\Contracts\MultiSessionProviderInterface;

/**
 * BaseMultiSessionProvider
 * 
 * Implémentation de base pour la gestion des sessions multiples
 * 
 * @package Bmvc\BAuth\Providers
 */
class BaseMultiSessionProvider implements MultiSessionProviderInterface
{
    /**
     * Stockage des sessions
     */
    protected array $sessions = [];

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

        $this->sessions[$sessionId] = [
            'user_id' => $userId,
            'device_name' => $deviceName,
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
            'created_at' => time(),
            'last_activity' => time(),
            'active' => true,
            'suspicious' => false,
        ];

        return [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'device_name' => $deviceName,
            'created_at' => time(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSession(string $sessionId): ?array
    {
        return $this->sessions[$sessionId] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserSessions($userId): array
    {
        $sessions = [];

        foreach ($this->sessions as $sessionId => $session) {
            if ($session['user_id'] == $userId && $session['active']) {
                $sessions[] = array_merge(
                    ['session_id' => $sessionId],
                    $session
                );
            }
        }

        return $sessions;
    }

    /**
     * {@inheritdoc}
     */
    public function terminateSession(string $sessionId): bool
    {
        if (!isset($this->sessions[$sessionId])) {
            return false;
        }

        $this->sessions[$sessionId]['active'] = false;
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function terminateAllUserSessions($userId, ?string $exceptSessionId = null): int
    {
        $count = 0;

        foreach ($this->sessions as $sessionId => $session) {
            if (
                $session['user_id'] == $userId &&
                $session['active'] &&
                $sessionId !== $exceptSessionId
            ) {
                $this->sessions[$sessionId]['active'] = false;
                $count++;
            }
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function isSessionValid(string $sessionId): bool
    {
        if (!isset($this->sessions[$sessionId])) {
            return false;
        }

        $session = $this->sessions[$sessionId];

        return $session['active'] === true;
    }

    /**
     * {@inheritdoc}
     */
    public function updateSessionActivity(string $sessionId): bool
    {
        if (!isset($this->sessions[$sessionId])) {
            return false;
        }

        $this->sessions[$sessionId]['last_activity'] = time();
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getSessionDeviceInfo(string $sessionId): array
    {
        if (!isset($this->sessions[$sessionId])) {
            return [];
        }

        $session = $this->sessions[$sessionId];

        return [
            'device_name' => $session['device_name'],
            'user_agent' => $session['user_agent'],
            'ip_address' => $session['ip_address'],
            'created_at' => $session['created_at'],
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
        if (!isset($this->sessions[$sessionId])) {
            return false;
        }

        $session = $this->sessions[$sessionId];

        // Vérifier si le User-Agent a changé drastiquement
        if ($session['user_agent'] !== $userAgent) {
            return true;
        }

        // Vérifier si l'adresse IP a changé (une session ne devrait pas changer d'IP rapidement)
        if ($session['ip_address'] !== $ipAddress) {
            // Si la session est récente et l'IP a changé, c'est suspect
            if (time() - $session['created_at'] < 300) { // 5 minutes
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
        $inactiveSessions = [];
        $currentTime = time();

        foreach ($this->sessions as $sessionId => $session) {
            if ($session['user_id'] == $userId && $session['active']) {
                $inactivityTime = $currentTime - $session['last_activity'];

                if ($inactivityTime > $inactivityThresholdSeconds) {
                    $inactiveSessions[] = [
                        'session_id' => $sessionId,
                        'device_name' => $session['device_name'],
                        'inactive_for' => $inactivityTime,
                    ];
                }
            }
        }

        return $inactiveSessions;
    }

    /**
     * {@inheritdoc}
     */
    public function cleanupExpiredSessions(): int
    {
        $count = 0;
        $maxSessionAge = 30 * 24 * 60 * 60; // 30 jours
        $currentTime = time();

        foreach ($this->sessions as $sessionId => $session) {
            $sessionAge = $currentTime - $session['created_at'];

            if ($sessionAge > $maxSessionAge) {
                $this->terminateSession($sessionId);
                $count++;
            }
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function limitSimultaneousSessions($userId, int $maxSessions): int
    {
        $userSessions = $this->getUserSessions($userId);

        if (count($userSessions) <= $maxSessions) {
            return 0;
        }

        // Trier par date de création (les plus anciennes en premier)
        usort($userSessions, function ($a, $b) {
            return $a['created_at'] - $b['created_at'];
        });

        $count = 0;
        $sessionsToKeep = array_slice($userSessions, -$maxSessions);
        $sessionsToRemoveIds = array_map(fn($s) => $s['session_id'], $sessionsToKeep);

        foreach ($userSessions as $session) {
            if (!in_array($session['session_id'], $sessionsToRemoveIds)) {
                $this->terminateSession($session['session_id']);
                $count++;
            }
        }

        return $count;
    }
}
