# Migration vers BAuth 1.1.0

Ce guide vous aide à ajouter les 5 nouvelles fonctionnalités à votre projet BAuth existant.

## ✅ Checklist d'installation

- [ ] Mettre à jour BAuth via Composer
- [ ] Créer les migrations de base de données
- [ ] Exécuter les migrations
- [ ] Configurer les variables d'environnement
- [ ] Intégrer les nouveaux providers
- [ ] Tester les nouvelles fonctionnalités

## 1️⃣ Mettre à jour BAuth

```bash
composer update bmvc/bauth
```

## 2️⃣ Créer les migrations

### Pour Laravel

```bash
# OAuth2 / Social Accounts
php artisan make:migration create_social_accounts_table
php artisan make:migration create_api_keys_table
php artisan make:migration create_sessions_table
php artisan make:migration create_webauthn_credentials_table
php artisan make:migration create_webauthn_backup_codes_table
```

Copier les schémas des migrations depuis [INSTALLATION.md](docs/INSTALLATION.md#installation-avec-laravel)

### Pour Symfony

```bash
# Générer les entités
php bin/console make:entity SocialAccount
php bin/console make:entity ApiKey
php bin/console make:entity Session
php bin/console make:entity WebAuthnCredential
php bin/console make:entity WebAuthnBackupCode

# Créer les migrations
php bin/console make:migration
```

### Pour PHP natif

Exécuter manuellement les scripts SQL depuis [INSTALLATION.md](docs/INSTALLATION.md#configuration-de-la-base-de-données)

## 3️⃣ Exécuter les migrations

### Laravel

```bash
php artisan migrate
```

### Symfony

```bash
php bin/console doctrine:migrations:migrate
```

## 4️⃣ Configurer les variables d'environnement

Ajouter à votre fichier `.env`:

```env
# OAuth2
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://yourapp.com/callback/google

GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=
GITHUB_REDIRECT_URI=https://yourapp.com/callback/github

# WebAuthn
WEBAUTHN_RP_NAME=Mon Application
WEBAUTHN_ORIGIN=https://yourapp.com
```

## 5️⃣ Intégrer les nouveaux providers

### Laravel - Dans `app/Providers/AuthServiceProvider.php`

```php
<?php

use Bmvc\BAuth\Adapters\Laravel\LaravelOAuth2Provider;
use Bmvc\BAuth\Adapters\Laravel\LaravelSocialLoginProvider;
use Bmvc\BAuth\Adapters\Laravel\LaravelAPIKeyProvider;
use Bmvc\BAuth\Adapters\Laravel\LaravelMultiSessionProvider;
use Bmvc\BAuth\Adapters\Laravel\LaravelWebAuthnProvider;

public function boot()
{
    $this->app->singleton('oauth2', function () {
        return new LaravelOAuth2Provider();
    });

    $this->app->singleton('social-login', function () {
        return new LaravelSocialLoginProvider();
    });

    $this->app->singleton('api-key', function () {
        return new LaravelAPIKeyProvider();
    });

    $this->app->singleton('multi-session', function () {
        return new LaravelMultiSessionProvider();
    });

    $this->app->singleton('webauthn', function () {
        return new LaravelWebAuthnProvider(env('APP_URL'));
    });
}
```

### Symfony - Dans `config/services.yaml`

```yaml
services:
  oauth2:
    class: Bmvc\BAuth\Adapters\Symfony\SymfonyOAuth2Provider
    arguments:
      - "@doctrine.orm.entity_manager"

  social_login:
    class: Bmvc\BAuth\Adapters\Symfony\SymfonySocialLoginProvider
    arguments:
      - "@doctrine.orm.entity_manager"

  api_key:
    class: Bmvc\BAuth\Adapters\Symfony\SymfonyAPIKeyProvider
    arguments:
      - "@doctrine.orm.entity_manager"

  multi_session:
    class: Bmvc\BAuth\Adapters\Symfony\SymfonyMultiSessionProvider
    arguments:
      - "@doctrine.orm.entity_manager"

  webauthn:
    class: Bmvc\BAuth\Adapters\Symfony\SymfonyWebAuthnProvider
    arguments:
      - "@doctrine.orm.entity_manager"
      - "%env(APP_URL)%"
```

## 6️⃣ Créer les routes

### OAuth2 Callback (Laravel)

```php
// routes/web.php

Route::get('/auth/{provider}/callback', function ($provider) {
    $oauth2 = app('oauth2');
    $code = request('code');
    $state = request('state');

    try {
        $result = $oauth2->handleCallback($provider, $code, $state);
        auth()->login($result['user']);
        return redirect('/dashboard');
    } catch (\Exception $e) {
        return redirect('/login')->withErrors($e->getMessage());
    }
});
```

### API Key Middleware (Laravel)

```php
// app/Http/Middleware/ApiKeyMiddleware.php

class ApiKeyMiddleware
{
    public function handle($request, $next)
    {
        $apiKeyProvider = app('api-key');
        $apiKey = $request->header('X-API-Key');
        $secret = $request->header('X-API-Secret');

        if (!$apiKey || !$secret || !$apiKeyProvider->validateApiKey($apiKey, $secret)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = $apiKeyProvider->getUserFromApiKey($apiKey);
        auth()->login($user);

        return $next($request);
    }
}
```

## 🧪 Tester les nouvelles fonctionnalités

### OAuth2

```php
$oauth2 = app('oauth2');
$url = $oauth2->getAuthorizationUrl('google', bin2hex(random_bytes(16)));
echo "Redirect: " . $url;
```

### Social Login

```php
$socialLogin = app('social-login');
$user = $socialLogin->getUserByExternalId('google', 'google-id-123');
echo "User: " . $user['name'];
```

### API Keys

```php
$apiKey = app('api-key');
$key = $apiKey->generateApiKey(auth()->id(), 'Test Key', ['api.read']);
echo "API Key: " . $key['api_key'];
echo "Secret: " . $key['secret'];
```

### Multi-Session

```php
$multiSession = app('multi-session');
$session = $multiSession->createSession(
    auth()->id(),
    'Firefox sur Windows',
    $_SERVER['HTTP_USER_AGENT'],
    $_SERVER['REMOTE_ADDR']
);
echo "Session ID: " . $session['session_id'];
```

### WebAuthn

```php
$webauthn = app('webauthn');
$challenge = $webauthn->startRegistration(auth()->id(), auth()->user()->email, auth()->user()->name);
echo json_encode($challenge);
```

## 📚 Guides détaillés

- [OAuth2](docs/OAUTH2.md)
- [Connexion Sociale](docs/SOCIAL_LOGIN.md)
- [Clés API](docs/API_KEYS.md)
- [Sessions Multiples](docs/MULTI_SESSION.md)
- [WebAuthn](docs/WEBAUTHN.md)

## 🆘 Dépannage

### Erreur: "Table does not exist"

Vérifiez que les migrations ont été exécutées:

```bash
# Laravel
php artisan migrate:status

# Symfony
php bin/console doctrine:migrations:status
```

### Erreur: "Provider not found"

Vérifiez que les providers sont bien enregistrés dans votre Service Provider / services.yaml

### Erreur: "OAuth callback failed"

1. Vérifiez vos variables d'environnement
2. Vérifiez votre redirect_uri
3. Consultez le troubleshooting: [TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md)

## ✨ Résumé

Vous avez maintenant ajouté à votre application BAuth:

✅ OAuth2 - Authentification par Google, GitHub, Facebook, Microsoft
✅ Social Login - Liaison de comptes sociaux
✅ API Keys - Clés API pour les applications tierces
✅ Multi-Session - Gestion de multiples sessions par utilisateur
✅ WebAuthn - Authentification sans mot de passe

Consultez la documentation complète pour apprendre à utiliser ces nouvelles fonctionnalités!
