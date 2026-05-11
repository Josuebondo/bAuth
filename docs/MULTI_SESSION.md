# Sessions Multiples

BAuth permet à un utilisateur d'avoir plusieurs sessions actives simultanément, avec la possibilité de gérer et de monitorer chaque session indépendamment.

## Installation

Sessions Multiples est inclus par défaut dans BAuth.

## Configuration

### Configuration de base

```php
<?php

use Bmvc\BAuth\Providers\BaseMultiSessionProvider;

$multiSession = new BaseMultiSessionProvider();
```

## Utilisation

### 1. Créer une nouvelle session

```php
<?php

$userId = auth()->id();

$session = $multiSession->createSession(
    $userId,
    'Mon ordinateur', // Nom du dispositif
    $_SERVER['HTTP_USER_AGENT'], // User-Agent du navigateur
    $_SERVER['REMOTE_ADDR'] // Adresse IP du client
);

$_SESSION['session_id'] = $session['session_id'];

echo "Session créée: " . $session['session_id'];
```

### 2. Obtenir une session

```php
<?php

$session = $multiSession->getSession('session_id');

if ($session) {
    echo "Dispositif: " . $session['device_name'];
    echo "Créée le: " . $session['created_at'];
}
```

### 3. Obtenir toutes les sessions actives d'un utilisateur

```php
<?php

$sessions = $multiSession->getUserSessions(auth()->id());

foreach ($sessions as $session) {
    echo $session['device_name'] . " - " . $session['ip_address'];
}
```

### 4. Terminer une session spécifique

```php
<?php

$multiSession->terminateSession($_SESSION['session_id']);

echo "Session terminée";
```

### 5. Terminer toutes les sessions

```php
<?php

$count = $multiSession->terminateAllUserSessions(auth()->id());

echo "$count session(s) terminée(s)";
```

### 6. Terminer toutes les sessions sauf une

```php
<?php

$count = $multiSession->terminateAllUserSessions(
    auth()->id(),
    $_SESSION['session_id'] // Garder cette session active
);

echo "$count autre(s) session(s) terminée(s)";
```

### 7. Vérifier si une session est valide

```php
<?php

if ($multiSession->isSessionValid($_SESSION['session_id'])) {
    echo "Session valide";
} else {
    // Session terminée, rediriger vers login
    header('Location: /login');
}
```

### 8. Mettre à jour l'activité d'une session

```php
<?php

$multiSession->updateSessionActivity($_SESSION['session_id']);
```

### 9. Obtenir les informations du dispositif

```php
<?php

$deviceInfo = $multiSession->getSessionDeviceInfo($_SESSION['session_id']);

echo "Utilisateur-Agent: " . $deviceInfo['user_agent'];
echo "Adresse IP: " . $deviceInfo['ip_address'];
```

### 10. Détecter une session suspecte

```php
<?php

$isSuspicious = $multiSession->isSessionSuspicious(
    $_SESSION['session_id'],
    $_SERVER['HTTP_USER_AGENT'],
    $_SERVER['REMOTE_ADDR']
);

if ($isSuspicious) {
    echo "Attention: Activité suspecte détectée";
    // Demander une re-authentification
}
```

### 11. Obtenir les sessions inactives

```php
<?php

$inactiveSessions = $multiSession->getInactiveSessions(
    auth()->id(),
    3600 // Sessions inactives depuis plus de 1 heure
);

foreach ($inactiveSessions as $session) {
    echo $session['device_name'] . " - Inactive depuis " . $session['inactive_for'] . "s";
}
```

### 12. Nettoyer les sessions expirées

```php
<?php

$count = $multiSession->cleanupExpiredSessions();

echo "$count session(s) supprimée(s)";
```

### 13. Limiter le nombre de sessions simultanées

```php
<?php

$count = $multiSession->limitSimultaneousSessions(
    auth()->id(),
    3 // Maximum 3 sessions simultanées
);

echo "$count session(s) terminée(s) pour respecter la limite";
```

## Workflow complet

