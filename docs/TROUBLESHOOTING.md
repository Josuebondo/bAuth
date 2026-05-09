# 🧰 Guide de dépannage BAuth (version claire & pratique)

## 🎯 Objectif

Ce guide t’aide à :

- comprendre **pourquoi une erreur arrive**
- savoir **où regarder**
- appliquer **une solution rapide**
- éviter les erreurs fréquentes

---

# 🚀 1. Installation

## ❌ Class not found

### 💥 Erreur

```
Class "BAuth\Auth" not found
```

### 🧠 Pourquoi ?

PHP ne charge pas la librairie.

---

### ✅ Solution

```bash
composer install
composer dump-autoload
```

Et vérifie :

```php
require 'vendor/autoload.php';
```

---

### 💡 Explication

Composer doit “mapper” les classes BAuth → sinon PHP ne sait pas où elles sont.

---

## ❌ Firebase JWT missing

### 💥 Erreur

```
Class "Firebase\JWT\JWT" not found
```

### ✅ Solution

```bash
composer require firebase/jwt
```

---

# 🔐 2. Authentification

## ❌ Login échoue (Authentication failed)

### 💥 Symptôme

Impossible de se connecter même avec bons identifiants.

---

## 🧠 Comment diagnostiquer

```php
try {
    $auth->login($email, $password);
} catch (Exception $e) {
    dd($e->getMessage());
}
```

---

## 🔍 Causes possibles

### 1. ❌ Utilisateur introuvable

👉 La requête SQL ne trouve pas l’utilisateur

```sql
SELECT * FROM users WHERE email = 'test@mail.com';
```

---

### 2. ❌ Mot de passe incorrect

👉 Le mot de passe n’est pas hashé ou mal comparé

```php
password_verify($password, $hash);
```

---

### 3. ❌ Provider non configuré

👉 BAuth ne sait pas comment lire ta DB

```php
$auth->setAuthProvider($provider);
```

---

# 👤 3. User non trouvé

## 💥 Erreur

```
User not found
```

---

## 🧠 Pourquoi ?

- email incorrect
- colonne mal nommée
- DB vide

---

## ✅ Debug rapide

```php
$user = $auth->getAuthProvider()->getUserByEmail($email);
dd($user);
```

---

## 💡 Si null → problème DB

---

# 🔑 4. JWT (Token)

## ❌ Invalid Token

### 💥 Erreur

```
Invalid token
```

---

## 🧠 Causes

### 1. Token expiré

```php
exp < time()
```

✔ Solution :

```php
$auth->refreshToken();
```

---

### 2. Mauvaise clé secrète

👉 La clé doit être identique partout

```env
AUTH_JWT_SECRET=xxx
```

---

### 3. Token cassé

Format attendu :

```
header.payload.signature
```

---

## 🔍 Debug rapide

```php
dd(explode('.', $token));
```

---

# 📡 5. Token introuvable

## 💥 Erreur

```
Token not found
```

---

## 🧠 Pourquoi ?

Le header HTTP est mal envoyé.

---

## ✅ Solution Laravel / API

```http
Authorization: Bearer YOUR_TOKEN
```

---

## 🔍 Debug

```php
dd(getallheaders());
```

---

# 🧾 6. Session

## ❌ Session ne marche pas

### 💥 Symptôme

Utilisateur connecté → puis perdu

---

## 🧠 Causes

- session pas démarrée
- cookies désactivés
- middleware manquant

---

## ✅ Fix rapide

```php
session_start();
```

---

## 🔍 Debug session

```php
dd($_SESSION);
```

---

# 🛡️ 7. Permissions / Roles

## ❌ Role ne marche pas

---

## 🧠 Causes

- rôle non assigné
- casse différente (admin ≠ Admin)

---

## 🔍 Debug

```php
dd($auth->getAuthorizationProvider()->getRoles($userId));
```

---

## ⚠️ Important

Les rôles sont **case-sensitive**

```php
admin ❌
Admin ❌
ADMIN ❌
```

---

# 🔐 8. 2FA

## ❌ Code 2FA invalide

---

## 🧠 Causes

### 1. Mauvaise heure serveur

👉 TOTP dépend du temps

```bash
date
```

---

### 2. Secret incorrect

```sql
SELECT totp_secret FROM users;
```

---

### 3. Fenêtre trop stricte

```php
window = 1 (±30 secondes)
```

---

## 🔍 Debug

```php
dd($auth->getTwoFactorProvider());
```

---

# 🗄️ 9. Base de données

## ❌ DB connection failed

---

## 🧠 Causes

- mauvais login
- DB down
- port bloqué

---

## ✅ Test rapide

```php
$pdo = new PDO(...);
$pdo->query("SELECT 1");
```

---

## ❌ Table missing

```
Table users doesn't exist
```

---

## ✅ Fix

```sql
CREATE TABLE users (...);
```

---

# ⚙️ 10. Laravel spécifique

---

## ❌ app('bauth') not found

### 🧠 Cause

Service provider pas chargé

---

## ✅ Fix

```php
App\Providers\BAuthServiceProvider::class,
```

Puis :

```bash
php artisan config:clear
```

---

## ❌ Middleware ne marche pas

### 🧠 Cause

Pas enregistré dans Kernel

---

## ✅ Fix

```php
'bauth' => \App\Http\Middleware\BAuthMiddleware::class,
```

---

# 🧠 11. Debug PRO (important)

## 🔥 Toujours utiliser ça

```php
dd([
    'auth' => $auth->isAuthenticated(),
    'user' => $auth->user(),
    'token' => $auth->token(),
    'session' => $_SESSION ?? null,
]);
```

---

## 📌 Logs (très utile)

```php
error_log($e->getMessage());
```

---

# 🧭 12. Méthode pro de debug

Quand un bug arrive, toujours vérifier :

### 1. 🔌 DB connectée ?

### 2. 👤 user existe ?

### 3. 🔐 password correct ?

### 4. 🎟 token valide ?

### 5. ⚙ provider configuré ?

### 6. 📦 session active ?

---

# 🚀 Conclusion

👉 90% des bugs BAuth viennent de :

- provider mal configuré
- DB mal structurée
- token mal envoyé
- session non active
