# 📘 Guide d'utilisation — BAuth

> Documentation complète et progressive pour utiliser BAuth dans vos projets PHP.
> Parfait pour les débutants comme pour les utilisateurs avancés.

---

## 📑 Table des matières

1. [Concepts de base](#concepts-de-base) — Comprendre l'architecture
2. [Authentification](#authentification) — Login, logout, vérification
3. [Sessions](#sessions) — Gérer les données utilisateur
4. [Tokens JWT](#tokens-jwt) — Authentification par tokens
5. [Autorisation](#autorisation) — Rôles & permissions
6. [2FA](#2fa) — Authentification à deux facteurs
7. [Gestion des utilisateurs](#gestion-des-utilisateurs) — Créer, modifier, supprimer
8. [Sécurité des mots de passe](#sécurité-des-mots-de-passe) — Hachage & vérification
9. [Gestion des erreurs](#gestion-des-erreurs) — Exceptions & try/catch
10. [Cas pratiques](#cas-pratiques) — Exemples réels complets

---

# 🧠 Concepts de base

## Architecture modulaire

BAuth fonctionne sur une architecture basée sur des **providers** - des composants modulaires chacun responsable d'une partie de l'authentification:

| Provider                  | Responsabilité       | Exemple                         |
| ------------------------- | -------------------- | ------------------------------- |
| **AuthProvider**          | Gestion utilisateurs | Créer, chercher, vérifier users |
| **SessionProvider**       | Données de session   | Stocker variables utilisateur   |
| **TokenProvider**         | Tokens JWT           | Générer, valider tokens         |
| **AuthorizationProvider** | Rôles & permissions  | Admin, modérateur, viewer       |
| **TwoFactorProvider**     | Authentification 2FA | Google Authenticator            |

**Avantage:** Vous pouvez remplacer n'importe quel provider par votre propre implémentation!

---

## 🔧 Initialisation (première étape)

Avant de pouvoir authentifier des utilisateurs, vous devez initialiser BAuth avec une configuration:

```php
<?php

require 'vendor/autoload.php';

use Bmvc\BAuth\Auth;
use Bmvc\BAuth\Config;

// Étape 1: Créer la configuration
$config = new Config([
    'jwt' => [
        'secret' => $_ENV['AUTH_JWT_SECRET'] ?? 'dev-secret',
        'expiresIn' => 3600,  // 1 heure
    ],
    'password' => [
        'algorithm' => PASSWORD_BCRYPT,
        'options' => ['cost' => 12],  // Plus haut = plus sécurisé mais plus lent
    ],
]);

// Étape 2: Créer l'instance Auth
$auth = new Auth($config);

// Étape 3: Configurer le provider (voir page Intégration)
// Ici vous configurez comment accéder à vos utilisateurs
// $auth->setAuthProvider($yourProvider);

// Étape 4: Prêt à l'emploi!
// $auth->login(...);
```

**À retenir:**

- ✅ `Config` = paramètres d'authentification
- ✅ `Auth` = le centre du contrôle
- ✅ Providers = la connexion à vos données

---

# 🔐 Authentification

L'authentification est le processus d'identification de l'utilisateur (ex: connexion).

## Connexion utilisateur

C'est l'opération la plus courante - permettre à un utilisateur de se connecter:

```php
<?php

try {
    // Tentative de connexion
    $result = $auth->login('user@example.com', 'password123');

    // Récupérer les données
    $user = $result['user'];      // Les infos utilisateur
    $token = $result['token'];    // Le token JWT

    // ✅ Connexion réussie!
    echo "Bienvenue, " . $user['email'];

} catch (AuthenticationException $e) {
    // ❌ Connexion échouée (email/password incorrect)
    echo "Identifiants invalides";
}
```

**Que se passe-t-il en arrière-plan?**

1. BAuth cherche l'utilisateur par email
2. Vérifie que le mot de passe est correct (avec bcrypt)
3. Crée une session utilisateur
4. Génère un token JWT
5. Retourne les deux

**Codes d'erreur possibles:**

- Email non trouvé → `AuthenticationException`
- Mot de passe incorrect → `AuthenticationException`
- Utilisateur désactivé → `AuthenticationException`

---

## Vérifier si l'utilisateur est connecté

Avant d'accéder à des données protégées, vérifiez l'authentification:

```php
<?php

// Simple: vérifier la connexion
if ($auth->isAuthenticated()) {
    echo "✅ Utilisateur connecté";
} else {
    echo "❌ Non connecté - redirection vers login";
    header('Location: /login');
    exit;
}

// Plus détaillé
$isConnected = $auth->isAuthenticated();
if (!$isConnected) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}
```

---

## Récupérer l'utilisateur actuel

Une fois connecté, récupérez les données utilisateur:

```php
<?php

if ($auth->isAuthenticated()) {
    $user = $auth->user();

    // Accès aux données
    echo "Email: " . $user['email'];
    echo "ID: " . $user['id'];
    echo "Créé: " . $user['created_at'];

    // Vous pouvez aussi obtenir juste l'ID
    $userId = $auth->userId();
}
```

---

## Déconnexion utilisateur

Terminer la session de l'utilisateur:

```php
<?php

// Déconnexion
$auth->logout();

// Vérifier après
if (!$auth->isAuthenticated()) {
    echo "✅ Déconnexion réussie";
    header('Location: /login');
}
```

**Important:** `logout()` :

- Supprime la session PHP
- Révoque le token JWT
- Nettoie tous les cookies

---

# 📦 Sessions

Les sessions permettent de stocker des données associées à l'utilisateur connecté.

**Différence avec les tokens JWT:**

- Sessions = données côté serveur
- Tokens = données envoyées au client

## Utiliser les sessions

```php
<?php

// Obtenir le provider
$session = $auth->getSessionProvider();

// Écrire une valeur
$session->put('user_preferences', 'dark_mode');
$session->put('cart_items', ['item1', 'item2']);

// Lire une valeur
$theme = $session->get('user_preferences');  // 'dark_mode'
$items = $session->get('cart_items');        // ['item1', 'item2']

// Vérifier si existe
if ($session->has('cart_items')) {
    echo "Panier trouvé!";
}

// Supprimer une valeur
$session->forget('user_preferences');

// Supprimer tout
$session->destroy();
```

**À retenir:**

- ✅ Les sessions persistent entre les requêtes HTTP
- ✅ Idéal pour stocker les préférences utilisateur
- ✅ Sécurisé (données côté serveur)

---

# 🔑 Tokens JWT

JWT = JSON Web Token. C'est un format pour envoyer des données sécurisées au client.

**Quand utiliser JWT?**

- API REST (endpoints JSON)
- Applications mobiles
- Single Page Applications (React, Vue, Angular)
- Authentification sans état (stateless)

## Récupérer le token JWT

Une fois connecté, le token est automatiquement généré:

```php
<?php

if ($auth->isAuthenticated()) {
    $token = $auth->token();

    // Token est quelque chose comme:
    // eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

    echo $token;
}
```

**Le client doit l'envoyer en `Authorization` header:**

```javascript
// JavaScript/React
const response = await fetch("/api/user", {
  headers: {
    Authorization: `Bearer ${token}`,
  },
});
```

## Vérifier un token

Quand le client envoie un token, vous devez le valider:

```php
<?php

// Extraire le token de la requête
$token = $auth->getTokenProvider()->extractFromRequest();

if ($token) {
    try {
        $payload = $auth->verifyToken($token);

        // Token valide! Récupérer les données
        $userId = $payload['user_id'];
        $email = $payload['email'];

        echo "✅ Token valide pour: " . $email;

    } catch (InvalidTokenException $e) {
        echo "❌ Token invalide ou expiré";
    }
} else {
    echo "❌ Pas de token fourni";
}
```

## Renouveler un token expirant

Les tokens ont une durée de vie (par défaut 1 heure). Vous pouvez les renouveler:

```php
<?php

// Vérifier si le token est bientôt expirant
if ($auth->token()) {
    // Générer un nouveau token
    $newToken = $auth->refreshToken();

    // Envoyer au client
    echo json_encode(['token' => $newToken]);
}
```

---

# 🛡️ Autorisation

L'**autorisation** vérifie si l'utilisateur a le DROIT de faire quelque chose.
(différent de l'authentification qui vérifie QUI il est)

## Rôles (roles)

Les rôles sont des groupes de permissions. Exemple: admin, modérateur, utilisateur.

```php
<?php

// Vérifier si l'utilisateur a un rôle
if ($auth->hasRole('admin')) {
    echo "Vous êtes admin";
} else {
    echo "Vous n'êtes pas admin";
}

// Vérifier plusieurs rôles
if ($auth->hasRole(['admin', 'moderator'])) {
    echo "Vous êtes admin OU modérateur";
}
```

## Permissions (permissions)

Les permissions sont des actions spécifiques. Exemple: `posts.create`, `users.delete`.

```php
<?php

// Vérifier une permission
if ($auth->can('posts.create')) {
    echo "✅ Vous pouvez créer un post";
} else {
    echo "❌ Vous ne pouvez pas créer de post";
}

// Utilisation pratique
if ($auth->can('users.delete')) {
    // Afficher le bouton supprimer
    echo '<button>Supprimer</button>';
}
```

## Autoriser une action (throw exception)

Plutôt que de faire un if/else, vous pouvez autoriser directement:

```php
<?php

try {
    // Si l'utilisateur n'a pas la permission, une exception est levée
    $auth->authorize('users.delete');

    // Si on arrive ici, l'utilisateur a la permission
    deleteUser($userId);

} catch (AuthorizationException $e) {
    http_response_code(403);
    exit("Accès refusé");
}
```

## Gérer les rôles et permissions

Assigner/retirer rôles et permissions:

```php
<?php

$authProvider = $auth->getAuthorizationProvider();

// Assigner un rôle à un utilisateur
$authProvider->assignRole($userId, 'admin');

// Assigner une permission à un rôle
$authProvider->assignPermission('admin', 'users.delete');

// Retirer un rôle
$authProvider->revokeRole($userId, 'admin');

// Récupérer les rôles d'un utilisateur
$roles = $authProvider->getRoles($userId);

// Récupérer les permissions d'un utilisateur
$permissions = $authProvider->getPermissions($userId);
```

---

# 🔐 2FA

L'authentification à deux facteurs ajoute une sécurité supplémentaire en demandant un code en plus du mot de passe.

## Activer la 2FA pour un utilisateur

```php
<?php

try {
    $userId = $auth->userId();

    // Générer un secret 2FA
    $result = $auth->getTwoFactorProvider()->enable($userId);

    $secret = $result['secret'];        // La clé secrète
    $qrCode = $result['qr_code'];      // Code QR à scanner

    // Afficher le code QR à l'utilisateur
    echo '<img src="' . $qrCode . '">';
    echo 'Scannez ce code avec Google Authenticator';

    // Sauvegarder temporairement (avant que l'utilisateur valide)
    session()->put('pending_2fa_secret', $secret);

} catch (Exception $e) {
    echo "Erreur lors de l'activation 2FA";
}
```

## Valider un code 2FA

```php
<?php

// L'utilisateur entre le code (6 chiffres) de son Google Authenticator
$code = $_POST['code'];  // '123456'

try {
    $isValid = $auth->getTwoFactorProvider()->verify($auth->userId(), $code);

    if ($isValid) {
        echo "✅ Code valide - 2FA activée!";
    } else {
        echo "❌ Code invalide";
    }

} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
```

## Vérifier lors de la connexion

```php
<?php

try {
    $result = $auth->login($email, $password);

    $user = $result['user'];

    // Vérifier si l'utilisateur a 2FA activée
    if ($auth->getTwoFactorProvider()->isEnabled($user['id'])) {
        // Demander le code 2FA
        header('Location: /enter-2fa-code');
        exit;
    }

    // Pas de 2FA, connexion complète
    session()->put('user_id', $user['id']);
    header('Location: /dashboard');

} catch (AuthenticationException $e) {
    echo "Identifiants invalides";
}
```

## Désactiver la 2FA

```php
<?php

$auth->getTwoFactorProvider()->disable($auth->userId());
echo "2FA désactivée";
```

---

# 👤 Gestion des utilisateurs

Créer, chercher, modifier, supprimer des utilisateurs.

## Créer un nouvel utilisateur

```php
<?php

try {
    $user = $auth->getAuthProvider()->createUser([
        'email' => 'john@example.com',
        'username' => 'john',
        'password' => 'SecurePassword123!',
        // Autres champs selon votre BD
        'full_name' => 'John Doe',
    ]);

    echo "✅ Utilisateur créé: " . $user['id'];

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
```

## Chercher un utilisateur

```php
<?php

// Par ID
$user = $auth->getAuthProvider()->getUserById(1);

// Par email
$user = $auth->getAuthProvider()->getUserByEmail('user@example.com');

// Si trouvé
if ($user) {
    echo "Utilisateur trouvé: " . $user['email'];
} else {
    echo "Utilisateur non trouvé";
}
```

## Mettre à jour un utilisateur

```php
<?php

try {
    $auth->getAuthProvider()->updateUser($userId, [
        'email' => 'new@example.com',
        'full_name' => 'John Updated',
    ]);

    echo "✅ Utilisateur mis à jour";

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
```

## Supprimer un utilisateur

```php
<?php

try {
    $auth->getAuthProvider()->deleteUser($userId);
    echo "✅ Utilisateur supprimé";

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
```

---

# 🔐 Sécurité des mots de passe

La classe `Password` gère le hachage et la vérification des mots de passe.

## Hacher un mot de passe

```php
<?php

use Bmvc\BAuth\Support\Password;

$password = new Password($config);

// Hacher un mot de passe
$plainPassword = 'MySecurePassword123!';
$hash = $password->hash($plainPassword);

// $hash ressemble à: $2y$12$abcdef...
// Le hash est unique à chaque fois (même mot de passe)
```

## Vérifier un mot de passe

```php
<?php

// Vérifier que le mot de passe correspond au hash
$isValid = $password->verify('MySecurePassword123!', $hash);

if ($isValid) {
    echo "✅ Mot de passe correct";
} else {
    echo "❌ Mot de passe incorrect";
}
```

## Contrôle de la complexité

```php
<?php

// Générer un mot de passe aléatoire sécurisé
$randomPassword = $password->generate(16);  // 16 caractères

// Vérifier si un hash doit être rafraîchi
// (utile après un changement d'algorithme de hachage)
if ($password->needsRehash($existingHash)) {
    $newHash = $password->hash($plainPassword);
}
```

---

# ⚠️ Gestion des erreurs

BAuth utilise des exceptions spécifiques pour les différentes erreurs.

## Exceptions principales

```php
<?php

use Bmvc\BAuth\Exceptions\{
    AuthenticationException,      // Erreur login
    AuthorizationException,       // Accès refusé
    InvalidTokenException,        // Token invalide/expiré
    UserNotFoundException,        // User n'existe pas
    BAuthException                // Erreur générique
};
```

## Capture des exceptions

```php
<?php

try {
    $auth->login($email, $password);

} catch (AuthenticationException $e) {
    // ❌ Email ou mot de passe incorrect
    http_response_code(401);
    echo "Identifiants invalides";

} catch (UserNotFoundException $e) {
    // ❌ L'utilisateur n'existe pas
    http_response_code(404);
    echo "Cet utilisateur n'existe pas";

} catch (AuthorizationException $e) {
    // ❌ L'utilisateur n'a pas les droits
    http_response_code(403);
    echo "Accès refusé";

} catch (BAuthException $e) {
    // ❌ Autre erreur BAuth
    http_response_code(500);
    echo "Erreur d'authentification";

} catch (Exception $e) {
    // ❌ Erreur PHP générale
    http_response_code(500);
    echo "Erreur serveur";

    // À développement uniquement!
    echo $e->getMessage();
}
```

---

# 🧪 Cas pratiques

Des exemples complets, prêts à utiliser.

## Exemple 1: Formulaire de connexion

```php
<?php

// Page: login.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $result = $auth->login($email, $password);

        // ✅ Connexion réussie
        $_SESSION['user_id'] = $result['user']['id'];
        header('Location: /dashboard');
        exit;

    } catch (AuthenticationException $e) {
        $error = "Email ou mot de passe incorrect";
    }
}

?>
<form method="POST">
    <?php if (isset($error)): ?>
        <p style="color: red;"><?= $error ?></p>
    <?php endif; ?>

    <input type="email" name="email" required>
    <input type="password" name="password" required>
    <button type="submit">Connexion</button>
</form>
```

## Exemple 2: Protection de route

```php
<?php

// Inclure au début d'une route protégée

if (!$auth->isAuthenticated()) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

// La route est protégée
$user = $auth->user();
echo "Bienvenue " . $user['email'];
```

## Exemple 3: API JSON avec JWT

```php
<?php

// API endpoint: /api/profile

header('Content-Type: application/json');

// Récupérer le token
$token = $auth->getTokenProvider()->extractFromRequest();

if (!$token) {
    http_response_code(401);
    exit(json_encode(['error' => 'No token']));
}

try {
    $payload = $auth->verifyToken($token);

    // ✅ Token valide
    $user = $auth->getAuthProvider()->getUserById($payload['user_id']);

    echo json_encode([
        'success' => true,
        'user' => $user
    ]);

} catch (InvalidTokenException $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
}
```

## Exemple 4: Vérification de permission

```php
<?php

// Protéger une action administrative

try {
    $auth->authorize('posts.delete');  // Levera une exception si pas autorisé

    // On peut supprimer
    deletePost($postId);
    echo "✅ Post supprimé";

} catch (AuthorizationException $e) {
    http_response_code(403);
    echo "❌ Vous n'avez pas la permission";
}
```

---

## 📚 Prochaines étapes

- **Besoin d'une API?** → Combiner JWT + API endpoints
- **Intégration Laravel?** → [Guide Laravel](LARAVEL.md)
- **Intégration Symfony?** → [Guide Symfony](SYMFONY.md)
- **Fonctionnalités avancées?** → [OAuth2](OAUTH2.md), [Social Login](SOCIAL_LOGIN.md), [WebAuthn](WEBAUTHN.md)
- **Sécurité?** → [Guide de sécurité](SECURITY.md)
