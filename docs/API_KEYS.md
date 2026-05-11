# API Keys

BAuth fournit un système de gestion des clés API pour l'authentification des applications tierces et des clients API.

## Installation

API Keys est inclus par défaut dans BAuth.

## Configuration

### Configuration de base

```php
<?php

use Bmvc\BAuth\Providers\BaseAPIKeyProvider;

$apiKeyProvider = new BaseAPIKeyProvider();

// Définir le callback pour obtenir un utilisateur
$apiKeyProvider->setGetUserCallback(function($userId) {
    return User::find($userId);
});
```

## Utilisation

### 1. Générer une clé API

```php
<?php

$userId = auth()->id();

// Générer une clé API simple
$apiKey = $apiKeyProvider->generateApiKey(
    $userId,
    'Ma première clé API'
);

echo "Clé API: " . $apiKey['api_key'];
echo "Secret: " . $apiKey['secret']; // À sauvegarder de manière sécurisée
```

### 2. Générer une clé API avec permissions

```php
<?php

$apiKey = $apiKeyProvider->generateApiKey(
    $userId,
    'Clé de lecture',
    ['posts.read', 'users.read'],
    3600 * 24 * 30 // Expire dans 30 jours
);
```

### 3. Générer une clé API sans expiration

```php
<?php

$apiKey = $apiKeyProvider->generateApiKey(
    $userId,
    'Clé permanente',
    ['*'], // Toutes les permissions
    null // Pas d'expiration
);
```

### 4. Valider une clé API

```php
<?php

$apiKey = 'ak_xxxxx';
$secret = 'xxxxx'; // Le secret fourni par le client

if ($apiKeyProvider->validateApiKey($apiKey, $secret)) {
    echo "Clé valide!";
} else {
    echo "Clé invalide";
    http_response_code(401);
}
```

### 5. Obtenir les informations d'une clé API

```php
<?php

$info = $apiKeyProvider->getApiKeyInfo('ak_xxxxx');

echo "Nom: " . $info['name'];
echo "Crée le: " . $info['created_at'];
echo "Expire le: " . $info['expires_at'];
echo "Permissions: " . implode(', ', $info['permissions']);
```

### 6. Obtenir toutes les clés API d'un utilisateur

```php
<?php

$keys = $apiKeyProvider->getUserApiKeys(auth()->id());

foreach ($keys as $key) {
    echo $key['api_key'] . " - " . $key['name'];
}
```

### 7. Vérifier les permissions d'une clé API

```php
<?php

if ($apiKeyProvider->hasPermission('ak_xxxxx', 'posts.write')) {
    echo "La clé a la permission d'écrire les posts";
} else {
    echo "Permission refusée";
    http_response_code(403);
}
```

### 8. Révoquer une clé API

```php
<?php

$apiKeyProvider->revokeApiKey('ak_xxxxx');
```

### 9. Révoquer toutes les clés API d'un utilisateur

```php
<?php

$count = $apiKeyProvider->revokeAllUserApiKeys(auth()->id());

echo "Nombre de clés révoquées: $count";
```

### 10. Mettre à jour les permissions d'une clé API

```php
<?php

$apiKeyProvider->updateApiKeyPermissions('ak_xxxxx', [
    'posts.read',
    'posts.write',
    'users.read'
]);
```

### 11. Obtenir l'utilisateur d'une clé API

```php
<?php

$user = $apiKeyProvider->getUserFromApiKey('ak_xxxxx');

echo "Utilisateur: " . $user['name'];
```

### 12. Obtenir l'historique d'utilisation d'une clé

```php
<?php

$history = $apiKeyProvider->getApiKeyUsageHistory('ak_xxxxx', 100);

echo "Dernière utilisation: " . $history['last_used_at'];
```

## Utilisation dans une API

### Middleware d'authentification par clé API

```php
<?php

class ApiKeyMiddleware
{
    public function handle($request)
    {
        $apiKeyProvider = new BaseAPIKeyProvider();

        // Obtenir la clé depuis les headers
        $apiKey = $request->header('X-API-Key');
        $secret = $request->header('X-API-Secret');

        if (!$apiKey || !$secret) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        if (!$apiKeyProvider->validateApiKey($apiKey, $secret)) {
            return response()->json(['error' => 'Clé invalide'], 401);
        }

        // Ajouter l'utilisateur au contexte
        $user = $apiKeyProvider->getUserFromApiKey($apiKey);
        auth()->setUser($user);

        return $next($request);
    }
}
```

