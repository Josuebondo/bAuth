# 📘 Guide d’utilisation — BAuth

> Documentation complète pour utiliser BAuth dans vos projets PHP.

---

# 📑 Table des matières

- Concepts de base
- Authentification
- Sessions
- JWT
- Autorisation
- 2FA
- Gestion des utilisateurs
- Sécurité & mots de passe
- Gestion des erreurs
- Cas pratiques

---

# 🧠 Concepts de base

BAuth repose sur une architecture modulaire composée de providers :

- **AuthProvider** → gestion des utilisateurs
- **SessionProvider** → gestion des sessions
- **TokenProvider** → gestion JWT
- **AuthorizationProvider** → rôles & permissions
- **TwoFactorProvider** → authentification 2FA

---

## 🔧 Initialisation

```php
<?php

use Bmvc\BAuth\Auth;
use Bmvc\BAuth\Config;

$config = new Config([
    'jwt' => [
        'secret' => 'your-secret-key',
        'expiresIn' => 3600,
    ],
    'password' => [
        'algorithm' => PASSWORD_BCRYPT,
        'options' => ['cost' => 12],
    ],
]);

$auth = new Auth($config);
```

---

# 🔐 Authentification

## Connexion

```php
<?php

$result = $auth->login('user@example.com', 'password123');

$user  = $result['user'];
$token = $result['token'];
```

---

## Vérifier l’authentification

```php
<?php

if ($auth->isAuthenticated()) {
    echo "Utilisateur connecté";
}
```

---

## Utilisateur actuel

```php
<?php

$user = $auth->user();

echo $user['email'];
```

---

## Déconnexion

```php
<?php

$auth->logout();
```

---

# 📦 Sessions

## Obtenir une session

```php
$auth->getSessionProvider()->get('key');
```

---

## Définir une session

```php
$auth->getSessionProvider()->put('key', 'value');
```

---

## Supprimer une session

```php
$auth->getSessionProvider()->forget('key');
```

---

## Détruire session

```php
$auth->getSessionProvider()->destroy();
```

---

# 🔑 JWT

## Récupérer token

```php
$token = $auth->token();
```

---

## Vérifier token

```php
$payload = $auth->verifyToken($token);
```

---

## Refresh token

```php
$newToken = $auth->refreshToken();
```

---

## Extraire token HTTP

```php
$token = $auth->getTokenProvider()->extractFromRequest();
```

---

# 🛡️ Autorisation

## Vérifier permission

```php
if ($auth->can('posts.edit')) {
    echo "Autorisé";
}
```

---

## Vérifier rôle

```php
if ($auth->hasRole('admin')) {
    echo "Admin";
}
```

---

## Action protégée

```php
$auth->authorize('users.delete');
```

---

## Gestion avancée

```php
$authProvider = $auth->getAuthorizationProvider();

$authProvider->assignRole($userId, 'admin');
$authProvider->assignPermission('admin', 'users.delete');

$roles = $authProvider->getRoles($userId);
$permissions = $authProvider->getPermissions($userId);
```

---

# 🔐 2FA (Two-Factor Authentication)

## Vérifier code

```php
$auth->verify2FA($code);
```

---

## Activer 2FA

```php
$result = $auth->getTwoFactorProvider()->enable($userId);

echo $result['secret'];
echo $result['qr_code'];
```

---

## Désactiver 2FA

```php
$auth->getTwoFactorProvider()->disable($userId);
```

---

## Vérifier statut

```php
$auth->getTwoFactorProvider()->isEnabled($userId);
```

---

# 👤 Gestion des utilisateurs

## Créer utilisateur

```php
$user = $auth->getAuthProvider()->createUser([
    'email' => 'user@example.com',
    'username' => 'user',
    'password' => 'password123',
]);
```

---

## Trouver utilisateur

```php
$user = $auth->getAuthProvider()->getUserById(1);
$user = $auth->getAuthProvider()->getUserByEmail('email@example.com');
```

---

## Mise à jour

```php
$auth->getAuthProvider()->updateUser($id, [
    'email' => 'new@email.com',
]);
```

---

## Supprimer utilisateur

```php
$auth->getAuthProvider()->deleteUser($id);
```

---

# 🔐 Sécurité des mots de passe

## Hash

```php
$password = new \Bmvc\BAuth\Support\Password($config);

$hash = $password->hash('mypassword');
```

---

## Vérification

```php
$password->verify('mypassword', $hash);
```

---

## Rehash automatique

```php
$password->needsRehash($hash);
```

---

## Générer mot de passe

```php
$password->generate(16);
```

---

# ⚠️ Gestion des erreurs

## Exceptions principales

```php
AuthenticationException
AuthorizationException
InvalidTokenException
UserNotFoundException
BAuthException
```

---

## Exemple global

```php
try {
    $auth->login($email, $password);

} catch (AuthenticationException $e) {
    echo "Erreur authentification";

} catch (AuthorizationException $e) {
    echo "Non autorisé";

} catch (Exception $e) {
    echo "Erreur serveur";
}
```

---

# 🧪 Cas pratique complet

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        $auth->login($_POST['email'], $_POST['password']);

        header('Location: /dashboard');
        exit;

    } catch (AuthenticationException $e) {
        $error = "Identifiants invalides";
    }
}
```

---

# 🔒 Middleware simple

```php
function auth_required() {
    global $auth;

    if (!$auth->isAuthenticated()) {
        http_response_code(401);
        exit("Non authentifié");
    }
}
```

---

# 🚀 Prochaines étapes

- JWT avancé
- Middleware Laravel / Symfony
- OAuth2
- API Keys
- Social Login
