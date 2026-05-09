# BAuth avec Laravel

## Installation

### 1. Installer BAuth

```bash
composer require bauth/bauth
```

### 2. Créer un Service Provider

```bash
php artisan make:provider BAuthServiceProvider
```

Modifiez `app/Providers/BAuthServiceProvider.php` :

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use BAuth\Config;
use BAuth\Auth;
use BAuth\Examples\Laravel\LaravelAuthProvider;

class BAuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('bauth', function () {
            $config = new Config([
                'jwt' => [
                    'secret' => config('auth.jwt_secret'),
                    'expiresIn' => 3600,
                ],
            ]);

            $auth = new Auth($config);
            $authProvider = new LaravelAuthProvider($config, 'users');
            $auth->setAuthProvider($authProvider);

            return $auth;
        });
    }

    public function boot()
    {
        //
    }
}
```

### 3. Enregistrer le Service Provider

Modifiez `config/app.php` :

```php
'providers' => [
    // ...
    App\Providers\BAuthServiceProvider::class,
],
```

### 4. Configurer `.env`

```env
AUTH_JWT_SECRET=your-secret-key-here
```

Générez une clé :

```bash
php -r "echo bin2hex(random_bytes(32));"
```

## Configuration de la table users

BAuth fonctionne avec la migration Laravel standard des utilisateurs.

Si vous devez ajouter des colonnes, utilisez une migration :

```bash
php artisan make:migration AddBAuthColumnsToUsersTable
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBAuthColumnsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('totp_secret')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('totp_secret');
            $table->dropColumn('two_factor_enabled');
        });
    }
}
```

## Utilisation basique

### Contrôleur d'authentification

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $auth = app('bauth');

        try {
            $result = $auth->login(
                $request->input('email'),
                $request->input('password')
            );

            return response()->json([
                'success' => true,
                'user' => $result['user'],
                'token' => $result['token'],
            ]);
        } catch (\BAuth\Exceptions\AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid credentials',
            ], 401);
        }
    }

    public function logout()
    {
        $auth = app('bauth');
        $auth->logout();

        return response()->json(['success' => true]);
    }

    public function profile()
    {
        $auth = app('bauth');

        if (!$auth->isAuthenticated()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json(['user' => $auth->user()]);
    }
}
```

### Routes

Modifiez `routes/api.php` :

```php
<?php

use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:bauth');
Route::get('/profile', [AuthController::class, 'profile'])->middleware('auth:bauth');
```

## Middleware

### Créer un middleware BAuth

```bash
php artisan make:middleware BAuthMiddleware
```

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $auth = app('bauth');

        if (!$auth->isAuthenticated()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Ajouter l'utilisateur à la requête
        $request->setUserResolver(fn() => $auth->user());

        return $next($request);
    }
}
```

Enregistrez-le dans `app/Http/Kernel.php` :

```php
protected $routeMiddleware = [
    // ...
    'bauth' => \App\Http\Middleware\BAuthMiddleware::class,
];
```

### Middleware de rôle

```bash
php artisan make:middleware BAuthRoleMiddleware
```

```php
<?php

namespace App\Http\Middleware;

use Closure;

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

Utilisez-le :

```php
Route::delete('/users/{id}', function() {
    // ...
})->middleware('auth:bauth', 'role:admin');
```

## Contrôle d'accès (Authorization)

### Policy Laravel

```bash
php artisan make:policy PostPolicy
```

```php
<?php

namespace App\Policies;

class PostPolicy
{
    public function create()
    {
        $auth = app('bauth');
        return $auth->can('create_posts');
    }

    public function update()
    {
        $auth = app('bauth');
        return $auth->can('update_posts');
    }

    public function delete()
    {
        $auth = app('bauth');
        return $auth->can('delete_posts');
    }
}
```

### Utiliser les policies

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Policies\PostPolicy;

class PostController extends Controller
{
    public function store()
    {
        $this->authorize('create', Post::class);
        // Créer un post
    }
}
```

## Authentification JWT

### Extraire le token de la requête

```php
<?php

$auth = app('bauth');
$tokenProvider = $auth->getTokenProvider();
$token = $tokenProvider->extractFromRequest();

if ($token) {
    try {
        $payload = $auth->verifyToken($token);
        // Token valide
    } catch (\BAuth\Exceptions\InvalidTokenException $e) {
        // Token invalide
    }
}
```

### Retourner un token

```php
<?php

