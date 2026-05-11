# 📋 BAuth Quick Reference

> Snippets rapides pour les tâches courantes. Signet à garder à proximité!

---

## 🔧 Configuration

```php
use Bmvc\BAuth\Auth;
use Bmvc\BAuth\Config;

$config = new Config([
    'jwt' => ['secret' => 'votre-secret', 'expiresIn' => 3600],
    'password' => ['algorithm' => PASSWORD_BCRYPT, 'options' => ['cost' => 12]],
]);

$auth = new Auth($config);
```

---

## 🔐 Authentification

```php
// Connexion
$result = $auth->login('email@example.com', 'password');
$user = $result['user'];
$token = $result['token'];

// Vérifier si connecté
$auth->isAuthenticated();

// Récupérer l'utilisateur
$user = $auth->user();
$userId = $auth->userId();

// Déconnexion
$auth->logout();
```

---

## 🛡️ Autorisation

```php
// Vérifier une permission
$auth->can('posts.edit');

// Vérifier un rôle
$auth->hasRole('admin');

// Autoriser ou lever une exception
$auth->authorize('users.delete');

// Gérer les rôles/permissions
$authProvider = $auth->getAuthorizationProvider();
$authProvider->assignRole($userId, 'admin');
$authProvider->getRoles($userId);
```

---

## 📦 Sessions

```php
$session = $auth->getSessionProvider();

$session->put('key', 'value');
$session->get('key');
$session->has('key');
$session->forget('key');
$session->destroy();
```

---

## 🔑 Tokens JWT

```php
// Récupérer le token
$token = $auth->token();

// Vérifier un token
$payload = $auth->verifyToken($token);

// Renouveler le token
$newToken = $auth->refreshToken();

// Extraire d'une requête HTTP
$token = $auth->getTokenProvider()->extractFromRequest();
```

---

## 👤 Gestion utilisateurs

```php
$provider = $auth->getAuthProvider();

// Créer
$user = $provider->createUser([
    'email' => 'user@example.com',
    'password' => 'password123'
]);

// Chercher
$user = $provider->getUserById(1);
$user = $provider->getUserByEmail('user@example.com');

// Mettre à jour
$provider->updateUser($userId, ['email' => 'new@example.com']);

// Supprimer
$provider->deleteUser($userId);
```

---

## 🔐 Mots de passe

```php
use Bmvc\BAuth\Support\Password;

$password = new Password($config);

// Hacher
$hash = $password->hash('mypassword');

// Vérifier
$password->verify('mypassword', $hash);

// Générer aléatoire
$pwd = $password->generate(16);

// Besoin de rehash?
$password->needsRehash($hash);
```

---

## 🔐 2FA

```php
// Activer
$result = $auth->getTwoFactorProvider()->enable($userId);
$secret = $result['secret'];
$qrCode = $result['qr_code'];

// Vérifier
$auth->getTwoFactorProvider()->verify($userId, $code);

// Vérifier si activée
$auth->getTwoFactorProvider()->isEnabled($userId);

// Désactiver
$auth->getTwoFactorProvider()->disable($userId);
```

---

## ⚠️ Erreurs

```php
use Bmvc\BAuth\Exceptions\{
    AuthenticationException,
    AuthorizationException,
    InvalidTokenException,
    UserNotFoundException,
    BAuthException
};

try {
    $auth->login($email, $password);
} catch (AuthenticationException $e) {
    // Erreur login
} catch (AuthorizationException $e) {
    // Accès refusé
} catch (InvalidTokenException $e) {
    // Token invalide
} catch (Exception $e) {
    // Autre erreur
}
```

---

## 🌐 API / JWT

```php
// Endpoint protégé
if (!$auth->isAuthenticated()) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

$user = $auth->user();

// OU avec JWT dans le header

$token = $auth->getTokenProvider()->extractFromRequest();

if (!$token) {
    http_response_code(401);
    exit(json_encode(['error' => 'No token']));
}

try {
    $payload = $auth->verifyToken($token);
    $user = $auth->getAuthProvider()->getUserById($payload['user_id']);
} catch (InvalidTokenException $e) {
    http_response_code(401);
    exit(json_encode(['error' => 'Invalid token']));
}
```

---

## 🔒 Middleware

```php
// Fonction middleware
function require_auth() {
    global $auth;
    if (!$auth->isAuthenticated()) {
        http_response_code(401);
        exit('Unauthorized');
    }
}

// Utilisation
require_auth();
// route code...
```

---

## 🔀 Flux de connexion typique

```php
// 1. Vérifier la requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

// 2. Récupérer les données
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// 3. Valider
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) exit('Invalid email');

// 4. Essayer la connexion
try {
    $result = $auth->login($email, $password);

    // 5. Stocker la session
    $_SESSION['user_id'] = $result['user']['id'];

    // 6. Rediriger
    header('Location: /dashboard');
    exit;

} catch (AuthenticationException $e) {
    echo "Invalid credentials";
}
```

---

## 📍 Chemins courants

| Tâche        | Endroit                                           |
| ------------ | ------------------------------------------------- |
| Connexion    | `login.php` → `$auth->login()`                    |
| Accès pages  | Début du fichier → `$auth->isAuthenticated()`     |
| Permissions  | Avant action → `$auth->authorize()`               |
| API protégée | `$auth->getTokenProvider()->extractFromRequest()` |
| Admin panel  | `$auth->hasRole('admin')`                         |

---

## 🚀 Trucs & astuces

```php
// Redirection après login
header('Location: ' . ($_SESSION['redirect_to'] ?? '/dashboard'));

// Message flash
$_SESSION['message'] = 'Login successful';
if (isset($_SESSION['message'])) {
    echo $_SESSION['message'];
    unset($_SESSION['message']);
}

// Vérifier plusieurs rôles
if ($auth->hasRole(['admin', 'moderator'])) { }

// Vérifier avant d'agir
if (!$auth->can('posts.delete')) {
    http_response_code(403);
    exit;
}

// Passer l'utilisateur à une vue
extract(['user' => $auth->user()]);
include 'views/profile.php';
```

---

## 🔗 Ressources complètes

- 📖 [Guide complet](USAGE.md)
- 🚀 [Getting Started](GETTING_STARTED.md)
- 📦 [Installation](INSTALLATION.md)
- 🔒 [Sécurité](SECURITY.md)
- 🚨 [Dépannage](TROUBLESHOOTING.md)
- 💻 [Exemples](../examples/)
