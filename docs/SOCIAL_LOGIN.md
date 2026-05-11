# Social Login

La connexion sociale permet aux utilisateurs de se connecter avec leurs comptes sociaux existants (Google, GitHub, Facebook, etc.) sans créer de compte supplémentaire.

## Installation

Social Login est inclus par défaut dans BAuth.

## Configuration

### Configuration de base

```php
<?php

use Bmvc\BAuth\Providers\BaseSocialLoginProvider;

$socialLogin = new BaseSocialLoginProvider();

// Définir les callbacks
$socialLogin
    ->setGetUserCallback(function($userId) {
        return User::find($userId);
    })
    ->setCreateUserCallback(function($data) {
        return User::create($data);
    })
    ->setUpdateUserCallback(function($userId, $data) {
        $user = User::find($userId);
        $user->update($data);
        return $user;
    });
```

## Utilisation

### 1. Vérifier si un utilisateur existe via un compte social

```php
<?php

if ($socialLogin->userExists('google', '123456789')) {
    echo "Cet utilisateur a un compte Google lié";
}
```

### 2. Obtenir un utilisateur via un compte social

```php
<?php

$user = $socialLogin->getUserByExternalId('github', 'github-user-id');

if ($user) {
    echo "Utilisateur trouvé: " . $user['name'];
}
```

### 3. Lier un compte social à un utilisateur existant

```php
<?php

$userId = auth()->id();
$externalId = 'google-123456';
$data = [
    'email' => 'user@example.com',
    'name' => 'John Doe',
    'picture' => 'https://example.com/avatar.jpg',
];

$socialLogin->linkSocialAccount($userId, 'google', $externalId, $data);
```

### 4. Créer un nouvel utilisateur via un compte social

```php
<?php

$data = [
    'email' => 'newuser@example.com',
    'name' => 'New User',
    'picture' => 'https://example.com/avatar.jpg',
];

$user = $socialLogin->createUserFromSocial('github', 'github-12345', $data);

// Créer une session
auth()->login($user);
```

### 5. Obtenir tous les comptes sociaux d'un utilisateur

```php
<?php

$accounts = $socialLogin->getSocialAccounts(auth()->id());

foreach ($accounts as $account) {
    echo $account['provider'] . " lié le " . $account['linked_at'];
}
```

### 6. Délier un compte social

```php
<?php

$socialLogin->unlinkSocialAccount(auth()->id(), 'google');
```

### 7. Mettre à jour les données d'un compte social

```php
<?php

$socialLogin->updateSocialAccount(auth()->id(), 'google', [
    'picture' => 'https://example.com/new-avatar.jpg',
    'email' => 'newemail@example.com',
]);
```

## Workflow complet d'authentification sociale

```php
<?php

// Étape 1: Redirection vers le fournisseur OAuth2
$state = bin2hex(random_bytes(16));
$oauth2 = new BaseOAuth2Provider();
header('Location: ' . $oauth2->getAuthorizationUrl('google', $state));

// Étape 2: Traiter le callback
$code = $_GET['code'];
$result = $oauth2->handleCallback('google', $code, $state);
$socialData = $result['user_info'];

// Étape 3: Vérifier si l'utilisateur existe
$socialLogin = new BaseSocialLoginProvider();

if ($socialLogin->userExists('google', $socialData['id'])) {
    // Utilisateur existant
    $user = $socialLogin->getUserByExternalId('google', $socialData['id']);
    auth()->login($user);
} else {
    // Nouvel utilisateur
    $user = $socialLogin->createUserFromSocial('google', $socialData['id'], $socialData);
    auth()->login($user);
}

// Redirection
header('Location: /dashboard');
```

## Avec Laravel

```php
<?php

use Bmvc\BAuth\Adapters\Laravel\LaravelSocialLoginProvider;

$socialLogin = new LaravelSocialLoginProvider(
    'App\Models\User',
    'App\Models\SocialAccount'
);

// Vérifier si l'utilisateur existe
if ($socialLogin->userExists('google', $externalId)) {
    $user = $socialLogin->getUserByExternalId('google', $externalId);
}

// Lier un compte social
$socialLogin->linkSocialAccount(auth()->id(), 'google', $externalId, $data);

// Créer un nouvel utilisateur via un compte social
$user = $socialLogin->createUserFromSocial('google', $externalId, $data);
```

## Avec Symfony

```php
<?php

use Bmvc\BAuth\Adapters\Symfony\SymfonySocialLoginProvider;

$socialLogin = new SymfonySocialLoginProvider(
    $entityManager,
    'App\Entity\User',
    'App\Entity\SocialAccount'
);

// Vérifier si l'utilisateur existe
if ($socialLogin->userExists('github', $externalId)) {
    $user = $socialLogin->getUserByExternalId('github', $externalId);
}
```

## Gestion des cas particuliers

### Email déjà utilisé par un autre compte

```php
<?php

$socialLogin = new BaseSocialLoginProvider();

// Si l'email existe déjà dans la base de données
if (userExists($socialData['email'])) {
    // Option 1: Demander à l'utilisateur de se connecter d'abord
    // puis de lier le compte social à son compte existant

    // Option 2: Créer un compte avec un email modifié
    $data = $socialData;
    $data['email'] = 'social_' . time() . '@' . parse_url($socialData['picture'], PHP_URL_HOST);
    $user = $socialLogin->createUserFromSocial('google', $socialData['id'], $data);
}
```

### Mettre à jour le profil utilisateur depuis les réseaux sociaux

```php
<?php

$socialLogin = new BaseSocialLoginProvider();

// Mettre à jour régulièrement les données du profil
$user = $socialLogin->getUserByExternalId('google', $externalId);

if ($user) {
    $socialLogin->updateSocialAccount($user['id'], 'google', [
        'name' => $newData['name'],
        'picture' => $newData['picture'],
        'email' => $newData['email'],
    ]);
}
```

## Migration d'un compte existant

```php
<?php

// Utilisateur existant se connecte avec un compte social pour la première fois
$user = auth()->user();
$socialLogin = new BaseSocialLoginProvider();

// Lier son compte social
$socialLogin->linkSocialAccount($user->id, 'google', $googleId, $googleData);

echo "Votre compte Google a été lié avec succès!";
```

## Sécurité

- Vérifier toujours que l'email retourné est valide
- Valider les données avant de les stocker
- Utiliser HTTPS pour toutes les redirections
- Vérifier l'état CSRF lors du callback
- Ne pas stocker les tokens d'accès en clair si possible

## Variables d'environnement

```env
SOCIAL_PROVIDERS=google,github,facebook,microsoft
SOCIAL_AUTO_CREATE_ACCOUNT=true
SOCIAL_AUTO_LINK_ACCOUNT=false
```

## Événements

Vous pouvez écouter les événements de connexion sociale:

```php
<?php

// Avant la création d'un utilisateur
$socialLogin->onBeforeCreateUser(function($provider, $data) {
    // Validation personnalisée
    if (blacklist_check($data['email'])) {
        throw new Exception("Email blacklisté");
    }
});

// Après la liaison d'un compte social
$socialLogin->onAfterLinkSocialAccount(function($userId, $provider, $externalId) {
    // Envoyer un email de confirmation
    sendEmail($userId, "Votre compte $provider a été lié");
});
```
