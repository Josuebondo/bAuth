Voici une version **améliorée, plus claire, plus “Laravel-style” et surtout expliquée (ce que fait chaque méthode + pourquoi tu l’utilises)**.

---

# 🔥 BAuth avec Laravel (Guide d’utilisation propre)

## 🎯 Objectif

BAuth s’intègre dans Laravel comme un **service d’authentification alternatif à Laravel Auth**.

Tu peux l’utiliser pour :

- login / logout
- JWT (API)
- rôles & permissions
- 2FA
- middleware
- API sécurisée

---

# 🚀 1. Installation

```bash
composer require bmvc/bauth
```

---

# ⚙️ 2. Service Provider (cœur de l’intégration)

## 📌 Créer le provider

```bash
php artisan make:provider BAuthServiceProvider
```

---

## 📌 Configuration du service

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Bmvc\BAuth\Config;
use Bmvc\BAuth\Auth;
use Bmvc\BAuth\Adapters\Laravel\LaravelAuthProvider;

class BAuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('bauth', function () {

            // 1. Configuration globale de BAuth
            $config = new Config([
                'jwt' => [
                    'secret' => env('AUTH_JWT_SECRET'),

                    // durée de vie du token (1h)
                    'expiresIn' => 3600,
                ],
            ]);

            // 2. Instance principale (le moteur d'auth)
            $auth = new Auth($config);

            // 3. Provider Laravel (connexion à la table users)
            $authProvider = new LaravelAuthProvider($config, 'users');

            // 4. On connecte BAuth à la base de données
            $auth->setAuthProvider($authProvider);

            return $auth;
        });
    }
}
```

---

## 💡 Ce que ça fait réellement

- `Config` → définit comment BAuth fonctionne (JWT, password…)
- `Auth` → moteur principal (login, logout, permissions)
- `LaravelAuthProvider` → connecte BAuth à ta table `users`
- `singleton('bauth')` → rend BAuth disponible partout dans Laravel

---

# 🧠 3. Comment utiliser BAuth dans Laravel

---

## 🔐 LOGIN (authentification)

```php
$auth = app('bauth');

$result = $auth->login($request->email, $request->password);
```

### 📌 Ce que fait `login()`

- vérifie email + password
- compare avec la base de données
- génère un JWT token
- retourne utilisateur + token

### 📦 Résultat

```php
[
    'user' => [...],
    'token' => 'eyJhbGciOi...'
]
```

---

## 🔐 LOGOUT

```php
$auth->logout();
```

### 📌 Ce que fait `logout()`

- supprime la session actuelle
- invalide l'utilisateur courant côté application

---

## 👤 UTILISATEUR ACTUEL

```php
$user = $auth->user();
```

### 📌 Ce que fait `user()`

- récupère l’utilisateur connecté via session ou token
- retourne un tableau utilisateur

---

## 🔎 CHECK AUTH

```php
if ($auth->isAuthenticated()) {
    // utilisateur connecté
}
```

### 📌 Ce que fait `isAuthenticated()`

- vérifie si un utilisateur est connecté
- basé sur session ou JWT

---

# 🧭 4. Routes Laravel (API)

```php
Route::post('/login', [AuthController::class, 'login']);

Route::get('/profile', [AuthController::class, 'profile'])
    ->middleware('bauth');
```

---

# 🛡️ 5. Middleware BAuth (protection des routes)

## 📌 Création

```bash
php artisan make:middleware BAuthMiddleware
```

---

## 📌 Code

```php
<?php

namespace App\Http\Middleware;

use Closure;

class BAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        $auth = app('bauth');

        // 🔐 Vérifie si utilisateur connecté
        if (!$auth->isAuthenticated()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        // 👤 injecte user dans request Laravel
        $request->setUserResolver(fn () => $auth->user());

        return $next($request);
    }
}
```

---

## 📌 Ce que fait ce middleware

- bloque accès si pas connecté
- ajoute user dans `$request->user()`

---

# 🔐 6. Middleware Role (accès admin)

```php
class BAuthRoleMiddleware
{
    public function handle($request, Closure $next, ...$roles)
    {
        $auth = app('bauth');

        if (!$auth->isAuthenticated()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        foreach ($roles as $role) {
            if ($auth->hasRole($role)) {
                return $next($request);
            }
        }

        return response()->json(['error' => 'Forbidden'], 403);
    }
}
```

---

## 📌 Utilisation

```php
Route::delete('/users/{id}', function () {
    //
})->middleware(['bauth', 'role:admin']);
```

---

# 🧠 7. Permissions (can)

```php
if ($auth->can('edit_posts')) {
    // autorisé
}
```

### 📌 Ce que fait `can()`

- vérifie si user a une permission
- basé sur roles + permissions DB

---

# 🔑 8. JWT (API)

## 📦 récupérer token

```php
$token = $auth->token();
```

---

## 📦 vérifier token

```php
$payload = $auth->verifyToken($token);
```

### 📌 Ce que fait `verifyToken()`

- vérifie signature JWT
- vérifie expiration
- retourne payload utilisateur

---

## 🔄 refresh token

```php
$newToken = $auth->refreshToken();
```

### 📌 Ce que fait `refreshToken()`

- génère un nouveau token
- sans refaire login

---

# 🔐 9. Controller Laravel (exemple propre)

```php
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $auth = app('bauth');

        $result = $auth->login(
            $request->email,
            $request->password
        );

        return response()->json($result);
    }

    public function profile()
    {
        $auth = app('bauth');

        return response()->json([
            'user' => $auth->user()
        ]);
    }

    public function logout()
    {
        app('bauth')->logout();

        return response()->json(['message' => 'logged out']);
    }
}
```

---

# 🧪 10. Résumé mental simple

## 💡 BAuth dans Laravel =

| Action         | Méthode             |
| -------------- | ------------------- |
| Login          | `login()`           |
| Logout         | `logout()`          |
| User connecté  | `user()`            |
| Vérifier login | `isAuthenticated()` |
| Permissions    | `can()`             |
| Rôles          | `hasRole()`         |
| Token          | `token()`           |
| Vérifier token | `verifyToken()`     |

---

# 🔥 11. Architecture Laravel (simple)

```
Controller
    ↓
app('bauth')
    ↓
Auth (core)
    ↓
LaravelAuthProvider
    ↓
Database (users, roles, permissions)
```

---

# 🚀 Conclusion

Avec Laravel, BAuth devient :

👉 un **Auth service central**
👉 remplaçant flexible de Laravel Auth
👉 compatible API JWT + session
👉 prêt pour RBAC + 2FA
