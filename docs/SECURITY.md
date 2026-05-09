# Guide de sécurité BAuth

## Table des matières

1. [Bonnes pratiques](#bonnes-pratiques)
2. [Configuration HTTPS](#configuration-https)
3. [Tokens JWT](#tokens-jwt)
4. [Mots de passe](#mots-de-passe)
5. [Sessions](#sessions)
6. [CORS](#cors)
7. [Rate limiting](#rate-limiting)
8. [Audit et logging](#audit-et-logging)
9. [OWASP Top 10](#owasp-top-10)

## Bonnes pratiques

### 1. Utilisez HTTPS en production

**Essentiel** : Ne JAMAIS transmmettre des tokens ou mots de passe en HTTP.

```php
<?php

// Vérifier que la connexion est sécurisée
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    throw new \Exception('HTTPS required');
}
```

### 2. Changez la clé secrète JWT

**IMPORTANT** : Générez une clé forte et unique.

```bash
# Générer une clé secrète
php -r "echo bin2hex(random_bytes(32));"

# Ou utiliser
openssl rand -hex 32
```

```env
AUTH_JWT_SECRET=your-very-long-random-secret-key-here
```

### 3. Utilisez des variables d'environnement

**JAMAIS** ne stockez de secrets en dur dans le code.

```php
<?php

// ✓ BON
$secret = $_ENV['AUTH_JWT_SECRET'];

// ✗ MAUVAIS
$secret = 'my-secret-key';
```

### 4. Restituez les erreurs avec prudence

```php
<?php

// ✓ BON - Erreur générique
throw new AuthenticationException('Invalid credentials');

// ✗ MAUVAIS - Révèle trop d'info
throw new Exception("User not found in database for email: $email");
```

### 5. Validez TOUTES les entrées utilisateur

```php
<?php

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Valider
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new Exception('Invalid email format');
}

if (strlen($password) < 8) {
    throw new Exception('Password too short');
}

$auth->login($email, $password);
```

## Configuration HTTPS

### Apache

```apache
# .htaccess
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

### Nginx

```nginx
server {
    listen 80;
    server_name example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name example.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
}
```

### PHP

```php
<?php

// Forcer HTTPS
if (php_sapi_name() === 'cli') {
    return;
}

if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
    exit;
}
```

## Tokens JWT

### Configuration sécurisée

```php
<?php

$config = new Config([
    'jwt' => [
        'secret' => $_ENV['AUTH_JWT_SECRET'],
        'algorithm' => 'HS256', // HS256, HS384, HS512
        'expiresIn' => 3600,    // 1 heure
        'refreshTokenExpiresIn' => 604800, // 7 jours
    ]
]);
```

### Recommandations

- **Courte durée de vie** : 15-30 minutes pour les tokens sensibles
- **Renouvellement** : Fournir un refresh token à longue durée
- **Révocation** : Implémenter une liste noire (blacklist)
- **Signature** : Utilisez HS512 pour plus de sécurité

### Implémenter une blacklist de tokens

```php
<?php

namespace App\Security;

use Redis;

class TokenBlacklist
{
    public function __construct(private Redis $redis) {}

    public function add(string $token, int $expiresIn): void
    {
        $this->redis->setex("token:$token", $expiresIn, 1);
    }

    public function isBlacklisted(string $token): bool
    {
        return (bool) $this->redis->exists("token:$token");
    }
}
```

Utilisez-le dans le vérificateur de token :

```php
<?php

try {
    $payload = $auth->verifyToken($token);

    if ($tokenBlacklist->isBlacklisted($token)) {
        throw new InvalidTokenException('Token has been revoked');
    }
} catch (InvalidTokenException $e) {
    // ...
}
```

## Mots de passe

### Configuration bcrypt

```php
<?php

$config = new Config([
    'password' => [
        'algorithm' => PASSWORD_BCRYPT,
        'options' => [
            'cost' => 12, // Augmentez pour plus de sécurité
        ]
    ]
]);
```

### Recommandations

```php
<?php

// ✓ BON - Coût élevé
'cost' => 12 // Prend ~200ms

// ✗ MAUVAIS - Coût faible
'cost' => 4 // Trop rapide
```

### Politique de mots de passe

```php
<?php

class PasswordPolicy
{
    public static function validate(string $password): array
    {
        $errors = [];

        if (strlen($password) < 12) {
            $errors[] = 'Password must be at least 12 characters';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain uppercase letter';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain lowercase letter';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain digit';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain special character';
        }

        return $errors;
    }
}

// Utilisation
$errors = PasswordPolicy::validate($_POST['password']);
if (!empty($errors)) {
    throw new Exception(implode(', ', $errors));
}
```

### Forcer le renouvellement du hash

```php
<?php

$authProvider = $auth->getAuthProvider();
$user = $authProvider->getUserById($userId);

if ($password->needsRehash($user['password'])) {
    $authProvider->updateUser($userId, [
        'password' => $_POST['new_password'],
    ]);
}
```

## Sessions

### Configuration sécurisée

```php
<?php

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);  // HTTPS seulement
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.cookie_lifetime', 3600);
ini_set('session.gc_maxlifetime', 3600);

session_start();
```

### Configuration dans php.ini

```ini
; PHP.ini
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Strict
session.use_only_cookies = 1
session.cookie_lifetime = 3600
session.gc_maxlifetime = 3600
```

### Régénération de session après login

```php
<?php

try {
    $result = $auth->login($email, $password);

    // Régénérer l'ID de session
    session_regenerate_id(true);

    // Démarrer la nouvelle session
    $auth->getSessionProvider()->start($result['user'], $result['token']);
} catch (Exception $e) {
    // ...
}
```

## CORS

### Configuration CORS sécurisée

```php
<?php

// Définir les origines autorisées
$allowedOrigins = explode(',', $_ENV['CORS_ORIGINS'] ?? 'http://localhost:3000');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

// Gérer les requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
```

### Configuration avec Apache

```apache
# .htaccess
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "%{HTTP:Origin}e" env=HTTP_Origin
    Header set Access-Control-Allow-Credentials "true"
    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization"

    RewriteCond %{REQUEST_METHOD} OPTIONS
    RewriteRule ^(.*)$ - [L,R=200]
</IfModule>
```

## Rate limiting

### Implémenter le rate limiting

```php
<?php

namespace App\Security;

use Redis;

class RateLimiter
{
    public function __construct(private Redis $redis) {}

    public function isAllowed(string $identifier, int $limit = 5, int $window = 60): bool
    {
        $key = "ratelimit:$identifier";
        $current = $this->redis->incr($key);

        if ($current === 1) {
            $this->redis->expire($key, $window);
        }

        return $current <= $limit;
    }
}
```

### Utiliser le rate limiting

```php
<?php

$rateLimiter = new RateLimiter($redis);
$ip = $_SERVER['REMOTE_ADDR'];

if (!$rateLimiter->isAllowed($ip, 5, 60)) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests']);
    exit;
}

// Permettre la tentative de connexion
try {
    $result = $auth->login($_POST['email'], $_POST['password']);
} catch (AuthenticationException $e) {
    // Incrémenter le compteur en cas d'échec
    $rateLimiter->isAllowed("auth:$email");
}
```

### Configuration Nginx

```nginx
http {
    limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;

    server {
        location /api/auth/login {
            limit_req zone=login burst=5 nodelay;
            # ...
        }
    }
}
```

## Audit et logging

### Logger les événements d'authentification

```php
<?php

class AuthAudit
{
    public function __construct(private \Psr\Log\LoggerInterface $logger) {}

    public function logLogin(string $email, bool $success, string $ip): void
    {
        $this->logger->info('User login attempt', [
            'email' => $email,
            'success' => $success,
            'ip' => $ip,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    public function logLogout(int $userId, string $ip): void
    {
        $this->logger->info('User logout', [
            'user_id' => $userId,
            'ip' => $ip,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    public function logAuthorization(int $userId, string $action, bool $allowed): void
    {
        $this->logger->info('Authorization check', [
            'user_id' => $userId,
            'action' => $action,
            'allowed' => $allowed,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }
}
```

### Configuration logging

```yaml
# config/services.yaml
services:
  App\Security\AuthAudit:
    arguments:
      - "@logger"
```

## OWASP Top 10

### 1. Injection

```php
<?php

// ✗ MAUVAIS - SQL Injection
$user = $pdo->query("SELECT * FROM users WHERE email = '$email'");

// ✓ BON - Prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();
```

### 2. Authentification brisée

```php
<?php

// ✓ BON - Vérifier l'authentification
if (!$auth->isAuthenticated()) {
    throw new AuthenticationException('Not authenticated');
}

// ✓ BON - Utiliser un hachage fort
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// ✓ BON - Renouveler les tokens
$newToken = $auth->refreshToken();
```

### 3. Exposition de données sensibles

```php
<?php

// ✗ MAUVAIS - Exposer les données
return json_encode([
    'user' => $user,
    'password' => $user['password'], // Ne JAMAIS inclure!
]);

// ✓ BON - Nettoyer les données
$userData = [
    'id' => $user['id'],
    'email' => $user['email'],
    'username' => $user['username'],
    // password, token non inclus
];
return json_encode(['user' => $userData]);
```

### 4. Contrôle d'accès défaillant

```php
<?php

// ✓ BON - Vérifier l'accès
$auth->authorize('delete_users');

// ✓ BON - Vérifier la propriété
if ($post->user_id !== $auth->userId()) {
    throw new AuthorizationException('Not allowed');
}
```

### 5. Chiffrement insuffisant

```php
<?php

// ✓ BON - Utiliser HTTPS
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// ✓ BON - Utiliser des clés fortes
$config->set('jwt.secret', bin2hex(random_bytes(32)));
```

### 6. Authentification faible

```php
<?php

// ✓ BON - Implémenter le 2FA
$twoFactor->enable($userId);
$twoFactor->verify($userId, $code);

// ✓ BON - Rate limiting
$rateLimiter->isAllowed($ip, 5, 60);
```

### 7. Injection de dépendances

```php
<?php

// ✗ MAUVAIS
eval("$userInput");

// ✓ BON - Valider et nettoyer
$action = trim($_GET['action']);
if (!in_array($action, ['login', 'logout', 'register'])) {
    throw new Exception('Invalid action');
}
```

### 8. Utilisation de composants avec des vulnérabilités connues

```bash
# Vérifier les dépendances
composer audit

# Mettre à jour les dépendances
composer update

# Vérifier avec Dependabot
```

### 9. Journalisation insuffisante

```php
<?php

// ✓ BON - Logger les événements importants
$logger->alert('Brute force attempt detected', ['ip' => $ip]);
$logger->warning('Failed login for user', ['email' => $email]);
$logger->info('Token refresh', ['user_id' => $userId]);
```

### 10. Falsification de requête intersites (CSRF)

```php
<?php

// ✓ BON - Générer un token CSRF
$csrfToken = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

// ✓ BON - Vérifier le token
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    throw new Exception('CSRF token mismatch');
}
```

## Checklist de sécurité

- [ ] HTTPS activé en production
- [ ] Clé JWT secrète générée et unique
- [ ] Mots de passe hachés avec bcrypt (cost ≥ 12)
- [ ] Tokens JWT à courte durée de vie
- [ ] Rate limiting implémenté
- [ ] Validation d'entrée complète
- [ ] Gestion d'erreurs sans révéler d'infos
- [ ] Logging et audit activés
- [ ] Sessions sécurisées (HttpOnly, Secure, SameSite)
- [ ] CORS correctement configuré
- [ ] 2FA implémenté (recommandé)
- [ ] Dépendances à jour et audit fait
- [ ] Tests de sécurité effectués
- [ ] Politique de mots de passe forte
- [ ] Plan de réponse aux incidents

## Ressources supplémentaires

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Symfony Security](https://symfony.com/doc/current/security.html)
- [Laravel Security](https://laravel.com/docs/security)
- [PHP Security](https://www.php.net/manual/en/security.php)
