# BAuth - Librairie PHP d'Authentification Complète

Une librairie PHP robuste, modulaire et framework-agnostique pour gérer l'authentification, l'autorisation, les tokens JWT et bien plus.

## Caractéristiques

✅ **Indépendante du framework** - Utilisable avec Laravel, Symfony, Slim, ou tout autre projet PHP
✅ **Authentification complète** - Email/mot de passe, JWT, sessions
✅ **Gestion des rôles et permissions** - Système d'autorisation flexible
✅ **Support du 2FA** - Authentification à deux facteurs (TOTP)
✅ **Tokens JWT** - Génération et vérification de tokens
✅ **Hachage sécurisé** - Utilise bcrypt pour les mots de passe
✅ **PSR-4 compliant** - Architecture standard PHP
✅ **Extensible** - Interfaces pour personnaliser chaque aspect

## Installation

```bash
composer require bmvc/bauth
```

## Configuration de base

```php
<?php

use BAuth\Config;
use BAuth\Auth;
use BAuth\Examples\Generic\GenericAuthProvider;

// Créer la configuration
$config = new Config([
    'jwt' => [
        'secret' => 'votre-clé-secrète',
        'expiresIn' => 3600,
    ],
    'password' => [
        'algorithm' => PASSWORD_BCRYPT,
        'options' => ['cost' => 12]
    ]
]);

// Créer l'instance Auth
$auth = new Auth($config);
```

## Utilisation basique

### 1. Configuration du fournisseur d'authentification

#### Avec un projet PHP générique

```php
<?php

$authProvider = new GenericAuthProvider($config);

// Configurer les callbacks pour accéder à votre base de données
$authProvider
    ->setGetUserByEmailCallback(function ($email) {
        // Votre logique de base de données
        return $pdo->query("SELECT * FROM users WHERE email = ?", [$email])->fetch();
    })
    ->setGetUserByIdentifierCallback(function ($identifier) {
        return $pdo->query("SELECT * FROM users WHERE username = ?", [$identifier])->fetch();
    })
    ->setGetUserByIdCallback(function ($id) {
        return $pdo->query("SELECT * FROM users WHERE id = ?", [$id])->fetch();
    })
    ->setCreateUserCallback(function ($userData) {
        $stmt = $pdo->prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)");
        $stmt->execute([$userData['email'], $userData['username'], $userData['password']]);
        return $authProvider->getUserById($pdo->lastInsertId());
    })
    ->setUpdateUserCallback(function ($userId, $data) {
        // Votre logique de mise à jour
        return true;
    })
    ->setDeleteUserCallback(function ($userId) {
        // Votre logique de suppression
        return true;
    });

$auth->setAuthProvider($authProvider);
```

#### Avec Laravel

```php
<?php

use BAuth\Examples\Laravel\LaravelAuthProvider;

$authProvider = new LaravelAuthProvider($config, 'users');
$auth->setAuthProvider($authProvider);
```

#### Avec Symfony

```php
<?php

use BAuth\Examples\Symfony\SymfonyAuthProvider;

$authProvider = new SymfonyAuthProvider($config, $entityManager, 'App\Entity\User');
$auth->setAuthProvider($authProvider);
```

### 2. Authentification

```php
<?php

try {
    // Connexion d'un utilisateur
    $result = $auth->login('user@example.com', 'password123');

    echo "Utilisateur connecté: " . $result['user']['email'];
    echo "Token: " . $result['token'];
} catch (\BAuth\Exceptions\AuthenticationException $e) {
    echo "Erreur d'authentification: " . $e->getMessage();
}
```

### 3. Vérifier l'authentification

```php
<?php

if ($auth->isAuthenticated()) {
    $user = $auth->user();
    echo "Connecté en tant que: " . $user['email'];
} else {
    echo "Non authentifié";
}
```

### 4. Déconnexion

```php
<?php

$auth->logout();
echo "Déconnecté avec succès";
```

### 5. Gestion des tokens JWT

```php
<?php

// Obtenir le token actuel
$token = $auth->token();

// Renouveler le token
$newToken = $auth->refreshToken();

// Vérifier un token
try {
    $payload = $auth->verifyToken($token);
    echo "Token valide";
} catch (\BAuth\Exceptions\InvalidTokenException $e) {
    echo "Token invalide: " . $e->getMessage();
}
```

### 6. Autorisation (Rôles et Permissions)

D'abord, implémenter un fournisseur d'autorisation :

```php
<?php

use BAuth\Providers\BaseAuthorizationProvider;

class MyAuthorizationProvider extends BaseAuthorizationProvider
{
    public function __construct(private $pdo)
    {
    }

    public function hasRole(mixed $userId, string $role): bool
    {
        $result = $this->pdo->query(
            "SELECT * FROM user_roles WHERE user_id = ? AND role = ?",
            [$userId, $role]
        );
        return $result->rowCount() > 0;
    }

    public function hasPermission(mixed $userId, string $permission): bool
    {
        $result = $this->pdo->query(
            "SELECT * FROM role_permissions rp
             JOIN user_roles ur ON rp.role = ur.role
             WHERE ur.user_id = ? AND rp.permission = ?",
            [$userId, $permission]
        );
        return $result->rowCount() > 0;
    }

    public function getRoles(mixed $userId): array
    {
        $results = $this->pdo->query(
            "SELECT role FROM user_roles WHERE user_id = ?",
            [$userId]
        );
        return array_column($results->fetchAll(), 'role');
    }

    public function getPermissions(mixed $userId): array
    {
        $results = $this->pdo->query(
            "SELECT DISTINCT rp.permission FROM role_permissions rp
             JOIN user_roles ur ON rp.role = ur.role
             WHERE ur.user_id = ?",
            [$userId]
        );
        return array_column($results->fetchAll(), 'permission');
    }

    public function assignRole(mixed $userId, string $role): bool
    {
        // Implémenter la logique
        return true;
    }

    public function removeRole(mixed $userId, string $role): bool
    {
        // Implémenter la logique
        return true;
    }

    public function assignPermission(string $role, string $permission): bool
    {
        // Implémenter la logique
        return true;
    }
}

// Configurer le fournisseur
$authorizationProvider = new MyAuthorizationProvider($pdo);
$auth->setAuthorizationProvider($authorizationProvider);
```

