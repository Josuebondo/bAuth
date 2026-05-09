# 📘 BAuth API

> Une API simple, expressive et orientée développeur pour l’authentification moderne en PHP.

---

# 🔐 Auth — le cœur de BAuth

La classe `Auth` fonctionne comme `Auth` dans Laravel :
👉 elle gère tout le cycle utilisateur (login, user, permissions, token)

---

## 🔑 Auth::login()

```php
$auth->login($email, $password);
```

### 🧠 Ce que ça fait

Cette méthode :

1. Cherche l’utilisateur en base (via AuthProvider)
2. Vérifie le mot de passe (bcrypt)
3. Si valide :
   - crée une session utilisateur
   - génère un token JWT
   - retourne l’utilisateur + token

4. Sinon → erreur

### 📦 Retour

```php
[
    "user" => [...],
    "token" => "jwt_token"
]
```

### 💡 Exemple

```php
$result = $auth->login("user@mail.com", "secret");

$user = $result["user"];
$token = $result["token"];
```

---

## 🚪 Auth::logout()

```php
$auth->logout();
```

### 🧠 Ce que ça fait

- supprime la session utilisateur
- déconnecte complètement l’utilisateur

👉 équivalent Laravel : `Auth::logout()`

---

## 👤 Auth::user()

```php
$auth->user();
```

### 🧠 Ce que ça fait

Retourne l’utilisateur actuellement connecté.

### 📦 Exemple

```php
$user = $auth->user();

echo $user["email"];
```

---

## 🆔 Auth::userId()

```php
$auth->userId();
```

### 🧠 Ce que ça fait

Retourne uniquement l’ID utilisateur connecté.

👉 plus léger que `user()`

---

## 🔍 Auth::isAuthenticated()

```php
$auth->isAuthenticated();
```

### 🧠 Ce que ça fait

Vérifie si un utilisateur est connecté via :

- session PHP
- ou token JWT

### 📦 Retour

```php
true  // connecté
false // pas connecté
```

---

# 🪪 JWT (API authentication)

---

## 🎟 Auth::token()

```php
$auth->token();
```

### 🧠 Ce que ça fait

Retourne le JWT du user connecté.

👉 utilisé pour API (React / mobile / SPA)

---

## 🔐 Auth::verifyToken()

```php
$auth->verifyToken($token);
```

### 🧠 Ce que ça fait

Valide un token JWT :

- signature
- expiration
- intégrité

### 📦 Retour

```php
[
    "user_id" => 1,
    "email" => "user@mail.com",
    "iat" => 123456,
    "exp" => 123999
]
```

---

## 🔄 Auth::refreshToken()

```php
$auth->refreshToken();
```

### 🧠 Ce que ça fait

- génère un nouveau JWT
- prolonge la session API
- garde le même utilisateur

👉 équivalent Laravel Sanctum refresh concept

---

# 🛡 Authorization (comme Laravel Gates)

---

## 🔐 Auth::can()

```php
$auth->can("edit_posts");
```

### 🧠 Ce que ça fait

- vérifie si l’utilisateur a une permission
- basé sur ses rôles

### 📦 Retour

```php
true / false
```

---

## 👑 Auth::hasRole()

```php
$auth->hasRole("admin");
```

### 🧠 Ce que ça fait

- vérifie si l’utilisateur possède un rôle

---

## 🚫 Auth::authorize()

```php
$auth->authorize("delete_users");
```

### 🧠 Ce que ça fait

- vérifie permission
- si refus → exception automatique

👉 comme Laravel `Gate::authorize()`

---

# 🔐 2FA (Two Factor Authentication)

---

## 🔢 Auth::verify2FA()

```php
$auth->verify2FA($code);
```

### 🧠 Ce que ça fait

- vérifie code Google Authenticator (TOTP)
- compare avec secret utilisateur
- valide fenêtre temporelle

---

# ⚙️ Config (style Laravel config helper)

---

## 📥 get()

```php
$config->get("jwt.secret");
```

### 🧠 Ce que ça fait

- lit une valeur de configuration
- supporte les chemins imbriqués

---

## 📤 set()

```php
$config->set("jwt.expiresIn", 7200);
```

### 🧠 Ce que ça fait

- modifie config dynamiquement

---

# 👤 AuthProvider (équivalent User Model layer)

---

## 🔎 getUserByEmail()

```php
$provider->getUserByEmail($email);
```

### 🧠 Ce que ça fait

- cherche un utilisateur par email
- utilisé automatiquement par login()

---

## 🔎 getUserById()

```php
$provider->getUserById($id);
```

### 🧠 Ce que ça fait

- récupère utilisateur par ID
- utilisé pour JWT restore session

---

## ➕ createUser()

```php
$provider->createUser($data);
```

### 🧠 Ce que ça fait

- crée un utilisateur en base
- hash password automatiquement (selon implémentation)

---

## ✏️ updateUser()

```php
$provider->updateUser($id, $data);
```

### 🧠 Ce que ça fait

- met à jour un utilisateur

---

## ❌ deleteUser()

```php
$provider->deleteUser($id);
```

### 🧠 Ce que ça fait

- supprime utilisateur

---

# 🔐 Password Helper (comme Hash:: dans Laravel)

---

## 🔒 hash()

```php
$password->hash("secret");
```

### 🧠 Ce que ça fait

- hash bcrypt sécurisé du mot de passe

---

## 🔍 verify()

```php
$password->verify("secret", $hash);
```

### 🧠 Ce que ça fait

- compare password brut avec hash DB

---

## 🎲 generate()

```php
$password->generate(12);
```

### 🧠 Ce que ça fait

- génère un mot de passe aléatoire sécurisé

---

# 💡 Résumé (style Laravel mindset)

| Action       | Méthode                           |
| ------------ | --------------------------------- |
| Login user   | `Auth::login()`                   |
| Get user     | `Auth::user()`                    |
| Check auth   | `Auth::check()` (isAuthenticated) |
| Logout       | `Auth::logout()`                  |
| Permissions  | `Auth::can()`                     |
| Roles        | `Auth::hasRole()`                 |
| Token        | `Auth::token()`                   |
| Verify token | `Auth::verifyToken()`             |

---

# 🚀 Philosophie BAuth (comme Laravel)

BAuth est construit pour être :

- simple comme Laravel Auth
- flexible comme Symfony Security
- compatible partout (framework agnostic)