public function login(Request $request)
{
    $auth = app('bauth');

    try {
        $result = $auth->login($request->email, $request->password);

        return response()->json([
            'access_token' => $result['token'],
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 401);
    }
}
```

### Renouveler le token

```php
<?php

Route::post('/refresh-token', function (Request $request) {
    $auth = app('bauth');

    try {
        $newToken = $auth->refreshToken();

        return response()->json([
            'access_token' => $newToken,
            'token_type' => 'Bearer',
        ]);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 401);
    }
})->middleware('auth:bauth');
```

## 2FA avec Laravel

### Controller 2FA

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    public function enable()
    {
        $auth = app('bauth');
        $twoFactor = $auth->getTwoFactorProvider();

        if (!$twoFactor) {
            return response()->json(['error' => '2FA not available'], 400);
        }

        $userId = $auth->userId();
        $result = $twoFactor->enable($userId);

        return response()->json([
            'secret' => $result['secret'],
            'qr_code' => $result['qr_code'],
        ]);
    }

    public function verify(Request $request)
    {
        $auth = app('bauth');
        $code = $request->input('code');

        if ($auth->verify2FA($code)) {
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Invalid code'], 400);
    }

    public function disable()
    {
        $auth = app('bauth');
        $twoFactor = $auth->getTwoFactorProvider();

        $userId = $auth->userId();
        $twoFactor->disable($userId);

        return response()->json(['success' => true]);
    }
}
```

## Événements

### Dispatcher d'événements

```php
<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLoggedIn
{
    use Dispatchable, SerializesModels;

    public function __construct(public array $user, public string $token) {}
}

class UserLoggedOut
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $userId) {}
}
```

### Utiliser dans le contrôleur

```php
<?php

use App\Events\UserLoggedIn;
use App\Events\UserLoggedOut;

public function login(Request $request)
{
    $auth = app('bauth');
    $result = $auth->login($request->email, $request->password);

    event(new UserLoggedIn($result['user'], $result['token']));

    return response()->json($result);
}

public function logout()
{
    $auth = app('bauth');
    $userId = $auth->userId();
    $auth->logout();

    event(new UserLoggedOut($userId));

    return response()->json(['success' => true]);
}
```

## Configuration avancée

### Provider personnalisé avec le repository pattern

```php
<?php

namespace App\Repositories;

use BAuth\Providers\BaseAuthProvider;
use App\Models\User;

class BAuthUserRepository extends BaseAuthProvider
{
    public function getUserByIdentifier(string $identifier): ?array
    {
        $user = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        return $user ? $user->toArray() : null;
    }

    public function getUserByEmail(string $email): ?array
    {
        $user = User::where('email', $email)->first();
        return $user ? $user->toArray() : null;
    }

    public function getUserById(mixed $id): ?array
    {
        $user = User::find($id);
        return $user ? $user->toArray() : null;
    }

    public function createUser(array $userData): ?array
    {
        $userData['password'] = $this->password->hash($userData['password'] ?? '');
        $user = User::create($userData);
        return $user->toArray();
    }

    public function updateUser(mixed $userId, array $data): bool
    {
        if (isset($data['password'])) {
            $data['password'] = $this->password->hash($data['password']);
        }

        return User::find($userId)->update($data) > 0;
    }

    public function deleteUser(mixed $userId): bool
    {
        return User::find($userId)->delete();
    }
}
```

Enregistrez-le dans le Service Provider :

```php
public function register()
{
    $this->app->singleton('bauth', function () {
        $config = new Config([...]);
        $auth = new Auth($config);

        $authProvider = new \App\Repositories\BAuthUserRepository($config);
        $auth->setAuthProvider($authProvider);

        return $auth;
    });
}
```

## Tests

### Test unitaire

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_user_can_login()
    {
        $response = $this->post('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'user',
            'token',
        ]);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $response = $this->post('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_access_protected_route()
    {
        $auth = app('bauth');
        // Login user
        $auth->login('test@example.com', 'password123');

        $response = $this->get('/api/profile');

        $response->assertStatus(200);
    }
}
```

## Ressources supplémentaires

- [Guide d'utilisation complet](USAGE.md)
- [Référence API](API.md)
- [Exemples](../examples/)
