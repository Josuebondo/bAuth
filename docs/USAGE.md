# Guide d'utilisation complet de BAuth

## Table des matières

1. [Concepts de base](#concepts-de-base)
2. [Authentification](#authentification)
3. [Gestion des sessions](#gestion-des-sessions)
4. [Tokens JWT](#tokens-jwt)
5. [Autorisation](#autorisation)
6. [2FA](#2fa)
7. [Gestion des utilisateurs](#gestion-des-utilisateurs)
8. [Gestion des erreurs](#gestion-des-erreurs)

## Concepts de base

BAuth fonctionne avec plusieurs composants :

- **AuthProvider** : Gère la base de données des utilisateurs
- **SessionProvider** : Gère les sessions PHP
- **TokenProvider** : Gère les tokens JWT
- **AuthorizationProvider** : Gère les rôles et permissions
- **TwoFactorProvider** : Gère le 2FA

```php
<?php

use BAuth\Auth;
use BAuth\Config;

// Créer la configuration
$config = new Config([
    'jwt' => ['secret' => 'votre-clé', 'expiresIn' => 3600],
    'password' => ['algorithm' => PASSWORD_BCRYPT, 'options' => ['cost' => 12]],
]);

// Créer l'instance Auth
$auth = new Auth($config);

// Configurer les providers
$auth->setAuthProvider($authProvider);      // Requis
$auth->setSessionProvider($sessionProvider); // Optionnel
$auth->setTokenProvider($tokenProvider);     // Optionnel
$auth->setAuthorizationProvider($authProvider); // Optionnel
$auth->setTwoFactorProvider($twoFactorProvider); // Optionnel
```

## Authentification

### Connexion basique

```php
<?php

try {
    $result = $auth->login('user@example.com', 'password123');

    // Résultat
    $user = $result['user'];    // Array avec les données utilisateur
    $token = $result['token'];  // Token JWT

    echo "Connecté: " . $user['email'];
} catch (\BAuth\Exceptions\AuthenticationException $e) {
    echo "Erreur: " . $e->getMessage();
} catch (\BAuth\Exceptions\UserNotFoundException $e) {
    echo "Utilisateur non trouvé";
}
```

### Vérifier l'authentification

```php
<?php

if ($auth->isAuthenticated()) {
    echo "L'utilisateur est connecté";
} else {
    echo "L'utilisateur n'est pas connecté";
}
```

### Obtenir l'utilisateur actuel

```php
<?php

$user = $auth->user();

if ($user) {
    echo "ID: " . $user['id'];
    echo "Email: " . $user['email'];
    echo "Nom d'utilisateur: " . $user['username'];
}
```

### Obtenir l'ID utilisateur

```php
<?php

$userId = $auth->userId();
echo "ID utilisateur: " . $userId;
```

### Déconnexion

```php
<?php

$auth->logout();
echo "Déconnecté avec succès";
```

## Gestion des sessions

Les sessions PHP sont gérées automatiquement par BAuth.

### Obtenir une valeur de session

```php
<?php

$token = $auth->user();                    // Récupérer l'utilisateur
$token = $auth->getSessionProvider()->get('auth_token'); // Récupérer le token
$customValue = $auth->getSessionProvider()->get('custom_key', 'default'); // Avec défaut
```

### Définir une valeur de session

```php
<?php

$auth->getSessionProvider()->put('key', 'value');
$auth->getSessionProvider()->put('nested.key', 'value');
```

### Supprimer une valeur de session

```php
<?php

$auth->getSessionProvider()->forget('key');
```

### Détruire la session

```php
<?php

$auth->getSessionProvider()->destroy();
```

## Tokens JWT

### Obtenir le token actuel

```php
<?php

$token = $auth->token();
echo "Token: " . $token;
```

### Renouveler le token

```php
<?php

try {
    $newToken = $auth->refreshToken();
    echo "Nouveau token: " . $newToken;
} catch (\BAuth\Exceptions\AuthenticationException $e) {
    echo "Erreur: " . $e->getMessage();
}
```

### Vérifier un token

```php
<?php

try {
    $payload = $auth->verifyToken($token);
    echo "Payload: " . json_encode($payload);
} catch (\BAuth\Exceptions\InvalidTokenException $e) {
    echo "Token invalide: " . $e->getMessage();
}
```

### Générer un token personnalisé

```php
<?php

$tokenProvider = $auth->getTokenProvider();
$token = $tokenProvider->generate([
    'user_id' => 123,
    'email' => 'user@example.com',
    'roles' => ['admin', 'user'],
], 7200); // Expire dans 2 heures

echo "Token: " . $token;
```

### Extraire le token de la requête

```php
<?php

$tokenProvider = $auth->getTokenProvider();
$token = $tokenProvider->extractFromRequest(); // Depuis header Authorization

if ($token) {
    echo "Token trouvé: " . substr($token, 0, 20) . "...";
} else {
    echo "Pas de token";
}
```

## Autorisation

### Vérifier une permission

```php
<?php

if ($auth->can('edit_posts')) {
    echo "L'utilisateur peut éditer les posts";
} else {
    echo "L'utilisateur ne peut pas éditer les posts";
}
```

### Vérifier un rôle

```php
<?php

if ($auth->hasRole('admin')) {
    echo "L'utilisateur est administrateur";
}

if ($auth->hasRole('moderator') || $auth->hasRole('admin')) {
    echo "L'utilisateur est modérateur ou administrateur";
}
```

### Autoriser une action (lève une exception)

```php
<?php

try {
    $auth->authorize('delete_users');
    // Continuer si autorisé
} catch (\BAuth\Exceptions\AuthorizationException $e) {
    echo "Non autorisé: " . $e->getMessage();
}
```

### Assigner des rôles et permissions

```php
<?php

$authProvider = $auth->getAuthorizationProvider();

// Assigner un rôle
$authProvider->assignRole($userId, 'admin');

// Retirer un rôle
$authProvider->removeRole($userId, 'admin');

// Assigner une permission à un rôle
$authProvider->assignPermission('admin', 'delete_users');

// Obtenir les rôles d'un utilisateur
$roles = $authProvider->getRoles($userId);

// Obtenir les permissions d'un utilisateur
$permissions = $authProvider->getPermissions($userId);
```

## 2FA

### Vérifier un code 2FA

```php
<?php

$code = $_POST['2fa_code'];

if ($auth->verify2FA($code)) {
    echo "2FA vérifié avec succès";
} else {
    echo "Code 2FA invalide";
}
```

### Activer le 2FA

```php
<?php

$twoFactorProvider = $auth->getTwoFactorProvider();

if ($twoFactorProvider) {
    $result = $twoFactorProvider->enable($userId);

    // Afficher le QR code
    echo "Secret: " . $result['secret'];
    echo "QR Code: " . $result['qr_code'];
}
```

### Désactiver le 2FA

```php
<?php

$twoFactorProvider = $auth->getTwoFactorProvider();

if ($twoFactorProvider) {
    $twoFactorProvider->disable($userId);
    echo "2FA désactivé";
}
```

### Vérifier si le 2FA est activé

```php
<?php

$twoFactorProvider = $auth->getTwoFactorProvider();

if ($twoFactorProvider && $twoFactorProvider->isEnabled($userId)) {
    echo "2FA activé pour cet utilisateur";
}
```

## Gestion des utilisateurs

### Créer un utilisateur

```php
<?php

$authProvider = $auth->getAuthProvider();

try {
    $user = $authProvider->createUser([
        'email' => 'newuser@example.com',
        'username' => 'newuser',
        'password' => 'securepassword123',
    ]);

    echo "Utilisateur créé: " . $user['id'];
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
```

### Obtenir un utilisateur

```php
<?php

$authProvider = $auth->getAuthProvider();

// Par ID
$user = $authProvider->getUserById(1);

// Par email
$user = $authProvider->getUserByEmail('user@example.com');

// Par identifiant (username ou email)
$user = $authProvider->getUserByIdentifier('user@example.com');

if ($user) {
    echo "Utilisateur trouvé: " . $user['email'];
}
```

### Mettre à jour un utilisateur

```php
<?php

$authProvider = $auth->getAuthProvider();

$updated = $authProvider->updateUser($userId, [
    'email' => 'newemail@example.com',
    'username' => 'newusername',
    'password' => 'newpassword123', // Sera hashé automatiquement
]);

if ($updated) {
    echo "Utilisateur mis à jour";
}
```

### Supprimer un utilisateur

```php
<?php

$authProvider = $auth->getAuthProvider();

$deleted = $authProvider->deleteUser($userId);

if ($deleted) {
    echo "Utilisateur supprimé";
}
```

### Valider les identifiants

```php
<?php

$authProvider = $auth->getAuthProvider();
$user = $authProvider->getUserByEmail('user@example.com');

if ($authProvider->validateCredentials($user, 'password123')) {
    echo "Identifiants valides";
} else {
    echo "Identifiants invalides";
}
```

## Gestion des mots de passe

### Hacher un mot de passe

```php
<?php

use BAuth\Support\Password;

$password = new Password($config);
$hashed = $password->hash('mypassword123');
```

### Vérifier un mot de passe

```php
<?php

$password = new Password($config);

if ($password->verify('mypassword123', $hashed)) {
    echo "Mot de passe correct";
}
```

### Vérifier si un hash doit être recalculé

```php
<?php

$password = new Password($config);

if ($password->needsRehash($currentHash)) {
    $newHash = $password->hash($plainTextPassword);
    // Mettre à jour le hash en base de données
}
```

### Générer un mot de passe aléatoire

```php
<?php

$password = new Password($config);
$randomPassword = $password->generate(16);
echo "Mot de passe généré: " . $randomPassword;
```

## Gestion des erreurs

### Exceptions disponibles

```php
<?php

use BAuth\Exceptions\AuthenticationException;
use BAuth\Exceptions\AuthorizationException;
use BAuth\Exceptions\InvalidTokenException;
use BAuth\Exceptions\UserNotFoundException;
use BAuth\Exceptions\BAuthException;

// Exceptions spécifiques
try {
    $auth->login('user@example.com', 'wrongpassword');
} catch (AuthenticationException $e) {
    // Authentification échouée
} catch (UserNotFoundException $e) {
    // Utilisateur non trouvé
} catch (InvalidTokenException $e) {
    // Token invalide
} catch (AuthorizationException $e) {
    // Non autorisé
} catch (BAuthException $e) {
    // Exception générique BAuth
}
```

### Gestion complète des erreurs

```php
<?php

try {
    $auth->authorize('admin_action');
    // Effectuer l'action
} catch (\BAuth\Exceptions\AuthenticationException $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
} catch (\BAuth\Exceptions\AuthorizationException $e) {
    http_response_code(403);
    echo json_encode(['error' => 'Non autorisé']);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
```

## Cas d'usage complets

### Formulaire de connexion

```php
<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $result = $auth->login($email, $password);
        $_SESSION['authenticated'] = true;
        header('Location: /dashboard');
        exit;
    } catch (\BAuth\Exceptions\AuthenticationException $e) {
        $error = 'Email ou mot de passe incorrect';
    } catch (\BAuth\Exceptions\UserNotFoundException $e) {
        $error = 'Cet email n\'existe pas';
    }
}
?>

<form method="POST">
    <?php if (isset($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <input type="email" name="email" required>
    <input type="password" name="password" required>
    <button type="submit">Connexion</button>
</form>
```

### Middleware de protection

```php
<?php

function requireAuth() {
    if (!$auth->isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['error' => 'Non authentifié']);
        exit;
    }
}

function requireRole($role) {
    requireAuth();

    if (!$auth->hasRole($role)) {
        http_response_code(403);
        echo json_encode(['error' => 'Non autorisé']);
        exit;
    }
}

function requirePermission($permission) {
    requireAuth();

    if (!$auth->can($permission)) {
        http_response_code(403);
        echo json_encode(['error' => 'Non autorisé']);
        exit;
    }
}

// Utilisation
requireAuth();
// Continuer si authentifié

requireRole('admin');
// Continuer si administrateur

requirePermission('edit_posts');
// Continuer si permission existe
```

## Prochaines étapes

- Consultez les [Exemples](../examples/)
- Découvrez les [Intégrations avec les frameworks](../docs/)
- Explorez l'[API de référence](API.md)
