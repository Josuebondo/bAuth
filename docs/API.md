# API de référence BAuth

## Table des matières

- [Classe Auth](#classe-auth)
- [Classe Config](#classe-config)
- [Interfaces](#interfaces)
- [Providers](#providers)
- [Exceptions](#exceptions)
- [Support](#support)

---

## Classe Auth

La classe principale pour gérer l'authentification.

### Constructeur

```php
public function __construct(Config $config)
```

**Paramètres:**

- `Config $config` - Configuration de l'application

**Exemple:**

```php
$config = new Config([...]);
$auth = new Auth($config);
```

### Méthodes de configuration

#### `setAuthProvider()`

```php
public function setAuthProvider(AuthProviderInterface $provider): self
```

Configure le fournisseur d'authentification (requis).

**Paramètres:**

- `AuthProviderInterface $provider` - Fournisseur d'authentification

**Retour:** `self` (pour le chaînage)

**Exemple:**

```php
$auth->setAuthProvider($authProvider);
```

#### `setSessionProvider()`

```php
public function setSessionProvider(SessionProviderInterface $provider): self
```

Configure le fournisseur de sessions.

#### `setTokenProvider()`

```php
public function setTokenProvider(TokenProviderInterface $provider): self
```

Configure le fournisseur de tokens JWT.

#### `setAuthorizationProvider()`

```php
public function setAuthorizationProvider(AuthorizationProviderInterface $provider): self
```

Configure le fournisseur d'autorisation (rôles/permissions).

#### `setTwoFactorProvider()`

```php
public function setTwoFactorProvider(TwoFactorProviderInterface $provider): self
```

Configure le fournisseur 2FA.

### Méthodes d'authentification

#### `login()`

```php
public function login(string $identifier, string $password, bool $rememberMe = false): array
```

Authentifie un utilisateur.

**Paramètres:**

- `string $identifier` - Email ou identifiant de l'utilisateur
- `string $password` - Mot de passe en clair
- `bool $rememberMe` - Non utilisé actuellement

**Retour:** `array` avec clés `user` et `token`

**Exceptions levées:**

- `AuthenticationException` - Identifiants invalides
- `UserNotFoundException` - Utilisateur non trouvé

**Exemple:**

```php
$result = $auth->login('user@example.com', 'password123');
$user = $result['user'];
$token = $result['token'];
```

#### `logout()`

```php
public function logout(): void
```

Déconnecte l'utilisateur actuel.

**Exemple:**

```php
$auth->logout();
```

#### `isAuthenticated()`

```php
public function isAuthenticated(): bool
```

Vérifie si un utilisateur est authentifié.

**Retour:** `bool` - true si authentifié, false sinon

**Exemple:**

```php
if ($auth->isAuthenticated()) {
    // ...
}
```

#### `user()`

```php
public function user(): ?array
```

Obtient l'utilisateur actuel.

**Retour:** `array` avec données utilisateur ou `null`

**Exemple:**

```php
$user = $auth->user();
echo $user['email'];
```

#### `userId()`

```php
public function userId(): ?string
```

Obtient l'ID de l'utilisateur actuel.

**Retour:** `string` - ID utilisateur ou `null`

**Exemple:**

```php
$id = $auth->userId();
```

#### `token()`

```php
public function token(): ?string
```

Obtient le token JWT actuel.

**Retour:** `string` - Token JWT ou `null`

**Exemple:**

```php
$token = $auth->token();
```

### Méthodes de tokens JWT

#### `verifyToken()`

```php
public function verifyToken(string $token): ?array
```

Vérifie et décode un token JWT.

**Paramètres:**

- `string $token` - Token JWT à vérifier

**Retour:** `array` - Payload du token

**Exceptions levées:**

- `InvalidTokenException` - Token invalide ou expiré

**Exemple:**

```php
try {
    $payload = $auth->verifyToken($token);
} catch (InvalidTokenException $e) {
    echo "Token invalide";
}
```

#### `refreshToken()`

```php
public function refreshToken(): string
```

Renouvelle le token JWT actuel.

**Retour:** `string` - Nouveau token

**Exceptions levées:**

- `AuthenticationException` - Pas de token trouvé

**Exemple:**

```php
$newToken = $auth->refreshToken();
```

### Méthodes 2FA

#### `verify2FA()`

```php
public function verify2FA(string $code): bool
```

Vérifie un code 2FA.

**Paramètres:**

- `string $code` - Code 2FA (6 chiffres pour TOTP)

**Retour:** `bool` - true si valide

**Exceptions levées:**

- `AuthenticationException` - Pas d'utilisateur en session

**Exemple:**

```php
if ($auth->verify2FA($code)) {
    // Code valide
}
```

### Méthodes d'autorisation

#### `can()`

```php
public function can(string $permission): bool
```

Vérifie si l'utilisateur a une permission.

**Paramètres:**

- `string $permission` - Nom de la permission

**Retour:** `bool`

**Exemple:**

```php
if ($auth->can('edit_posts')) {
    // Autoriser l'action
}
```

#### `hasRole()`

```php
public function hasRole(string $role): bool
```

Vérifie si l'utilisateur a un rôle.

**Paramètres:**

- `string $role` - Nom du rôle

**Retour:** `bool`

**Exemple:**

```php
if ($auth->hasRole('admin')) {
    // Utilisateur est admin
}
```

#### `authorize()`

```php
public function authorize(string $permission): void
```

Vérifie une permission et lève une exception si refusée.

**Paramètres:**

- `string $permission` - Nom de la permission

**Exceptions levées:**

- `AuthorizationException` - Permission refusée

**Exemple:**

```php
try {
    $auth->authorize('delete_users');
} catch (AuthorizationException $e) {
    echo "Non autorisé";
}
```

### Méthodes utilitaires

#### `getConfig()`

```php
public function getConfig(): Config
```

Obtient l'objet Config.

**Retour:** `Config`

#### `getAuthProvider()`

```php
public function getAuthProvider(): AuthProviderInterface
```

Obtient le fournisseur d'authentification.

#### `getSessionProvider()`

```php
public function getSessionProvider(): SessionProviderInterface
```

Obtient le fournisseur de sessions.

#### `getTokenProvider()`

```php
public function getTokenProvider(): TokenProviderInterface
```

Obtient le fournisseur de tokens.

#### `getAuthorizationProvider()`

```php
public function getAuthorizationProvider(): ?AuthorizationProviderInterface
```

Obtient le fournisseur d'autorisation (peut être null).

#### `getTwoFactorProvider()`

```php
public function getTwoFactorProvider(): ?TwoFactorProviderInterface
```

Obtient le fournisseur 2FA (peut être null).

---

## Classe Config

Gère la configuration de BAuth.

### Constructeur

```php
public function __construct(array $config = [])
```

**Paramètres:**

- `array $config` - Configuration personnalisée (fusionnée avec les défauts)

**Exemple:**

```php
$config = new Config([
    'jwt' => ['secret' => 'my-secret'],
]);
```

### Méthodes

#### `get()`

```php
public function get(string $key, mixed $default = null): mixed
```

Obtient une valeur de configuration.

**Paramètres:**

- `string $key` - Clé (peut utiliser la notation pointée: `jwt.secret`)
- `mixed $default` - Valeur par défaut

**Retour:** `mixed`

**Exemple:**

```php
$secret = $config->get('jwt.secret');
$cost = $config->get('password.options.cost', 12);
```

#### `set()`

```php
public function set(string $key, mixed $value): self
```

Définit une valeur de configuration.

**Paramètres:**

- `string $key` - Clé
- `mixed $value` - Valeur

**Retour:** `self` (pour le chaînage)

**Exemple:**

```php
$config->set('jwt.expiresIn', 7200);
```

#### `all()`

```php
public function all(): array
```

Obtient toute la configuration.

**Retour:** `array`

---

## Interfaces

### AuthProviderInterface

Gère l'authentification et les utilisateurs.

```php
interface AuthProviderInterface {
    public function authenticate(string $identifier, string $password): bool;
    public function getUser(): ?array;
    public function getUserById(mixed $id): ?array;
    public function getUserByEmail(string $email): ?array;
    public function createUser(array $userData): ?array;
    public function updateUser(mixed $userId, array $data): bool;
    public function deleteUser(mixed $userId): bool;
    public function validateCredentials(array $user, string $password): bool;
}
```

### TokenProviderInterface

Gère les tokens JWT.

```php
interface TokenProviderInterface {
    public function generate(array $payload, ?int $expiresIn = null): string;
    public function verify(string $token): ?array;
    public function extractFromRequest(): ?string;
    public function refresh(string $token): string;
}
```

### SessionProviderInterface

Gère les sessions.

```php
interface SessionProviderInterface {
    public function start(array $userData, string $token): void;
    public function get(string $key, mixed $default = null): mixed;
    public function put(string $key, mixed $value): void;
    public function forget(string $key): void;
    public function destroy(): void;
    public function isAuthenticated(): bool;
    public function getUserId(): ?string;
}
```

### AuthorizationProviderInterface

Gère les rôles et permissions.

```php
interface AuthorizationProviderInterface {
    public function hasRole(mixed $userId, string $role): bool;
    public function hasPermission(mixed $userId, string $permission): bool;
    public function assignRole(mixed $userId, string $role): bool;
    public function removeRole(mixed $userId, string $role): bool;
    public function assignPermission(string $role, string $permission): bool;
    public function getRoles(mixed $userId): array;
    public function getPermissions(mixed $userId): array;
}
```

### TwoFactorProviderInterface

Gère le 2FA.

```php
interface TwoFactorProviderInterface {
    public function generate(mixed $userId): string;
    public function verify(mixed $userId, string $code): bool;
    public function enable(mixed $userId): array;
    public function disable(mixed $userId): bool;
    public function isEnabled(mixed $userId): bool;
}
```

---

## Providers

### BaseAuthProvider

Classe de base abstraite pour implémenter `AuthProviderInterface`.

**Méthodes concrètes:**

- `authenticate()` - Authentifie un utilisateur
- `getUser()` - Obtient l'utilisateur courant
- `validateCredentials()` - Valide les identifiants

**Méthodes abstraites à implémenter:**

- `getUserByIdentifier()`
- `getUserByEmail()`
- `getUserById()`
- `createUser()`
- `updateUser()`
- `deleteUser()`

**Exemple:**

```php
class MyAuthProvider extends BaseAuthProvider {
    public function getUserByIdentifier(string $identifier): ?array { /* ... */ }
    public function getUserByEmail(string $email): ?array { /* ... */ }
    // ... autres implémentations
}
```

### JWTProvider

Fournisseur de tokens JWT (implémentation concrete).

Supporte firebase/jwt v5.x et v6.x+.

### SessionProvider

Fournisseur de sessions PHP (implémentation concrete).

### GenericAuthProvider

Fournisseur d'authentification générique utilisant des callbacks.

**Méthodes de configuration:**

- `setGetUserByIdentifierCallback()`
- `setGetUserByEmailCallback()`
- `setGetUserByIdCallback()`
- `setCreateUserCallback()`
- `setUpdateUserCallback()`
- `setDeleteUserCallback()`

### PDOAuthProvider

Fournisseur pour PDO (MySQL, PostgreSQL, SQLite, etc.).

```php
$pdo = new PDO('mysql:host=localhost;dbname=db', 'user', 'pass');
$provider = new PDOAuthProvider($config, $pdo, 'users');
```

### LaravelAuthProvider

Fournisseur pour Laravel utilisant Query Builder.

### SymfonyAuthProvider

Fournisseur pour Symfony utilisant Doctrine ORM.

---

## Exceptions

Toutes les exceptions héritent de `BAuthException`.

### BAuthException

Exception de base pour BAuth.

```php
throw new BAuthException('Message', 500);
```

### AuthenticationException

Levée lors d'une authentification échouée.

```php
throw new AuthenticationException('Invalid credentials', 401);
```

### AuthorizationException

Levée lors d'une autorisation refusée.

```php
throw new AuthorizationException('Forbidden', 403);
```

### InvalidTokenException

Levée quand un token est invalide.

```php
throw new InvalidTokenException('Token expired', 401);
```

### UserNotFoundException

Levée quand un utilisateur n'existe pas.

```php
throw new UserNotFoundException('User not found', 404);
```

---

## Support

### Classe Password

Utilitaires pour la gestion des mots de passe.

#### `hash()`

```php
public function hash(string $password): string
```

Hache un mot de passe avec bcrypt.

#### `verify()`

```php
public function verify(string $password, string $hash): bool
```

Vérifie qu'un mot de passe correspond à un hash.

#### `needsRehash()`

```php
public function needsRehash(string $hash): bool
```

Vérifie si un hash doit être recalculé.

#### `generate()`

```php
public function generate(int $length = 16): string
```

Génère un mot de passe aléatoire sécurisé.

**Exemple:**

```php
$password = new Password($config);
$hashed = $password->hash('mypassword');
if ($password->verify('mypassword', $hashed)) {
    echo "Correct!";
}
```

---

## Constantes

### Algorithmes de password

- `PASSWORD_BCRYPT` (2y) - Recommandé (2y = PHP 5.3+, 2a = PHP 5.3.7+)
- `PASSWORD_ARGON2I` - Algorithme Argon2i
- `PASSWORD_ARGON2ID` - Algorithme Argon2id

### Codes HTTP d'exceptions

- `401` - Authentication (AuthenticationException)
- `403` - Authorization (AuthorizationException)
- `404` - Not Found (UserNotFoundException)

---

## Guide d'extensibilité

### Créer un AuthProvider personnalisé

```php
<?php

use BAuth\Providers\BaseAuthProvider;

class MyAuthProvider extends BaseAuthProvider
{
    public function getUserByIdentifier(string $identifier): ?array
    {
        // Implémentation
    }

    public function getUserByEmail(string $email): ?array
    {
        // Implémentation
    }

    public function getUserById(mixed $id): ?array
    {
        // Implémentation
    }

    public function createUser(array $userData): ?array
    {
        // Implémentation
    }

    public function updateUser(mixed $userId, array $data): bool
    {
        // Implémentation
    }

    public function deleteUser(mixed $userId): bool
    {
        // Implémentation
    }
}
```

### Créer un TokenProvider personnalisé

```php
<?php

use BAuth\Contracts\TokenProviderInterface;

class MyTokenProvider implements TokenProviderInterface
{
    public function generate(array $payload, ?int $expiresIn = null): string { /* ... */ }
    public function verify(string $token): ?array { /* ... */ }
    public function extractFromRequest(): ?string { /* ... */ }
    public function refresh(string $token): string { /* ... */ }
}

$auth->setTokenProvider(new MyTokenProvider());
```

---

Pour plus d'exemples et de détails, consultez la [documentation d'utilisation](USAGE.md).
