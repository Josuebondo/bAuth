# Guide de dépannage BAuth

## Table des matières

1. [Installation](#installation)
2. [Authentification](#authentification)
3. [Tokens JWT](#tokens-jwt)
4. [Sessions](#sessions)
5. [Autorisation](#autorisation)
6. [2FA](#2fa)
7. [Base de données](#base-de-données)
8. [Framework-spécifique](#framework-spécifique)

## Installation

### "Class 'BAuth\\...' not found"

**Problème :** Les classes de BAuth ne sont pas trouvées.

**Solutions :**

1. Vérifiez que Composer est installé

   ```bash
   composer install
   ```

2. Vérifiez l'autoload

   ```bash
   composer dump-autoload
   ```

3. Incluez l'autoloader

   ```php
   require 'vendor/autoload.php';
   ```

4. Vérifiez le namespace
   ```php
   use BAuth\Auth;
   use BAuth\Config;
   ```

### "Undefined type 'Firebase\\JWT\\JWT'"

**Problème :** La librairie firebase/jwt n'est pas installée.

**Solution :**

```bash
composer require firebase/jwt
```

### "Call to undefined function env()"

**Problème :** La fonction `env()` n'existe pas.

**Solution :**

```php
<?php

// Ajouter la fonction
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}

// Ou charger un fichier .env
require 'vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
```

## Authentification

### "Authentication failed"

**Problème :** La connexion échoue pour tous les utilisateurs.

**Diagnostiquer :**

```php
<?php

try {
    $auth->login('user@example.com', 'password123');
} catch (\BAuth\Exceptions\AuthenticationException $e) {
    echo "Erreur: " . $e->getMessage();
} catch (\BAuth\Exceptions\UserNotFoundException $e) {
    echo "Utilisateur non trouvé";
} catch (Exception $e) {
    echo "Erreur inattendue: " . $e->getMessage();
}
```

**Solutions selon le cas :**

1. **Utilisateur non trouvé**
   - Vérifiez que l'utilisateur existe dans la base de données
   - Vérifiez la requête SQL

   ```php
   $authProvider = $auth->getAuthProvider();
   $user = $authProvider->getUserByEmail('user@example.com');
   if (!$user) {
       echo "Utilisateur non trouvé";
   }
   ```

2. **Mot de passe incorrect**
   - Vérifiez que le mot de passe est correct
   - Vérifiez que le mot de passe est hashé en base de données

   ```php
   $password = new Password($config);
   if ($password->verify('password123', $user['password'])) {
       echo "Mot de passe correct";
   } else {
       echo "Mot de passe incorrect";
   }
   ```

3. **AuthProvider non configuré**
   - Vérifiez que `setAuthProvider()` a été appelé

   ```php
   $auth->setAuthProvider($authProvider);
   ```

### "User not found"

**Problème :** La méthode getUserByEmail/getUserByIdentifier retourne null.

**Solutions :**

1. Vérifiez la connexion à la base de données

   ```php
   try {
       $pdo->query("SELECT 1");
       echo "Connexion OK";
   } catch (PDOException $e) {
       echo "Erreur BD: " . $e->getMessage();
   }
   ```

2. Vérifiez les données de la table

   ```sql
   SELECT * FROM users WHERE email = 'user@example.com';
   ```

3. Vérifiez le nom de la colonne

   ```php
   // Si la colonne n'est pas "email", adaptez
   $user = $pdo->query("SELECT * FROM users WHERE email_address = ?");
   ```

4. Vérifiez les colonnes retournées

   ```php
   $stmt = $pdo->prepare("SELECT id, email, username, password FROM users WHERE email = ?");
   $stmt->execute([$email]);
   $user = $stmt->fetch(PDO::FETCH_ASSOC);
   var_dump($user);
   ```

### "Session not started"

**Problème :** Les sessions PHP ne sont pas démarrées.

**Solution :**

```php
<?php

// Vérifier le statut de session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ou en configuration Apache/Nginx
// auto_prepend_file = /path/to/session_start.php
```

### "Not authenticated after login"

**Problème :** `isAuthenticated()` retourne false après une connexion réussie.

**Solutions :**

1. Vérifiez que les sessions sont activées

   ```php
   echo session_status() === PHP_SESSION_ACTIVE ? "Sessions OK" : "Sessions pas activées";
   ```

2. Vérifiez que `start()` est appelé

   ```php
   $result = $auth->login($email, $password);
   // start() est appelé automatiquement
   ```

3. Vérifiez les données de session

   ```php
   echo "Session data: " . json_encode($_SESSION);
   ```

## Tokens JWT

### "Invalid token"

**Problème :** Le token ne peut pas être vérifié.

**Diagnostiquer :**

```php
<?php

try {
    $payload = $auth->verifyToken($token);
    echo "Token valide";
} catch (\BAuth\Exceptions\InvalidTokenException $e) {
    echo "Erreur: " . $e->getMessage();
}
```

**Solutions :**

1. **Token expiré**
   - Vérifiez que `exp` n'est pas passé
   - Renouveler le token

   ```php
   $newToken = $auth->refreshToken();
   ```

2. **Signature invalide**
   - Vérifiez que la clé secrète est la même
   - Vérifiez le format du token

   ```php
   echo "Token: " . substr($token, 0, 50) . "...";
   ```

3. **Token non bien formé**
   - Vérifiez que le token a 3 parties séparées par des points
   - Vérifiez qu'il commence par "Bearer "

   ```php
   $parts = explode('.', $token);
   if (count($parts) !== 3) {
       echo "Token non valide";
   }
   ```

### "Token not found"

**Problème :** `extractFromRequest()` retourne null.

**Solutions :**

1. Vérifiez le header Authorization

   ```php
   echo "Headers: " . json_encode(getallheaders());
   ```

2. Vérifiez le format Bearer

   ```php
   // Format attendu: "Bearer TOKEN_HERE"
   $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
   if (preg_match('/Bearer\s+(\S+)/', $auth, $matches)) {
       $token = $matches[1];
   }
   ```

3. Vérifiez que le header est envoyé

   ```bash
   curl -H "Authorization: Bearer TOKEN" http://localhost/api/endpoint
   ```

### "Refresh token failed"

**Problème :** `refreshToken()` lève une exception.

**Solutions :**

1. Vérifiez que vous êtes authentifié

   ```php
   if ($auth->isAuthenticated()) {
       $newToken = $auth->refreshToken();
   }
   ```

2. Vérifiez que le token actuel est valide

   ```php
   $token = $auth->token();
   if ($token) {
       try {
           $auth->verifyToken($token);
       } catch (Exception $e) {
           echo "Token actuel invalide: " . $e->getMessage();
       }
   }
   ```

### "Secret key is empty"

**Problème :** La clé secrète JWT n'est pas configurée.

**Solution :**

```php
<?php

// Vérifier la clé
$config = $auth->getConfig();
$secret = $config->get('jwt.secret');

if (empty($secret)) {
    echo "Clé secrète vide!";
}

// Générer une clé
$secret = bin2hex(random_bytes(32));
echo "Clé générée: $secret";
```

## Sessions

### "Session data not persisted"

**Problème :** Les données de session disparaissent.

**Solutions :**

1. Vérifiez les permissions du dossier session

   ```bash
   chmod 755 /var/lib/php/sessions
   ```

2. Vérifiez la configuration session

   ```php
   echo "Session path: " . session_save_path();
   echo "Session handler: " . ini_get('session.save_handler');
   ```

3. Vérifiez la durée de vie

   ```php
   echo "Session lifetime: " . ini_get('session.cookie_lifetime');
   ```

### "Multiple sessions"

**Problème :** Plusieurs sessions sont créées.

**Solution :**

```php
<?php

// Régénérer l'ID de session après login
session_regenerate_id(true);

// Vérifier qu'une seule session existe
echo "Session ID: " . session_id();
```

### "Session lost after redirect"

**Problème :** La session est perdue après une redirection.

**Solutions :**

1. Assurez-vous que `session_start()` est appelé avant toute sortie

   ```php
   <?php
   session_start(); // Avant tout autre code
   ```

2. Vérifiez les cookies

   ```bash
   # Via curl
   curl -b "PHPSESSID=value" http://localhost
   ```

3. Vérifiez la configuration PHP

   ```ini
   session.use_cookies = 1
   session.use_only_cookies = 1
   session.use_trans_sid = 0
   ```

## Autorisation

### "Permission not found"

**Problème :** La permission ne peut pas être vérifiée.

**Solutions :**

1. Vérifiez que le fournisseur d'autorisation est configuré

   ```php
   $provider = $auth->getAuthorizationProvider();
   if (!$provider) {
       echo "AuthorizationProvider non configuré";
   }
   ```

2. Vérifiez que la permission existe en base de données

   ```sql
   SELECT * FROM permissions WHERE name = 'edit_posts';
   ```

3. Vérifiez que l'utilisateur a le rôle

   ```php
   $roles = $provider->getRoles($userId);
   var_dump($roles);
   ```

### "Role not assigned"

**Problème :** `hasRole()` retourne false bien que le rôle soit assigné.

**Solutions :**

1. Vérifiez la relation en base

   ```sql
   SELECT * FROM user_roles WHERE user_id = 1;
   ```

2. Vérifiez la requête SQL

   ```php
   $roles = $provider->getRoles($userId);
   var_dump($roles); // Doit contenir le rôle
   ```

3. Vérifiez la casse

   ```php
   // Les rôles sont case-sensitive
   $provider->hasRole($userId, 'admin');  // ✓
   $provider->hasRole($userId, 'Admin');  // ✗
   ```

## 2FA

### "2FA code not verified"

**Problème :** Le code 2FA est rejeté bien qu'il soit correct.

**Solutions :**

1. Vérifiez que 2FA est activé

   ```php
   $twoFactor = $auth->getTwoFactorProvider();
   if (!$twoFactor) {
       echo "2FA non disponible";
   }
   ```

2. Vérifiez le secret stocké

   ```sql
   SELECT totp_secret FROM users WHERE id = 1;
   ```

3. Vérifiez la synchronisation horaire

   ```bash
   date # Comparez avec le serveur
   ```

4. Vérifiez la fenêtre de temps

   ```php
   // La fenêtre est généralement 1 (±30 secondes)
   $window = $config->get('two_factor.window', 1);
   ```

### "Secret not generated"

**Problème :** `enable()` ne retourne pas le secret.

**Solution :**

```php
<?php

try {
    $result = $twoFactor->enable($userId);

    if (!isset($result['secret'])) {
        echo "Secret manquant";
    } else {
        echo "Secret: " . $result['secret'];
    }
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
```

## Base de données

### "Database connection failed"

**Problème :** Impossible de se connecter à la base de données.

**Solutions :**

1. Vérifiez les identifiants

   ```php
   try {
       $pdo = new PDO(
           'mysql:host=localhost;dbname=bauth_db',
           'root',
           'password'
       );
   } catch (PDOException $e) {
       echo "Erreur: " . $e->getMessage();
   }
   ```

2. Vérifiez que le serveur est en ligne

   ```bash
   telnet localhost 3306
   ```

3. Vérifiez que la base de données existe

   ```sql
   SHOW DATABASES;
   ```

### "Table not found"

**Problème :** La table users n'existe pas.

**Solution :**

```bash
# Créer la table
php bin/console doctrine:migrations:migrate  # Symfony

# Ou manuellement
mysql -u root -p bauth_db < schema.sql
```

### "Column 'password' doesn't have a default value"

**Problème :** Le champ password n'est pas nullable.

**Solution :**

```sql
ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL;
```

## Framework-spécifique

### Laravel

#### "Service Provider not loading"

**Solutions :**

1. Vérifiez que le provider est enregistré

   ```php
   // config/app.php
   'providers' => [
       App\Providers\BAuthServiceProvider::class,
   ]
   ```

2. Effacez le cache

   ```bash
   php artisan config:clear
   ```

#### "app('bauth') not found"

**Solution :**

```php
<?php

// Utiliser depuis le container
$auth = app(BAuthService::class)->getAuth();

// Ou créer un facade
use App\Services\BAuthService;
$auth = app(BAuthService::class)->getAuth();
```

### Symfony

#### "Service BAuthService not found"

**Solutions :**

1. Vérifiez `services.yaml`

   ```yaml
   services:
     App\Service\BAuthService:
       arguments:
         $jwtSecret: "%env(AUTH_JWT_SECRET)%"
   ```

2. Rechargez les services

   ```bash
   bin/console cache:clear
   ```

#### "AuthGuard not working"

**Solutions :**

1. Vérifiez `security.yaml`

   ```yaml
   security:
     firewalls:
       api:
         custom_authenticator: App\Security\BAuthGuard
   ```

2. Vérifiez que le Guard est autoconfigurable

   ```yaml
   services:
     App\Security\BAuthGuard:
       tags:
         - name: security.authenticator
   ```

## Logs et débogage

### Activer le verbose logging

```php
<?php

$logger = new \Monolog\Logger('BAuth');
$handler = new \Monolog\Handler\StreamHandler('logs/bauth.log');
$logger->pushHandler($handler);

try {
    $auth->login($email, $password);
} catch (Exception $e) {
    $logger->error('Login failed', [
        'email' => $email,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
}
```

### Debugger les requêtes SQL

```php
<?php

// PDO
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
} catch (PDOException $e) {
    echo "Erreur SQL: " . $e->getMessage();
}
```

### Var_dump le contexte

```php
<?php

echo "<pre>";
var_dump([
    'authenticated' => $auth->isAuthenticated(),
    'user' => $auth->user(),
    'token' => substr($auth->token() ?? '', 0, 50),
    'session' => $_SESSION,
]);
echo "</pre>";
```

## Support

Si vous avez des problèmes non couverts ici :

1. Consultez la [documentation d'utilisation](USAGE.md)
2. Consultez la [référence API](API.md)
3. Explorez les [exemples](../examples/)
4. Ouvrez une issue sur GitHub
