# OAuth2

BAuth supporte l'authentification OAuth2 avec plusieurs fournisseurs populaires (Google, GitHub, Facebook, Microsoft, etc.).

## Installation

OAuth2 est inclus par défaut dans BAuth. Aucune installation supplémentaire n'est requise.

## Configuration

### Configuration de base

```php
<?php

use Bmvc\BAuth\Config;
use Bmvc\BAuth\Providers\BaseOAuth2Provider;

$config = new Config([
    'oauth2' => [
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
        ],
        'github' => [
            'client_id' => env('GITHUB_CLIENT_ID'),
            'client_secret' => env('GITHUB_CLIENT_SECRET'),
            'redirect_uri' => env('GITHUB_REDIRECT_URI'),
        ],
    ],
]);

$oauth2 = new BaseOAuth2Provider();
$oauth2->registerProvider('google', env('GOOGLE_CLIENT_ID'), env('GOOGLE_CLIENT_SECRET'), env('GOOGLE_REDIRECT_URI'));
$oauth2->registerProvider('github', env('GITHUB_CLIENT_ID'), env('GITHUB_CLIENT_SECRET'), env('GITHUB_REDIRECT_URI'));
```

## Utilisation

### 1. Obtenir l'URL d'autorisation

```php
<?php

// Générer un état CSRF unique
$state = bin2hex(random_bytes(16));

// Rediriger vers le fournisseur
$authorizationUrl = $oauth2->getAuthorizationUrl('google', $state);
header('Location: ' . $authorizationUrl);
```

### 2. Traiter le callback

```php
<?php

$code = $_GET['code'];
$state = $_GET['state'];

try {
    $result = $oauth2->handleCallback('google', $code, $state);

    $user = $result['user'];
    $accessToken = $result['access_token'];
    $refreshToken = $result['refresh_token'];

    // Créer une session utilisateur
    $_SESSION['user'] = $user;
    $_SESSION['oauth_token'] = $accessToken;

} catch (Exception $e) {
    echo "Erreur d'authentification: " . $e->getMessage();
}
```

### 3. Obtenir les informations utilisateur

```php
<?php

$userInfo = $oauth2->getUserInfo('google', $accessToken);

echo "Email: " . $userInfo['email'];
echo "Nom: " . $userInfo['name'];
echo "Avatar: " . $userInfo['picture'];
```

### 4. Rafraîchir le token

```php
<?php

$newTokenData = $oauth2->refreshAccessToken('google', $refreshToken);

$accessToken = $newTokenData['access_token'];
$expiresIn = $newTokenData['expires_in'];
```

### 5. Révoquer le token

```php
<?php

$oauth2->revokeAccessToken('google', $accessToken);
```

## Fournisseurs supportés

- **Google**: `google`
- **GitHub**: `github`
- **Facebook**: `facebook`
- **Microsoft**: `microsoft`

## Ajouter un fournisseur personnalisé

```php
<?php

// Étendre la classe BaseOAuth2Provider
class CustomOAuth2Provider extends BaseOAuth2Provider
{
    protected function getProviderAuthorizationUrl(string $provider): string
    {
        if ($provider === 'custom') {
            return 'https://custom-provider.com/oauth/authorize';
        }
        return parent::getProviderAuthorizationUrl($provider);
    }

    protected function getProviderTokenUrl(string $provider): string
    {
        if ($provider === 'custom') {
            return 'https://custom-provider.com/oauth/token';
        }
        return parent::getProviderTokenUrl($provider);
    }

    protected function getProviderUserInfoUrl(string $provider): string
    {
        if ($provider === 'custom') {
            return 'https://api.custom-provider.com/user';
        }
        return parent::getProviderUserInfoUrl($provider);
    }

    protected function normalizeUserInfo(string $provider, array $data): array
    {
        if ($provider === 'custom') {
            return [
                'id' => $data['user_id'],
                'email' => $data['email_address'],
                'name' => $data['full_name'],
                'picture' => $data['avatar_url'],
            ];
        }
        return parent::normalizeUserInfo($provider, $data);
    }
}

$oauth2 = new CustomOAuth2Provider();
$oauth2->registerProvider('custom', 'client_id', 'client_secret', 'https://yourapp.com/callback');
```

## Avec Laravel

```php
<?php

use Bmvc\BAuth\Adapters\Laravel\LaravelOAuth2Provider;

$oauth2 = new LaravelOAuth2Provider(
    'App\Models\User',
    'App\Models\SocialAccount'
);

// Enregistrer les fournisseurs
$oauth2->registerProvider('google', env('GOOGLE_CLIENT_ID'), env('GOOGLE_CLIENT_SECRET'), env('GOOGLE_REDIRECT_URI'));
```

## Avec Symfony

```php
<?php

use Bmvc\BAuth\Adapters\Symfony\SymfonyOAuth2Provider;

$oauth2 = new SymfonyOAuth2Provider(
    $entityManager,
    'App\Entity\User',
    'App\Entity\SocialAccount'
);

$oauth2->registerProvider('google', env('GOOGLE_CLIENT_ID'), env('GOOGLE_CLIENT_SECRET'), env('GOOGLE_REDIRECT_URI'));
```

## Sécurité

- Toujours valider l'état CSRF
- Stocker les tokens de manière sécurisée
- Rafraîchir les tokens régulièrement
- Révoquer les tokens lors de la déconnexion

## Gestion des erreurs

```php
<?php

try {
    $result = $oauth2->handleCallback('google', $code, $state);
} catch (\Exception $e) {
    // État CSRF invalide
    if (strpos($e->getMessage(), 'État CSRF') !== false) {
        // Traiter l'erreur CSRF
    }

    // Fournisseur non configuré
    if (strpos($e->getMessage(), 'non configuré') !== false) {
        // Traiter l'erreur de configuration
    }
}
```

## Variables d'environnement

```env
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=https://yourapp.com/callback/google

GITHUB_CLIENT_ID=your-client-id
GITHUB_CLIENT_SECRET=your-client-secret
GITHUB_REDIRECT_URI=https://yourapp.com/callback/github
```