### Middleware de vérification des permissions

```php
<?php

class ApiPermissionMiddleware
{
    public function handle($request, $permission)
    {
        $apiKeyProvider = new BaseAPIKeyProvider();
        $apiKey = $request->header('X-API-Key');

        if (!$apiKeyProvider->hasPermission($apiKey, $permission)) {
            return response()->json(['error' => 'Permission refusée'], 403);
        }

        return $next($request);
    }
}

// Utilisation
// Vérifier la permission avant d'accéder à une route
Route::post('/posts', 'PostController@store')
    ->middleware('api-key:posts.write');
```

### Exemple d'API sécurisée

```php
<?php

// routes/api.php

Route::middleware('api-key')->group(function () {
    Route::get('/posts', 'PostController@index')
        ->middleware('api-permission:posts.read');

    Route::post('/posts', 'PostController@store')
        ->middleware('api-permission:posts.write');

    Route::put('/posts/{id}', 'PostController@update')
        ->middleware('api-permission:posts.write');

    Route::delete('/posts/{id}', 'PostController@destroy')
        ->middleware('api-permission:posts.delete');
});
```

## Avec Laravel

```php
<?php

use Bmvc\BAuth\Adapters\Laravel\LaravelAPIKeyProvider;

$apiKeyProvider = new LaravelAPIKeyProvider(
    'App\Models\User',
    'App\Models\ApiKey'
);

// Générer une clé
$apiKey = $apiKeyProvider->generateApiKey(auth()->id(), 'Ma clé');

// Valider une clé
if ($apiKeyProvider->validateApiKey($apiKey['api_key'], $apiKey['secret'])) {
    // Clé valide
}
```

## Avec Symfony

```php
<?php

use Bmvc\BAuth\Adapters\Symfony\SymfonyAPIKeyProvider;

$apiKeyProvider = new SymfonyAPIKeyProvider(
    $entityManager,
    'App\Entity\User',
    'App\Entity\ApiKey'
);

$apiKey = $apiKeyProvider->generateApiKey($user->getId(), 'Ma clé');
```

## Permissions recommandées

```
posts.read
posts.write
posts.delete
users.read
users.write
users.delete
comments.read
comments.write
comments.delete
admin.*
```

## Sécurité

- **Ne pas exposer les secrets**: Traiter les secrets comme des mots de passe
- **Utiliser HTTPS**: Toujours utiliser HTTPS pour les requêtes API
- **Hachage des secrets**: Les secrets sont toujours hachés en base de données
- **Expiration**: Définir une expiration appropriée pour les clés
- **Rotation**: Rotationner régulièrement les clés importantes
- **Logging**: Enregistrer les tentatives d'accès avec des clés invalides
- **Rate limiting**: Implémenter un rate limiting par clé API

## Bonnes pratiques

1. Créer une clé par application/service
2. Limiter les permissions au minimum nécessaire
3. Utiliser des noms descriptifs
4. Définir une expiration appropriée
5. Monitorer l'utilisation des clés
6. Révoquer rapidement les clés compromises
7. Utiliser les variables d'environnement pour les secrets

## Variables d'environnement

```env
API_KEY_MAX_LIFETIME=2592000
API_KEY_DEFAULT_PERMISSIONS=api.read
API_KEY_RATE_LIMIT=100/hour
API_KEY_LOG_ENABLED=true
```

## Exemple complet

```php
<?php

// routes/api.php

use Bmvc\BAuth\Adapters\Laravel\LaravelAPIKeyProvider;

$apiKeyProvider = new LaravelAPIKeyProvider();

// Créer un endpoint pour générer une clé
Route::post('/api-keys', function() {
    $apiKey = $apiKeyProvider->generateApiKey(
        auth()->id(),
        request('name'),
        request('permissions', []),
        request('expires_in')
    );

    return response()->json($apiKey);
});

// Créer un endpoint pour lister les clés
Route::get('/api-keys', function() {
    $keys = $apiKeyProvider->getUserApiKeys(auth()->id());
    return response()->json($keys);
});

// Créer un endpoint pour révoquer une clé
Route::delete('/api-keys/{api_key}', function($apiKey) {
    $apiKeyProvider->revokeApiKey($apiKey);
    return response()->json(['message' => 'Clé révoquée']);
});
```
