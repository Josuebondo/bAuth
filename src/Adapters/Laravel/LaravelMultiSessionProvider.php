<?php

namespace Bmvc\BAuth\Adapters\Laravel;

use Bmvc\BAuth\Providers\BaseMultiSessionProvider;

/**
 * LaravelMultiSessionProvider
 * 
 * Adapter Multi-Session pour Laravel
 * Gère les sessions multiples avec la base de données Laravel
 * 
 * @package Bmvc\BAuth\Adapters\Laravel
 */
class LaravelMultiSessionProvider extends BaseMultiSessionProvider
{
    /**
     * Modèle de session
     */
    protected string $sessionModel;

    /**
     * Constructeur
     */
    public function __construct(string $sessionModel = '\App\Models\Session')
    {
        $this->sessionModel = $sessionModel;
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

        $record = $this->sessionModel::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'device_name' => $deviceName,
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
            'active' => true,
        ]);

        return [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'device_name' => $deviceName,
            'created_at' => $record->created_at,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSession(string $sessionId): ?array
    {
        $record = $this->sessionModel::where('session_id', $sessionId)->first();

        if (!$record) {
            return null;
        }

        return $record->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getUserSessions($userId): array
    {
        return $this->sessionModel::where('user_id', $userId)
            ->where('active', true)
            ->get()
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function terminateSession(string $sessionId): bool
    {
        return $this->sessionModel::where('session_id', $sessionId)
            ->update(['active' => false]) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function terminateAllUserSessions($userId, ?string $exceptSessionId = null): int
    {
        $query = $this->sessionModel::where('user_id', $userId)->where('active', true);

        if ($exceptSessionId) {
            $query->where('session_id', '!=', $exceptSessionId);
        }

        return $query->update(['active' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function isSessionValid(string $sessionId): bool
    {
        return $this->sessionModel::where('session_id', $sessionId)
            ->where('active', true)
            ->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function updateSessionActivity(string $sessionId): bool
    {
        return $this->sessionModel::where('session_id', $sessionId)
            ->update(['last_activity' => now()]) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getSessionDeviceInfo(string $sessionId): array
    {
        $record = $this->sessionModel::where('session_id', $sessionId)->first();

        if (!$record) {
            return [];
        }

        return [
            'device_name' => $record->device_name,
            'user_agent' => $record->user_agent,
            'ip_address' => $record->ip_address,
            'created_at' => $record->created_at,
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
        $record = $this->sessionModel::where('session_id', $sessionId)->first();

        if (!$record) {
            return false;
        }

        if ($record->user_agent !== $userAgent) {
            return true;
        }

        if ($record->ip_address !== $ipAddress) {
            if (now()->diffInSeconds($record->created_at) < 300) {
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
        $threshold = now()->subSeconds($inactivityThresholdSeconds);

        return $this->sessionModel::where('user_id', $userId)
            ->where('active', true)
            ->where('last_activity', '<', $threshold)
            ->get()
            ->map(fn($session) => [
                'session_id' => $session->session_id,
                'device_name' => $session->device_name,
                'inactive_for' => now()->diffInSeconds($session->last_activity),
            ])
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function cleanupExpiredSessions(): int
    {
        $maxAge = now()->subDays(30);

        return $this->sessionModel::where('created_at', '<', $maxAge)
            ->update(['active' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function limitSimultaneousSessions($userId, int $maxSessions): int
    {
        $sessions = $this->sessionModel::where('user_id', $userId)
            ->where('active', true)
            ->orderBy('created_at')
            ->get();

        if ($sessions->count() <= $maxSessions) {
            return 0;
        }

        $count = 0;
        $keep = $sessions->slice(-$maxSessions);

        foreach ($sessions as $session) {
            if (!$keep->pluck('id')->contains($session->id)) {
                $session->update(['active' => false]);
                $count++;
            }
        }

        return $count;
    }
}