Puis, vérifier les permissions :

```php
<?php

// Vérifier si l'utilisateur a une permission
if ($auth->can('edit_posts')) {
    echo "L'utilisateur peut éditer les posts";
}

// Vérifier si l'utilisateur a un rôle
if ($auth->hasRole('admin')) {
    echo "L'utilisateur est administrateur";
}

// Autoriser une action (lève une exception si non autorisé)
try {
    $auth->authorize('delete_users');
    // Continuer si autorisé
} catch (\BAuth\Exceptions\AuthorizationException $e) {
    echo "Non autorisé: " . $e->getMessage();
}
```

### 7. Authentification à deux facteurs (2FA)

Implémenter un fournisseur 2FA :

```php
<?php

use BAuth\Providers\BaseTwoFactorProvider;

class MyTwoFactorProvider extends BaseTwoFactorProvider
{
    public function __construct(private $pdo)
    {
    }

    protected function getSecret(mixed $userId): ?string
    {
        $result = $this->pdo->query(
            "SELECT totp_secret FROM users WHERE id = ?",
            [$userId]
        );
        $row = $result->fetch();
        return $row ? $row['totp_secret'] : null;
    }

    public function enable(mixed $userId): array
    {
        $secret = $this->generateSecret();

        $this->pdo->execute(
            "UPDATE users SET totp_secret = ? WHERE id = ?",
            [$secret, $userId]
        );

        return [
            'secret' => $secret,
            'qr_code' => $this->generateQRCode($secret)
        ];
    }

    public function disable(mixed $userId): bool
    {
        return $this->pdo->execute(
            "UPDATE users SET totp_secret = NULL WHERE id = ?",
            [$userId]
        ) > 0;
    }

    public function isEnabled(mixed $userId): bool
    {
        return $this->getSecret($userId) !== null;
    }

    private function generateSecret(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function generateQRCode(string $secret): string
    {
        // Implémenter la génération de QR code
        return '';
    }
}

// Configurer le fournisseur
$twoFactorProvider = new MyTwoFactorProvider($pdo);
$auth->setTwoFactorProvider($twoFactorProvider);
```

Utiliser le 2FA :

```php
<?php

// Lors de la connexion
$result = $auth->login('user@example.com', 'password123');

// Vérifier le 2FA
if ($auth->getTwoFactorProvider()?->isEnabled($result['user']['id'])) {
    // Demander le code 2FA à l'utilisateur
    $code2fa = $_POST['2fa_code'];

    if ($auth->verify2FA($code2fa)) {
        echo "Authentification 2FA réussie";
    } else {
        echo "Code 2FA invalide";
    }
}
```

## Exemples d'intégration

### Avec Express.js + PHP Backend

```php
<?php

header('Content-Type: application/json');

$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$auth = setupAuth(); // Votre fonction de configuration

try {
    if ($path === '/api/login' && $requestMethod === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $auth->login($input['email'], $input['password']);
        echo json_encode($result);
    } elseif ($path === '/api/logout' && $requestMethod === 'POST') {
        $auth->logout();
        echo json_encode(['success' => true]);
    } elseif ($path === '/api/user' && $requestMethod === 'GET') {
        if ($auth->isAuthenticated()) {
            echo json_encode(['user' => $auth->user()]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
        }
    }
} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
```

### Middleware pour Laravel

```php
<?php

namespace App\Http\Middleware;

use Closure;

class BAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        $auth = app('bauth');

        if (!$auth->isAuthenticated()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
```

## Architecture

```
src/
├── Auth.php                    # Classe principale
├── Config.php                  # Configuration
├── Contracts/                  # Interfaces
│   ├── AuthProviderInterface.php
│   ├── TokenProviderInterface.php
│   ├── SessionProviderInterface.php
│   ├── AuthorizationProviderInterface.php
│   └── TwoFactorProviderInterface.php
├── Providers/                  # Implémentations
│   ├── BaseAuthProvider.php
│   ├── BaseAuthorizationProvider.php
│   ├── BaseTwoFactorProvider.php
│   ├── JWTProvider.php
│   └── SessionProvider.php
├── Support/                    # Utilitaires
│   └── Password.php
├── Examples/                   # Exemples d'implémentation
│   ├── Generic/
│   ├── Laravel/
│   └── Symfony/
└── Exceptions/                 # Exceptions personnalisées
    ├── BAuthException.php
    ├── AuthenticationException.php
    ├── AuthorizationException.php
    ├── InvalidTokenException.php
    └── UserNotFoundException.php
```

## Sécurité

- Les mots de passe sont hachés avec bcrypt (coût 12)
- Les tokens JWT utilisent HMAC-SHA256
- Support du 2FA TOTP
- Protection contre les replay attacks
- Validation stricte des tokens

## Testing

```bash
composer test
```

Voir `tests/AuthTest.php` pour des exemples de tests.

## Licence

MIT

## Support

Pour des questions ou des problèmes, veuillez ouvrir une issue sur GitHub.