```php
<?php

// Créer une session au login
if ($auth->login($email, $password)) {
    $session = $multiSession->createSession(
        auth()->id(),
        $deviceName,
        $_SERVER['HTTP_USER_AGENT'],
        $_SERVER['REMOTE_ADDR']
    );

    setcookie('session_id', $session['session_id'], [
        'httponly' => true,
        'secure' => true,
        'samesite' => 'Lax'
    ]);
}

// À chaque requête
if (!isset($_COOKIE['session_id'])) {
    // Aucune session, rediriger vers login
    header('Location: /login');
}

if (!$multiSession->isSessionValid($_COOKIE['session_id'])) {
    // Session invalide/terminée
    header('Location: /login');
}

// Mettre à jour l'activité
$multiSession->updateSessionActivity($_COOKIE['session_id']);

// Vérifier si la session est suspecte
if ($multiSession->isSessionSuspicious(
    $_COOKIE['session_id'],
    $_SERVER['HTTP_USER_AGENT'],
    $_SERVER['REMOTE_ADDR']
)) {
    // Demander une re-authentification
    echo "Veuillez vous re-authentifier pour des raisons de sécurité";
}
```

## Avec Laravel

```php
<?php

use Bmvc\BAuth\Adapters\Laravel\LaravelMultiSessionProvider;

$multiSession = new LaravelMultiSessionProvider(
    'App\Models\Session'
);

// Créer une session
$session = $multiSession->createSession(
    auth()->id(),
    request()->header('User-Agent'),
    request()->ip()
);

// Dans le controller
session()->put('session_id', $session['session_id']);
```

## Avec Symfony

```php
<?php

use Bmvc\BAuth\Adapters\Symfony\SymfonyMultiSessionProvider;

$multiSession = new SymfonyMultiSessionProvider(
    $entityManager,
    'App\Entity\User',
    'App\Entity\Session'
);

$session = $multiSession->createSession(
    $user->getId(),
    $request->headers->get('User-Agent'),
    $request->getClientIp()
);
```

## Affichage des sessions actives

```php
<?php

// Dans un template (par exemple avec Blade Laravel)

@foreach($sessions as $session)
    <div class="session-card">
        <h3>{{ $session['device_name'] }}</h3>
        <p>IP: {{ $session['ip_address'] }}</p>
        <p>Créée le: {{ $session['created_at'] }}</p>
        <p>Dernière activité: {{ $session['last_activity'] }}</p>

        @if($session['session_id'] === Session::get('session_id'))
            <span class="badge-primary">Session actuelle</span>
        @else
            <form method="POST" action="/sessions/{{ $session['session_id'] }}/terminate">
                @csrf
                @method('DELETE')
                <button type="submit">Terminer cette session</button>
            </form>
        @endif
    </div>
@endforeach
```

## Sécurité

- Valider le User-Agent et l'IP à chaque requête
- Détecter les changements d'IP rapides (impossible)
- Implémenter le MFA pour les sessions suspectes
- Enregistrer les sessions pour audit
- Limiter les sessions par utilisateur
- Nettoyer les sessions expirées régulièrement

## Bonnes pratiques

1. Afficher à l'utilisateur toutes ses sessions actives
2. Permettre à l'utilisateur de terminer les sessions à distance
3. Monitorer les changements de dispositif
4. Implémenter des alertes pour les nouvelles sessions
5. Utiliser des cookies sécurisés (httponly, secure, samesite)
6. Exprédition maximale: 30 jours
7. Activité maximale: 24 heures sans activité

## Noms de dispositifs recommandés

```
Chrome sur Windows 10
Safari sur iPhone 14
Firefox sur macOS
Chrome sur Samsung Galaxy S21
Edge sur Windows 11
```

## Monitorer les sessions

```php
<?php

// Activer le logging
$sessions = $multiSession->getUserSessions($userId);

foreach ($sessions as $session) {
    $activity = $multiSession->getSessionDeviceInfo($session['session_id']);

    // Log pour audit
    Log::info('Session active', [
        'user_id' => $userId,
        'device' => $activity['device_name'],
        'ip' => $activity['ip_address'],
        'created_at' => $activity['created_at'],
    ]);
}
```

## Variables d'environnement

```env
SESSION_MAX_LIFETIME=2592000
SESSION_ACTIVITY_TIMEOUT=86400
SESSION_MAX_CONCURRENT=5
SESSION_CLEANUP_INTERVAL=3600
SESSION_SUSPECT_IP_CHANGE=true
SESSION_SUSPECT_USERAGENT_CHANGE=true
```
