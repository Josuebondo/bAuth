# BAuth avec Symfony

## Installation

### 1. Installer BAuth

```bash
composer require bauth/bauth
```

### 2. Créer un Service pour BAuth

```bash
mkdir -p src/Service
touch src/Service/BAuthService.php
```

Créez `src/Service/BAuthService.php` :

```php
<?php

namespace App\Service;

use Bmvc\BAuth\Config;
use Bmvc\BAuth\Auth;
use Bmvc\BAuth\Adapters\Symfony\SymfonyAuthProvider;
use Doctrine\ORM\EntityManagerInterface;

class BAuthService
{
    private Auth $auth;

    public function __construct(
        EntityManagerInterface $entityManager,
        string $jwtSecret
    ) {
        $config = new Config([
            'jwt' => [
                'secret' => $jwtSecret,
                'expiresIn' => 3600,
            ],
        ]);

        $this->auth = new Auth($config);
        $authProvider = new SymfonyAuthProvider(
            $config,
            $entityManager,
            'App\Entity\User'
        );
        $this->auth->setAuthProvider($authProvider);
    }

    public function getAuth(): Auth
    {
        return $this->auth;
    }
}
```

### 3. Enregistrer le Service

Modifiez `config/services.yaml` :

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true

  App\Service\BAuthService:
    arguments:
      $jwtSecret: "%env(AUTH_JWT_SECRET)%"
```

### 4. Configurer `.env`

```env
AUTH_JWT_SECRET=your-secret-key-here
```

Générez une clé :

```bash
php -r "echo bin2hex(random_bytes(32));"
```

## Configuration de l'entité User

### Créer l'entité User

```bash
php bin/console make:entity User
```

Ajoutez les champs :

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", unique=true)
     */
    private string $email;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     */
    private ?string $username = null;

    /**
     * @ORM\Column(type="string")
     */
    private string $password;

    /**
     * @ORM\Column(type="datetime")
     */
    private \DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private \DateTime $updatedAt;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $totpSecret = null;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private bool $twoFactorEnabled = false;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // Getters et Setters
    public function getId(): int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function getUsername(): ?string { return $this->username; }
    public function setUsername(?string $username): self { $this->username = $username; return $this; }
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }
    public function setCreatedAt(\DateTime $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }
    public function setUpdatedAt(\DateTime $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
    public function getTotpSecret(): ?string { return $this->totpSecret; }
    public function setTotpSecret(?string $totpSecret): self { $this->totpSecret = $totpSecret; return $this; }
    public function isTwoFactorEnabled(): bool { return $this->twoFactorEnabled; }
    public function setTwoFactorEnabled(bool $twoFactorEnabled): self { $this->twoFactorEnabled = $twoFactorEnabled; return $this; }
}
```

### Créer la migration

```bash
php bin/console make:migration AddBAuthFields
php bin/console doctrine:migrations:migrate
```

## Contrôleurs

### Créer un contrôleur d'authentification

```bash
php bin/console make:controller AuthController
```

```php
<?php

namespace App\Controller;

use App\Service\BAuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/auth", name="auth_")
 */
class AuthController extends AbstractController
{
    public function __construct(private BAuthService $bAuthService) {}

    /**
     * @Route("/login", name="login", methods={"POST"})
     */
    public function login(Request $request): JsonResponse
    {
        $auth = $this->bAuthService->getAuth();
        $data = json_decode($request->getContent(), true);

        try {
            $result = $auth->login(
                $data['email'] ?? '',
                $data['password'] ?? ''
            );

            return $this->json([
                'success' => true,
                'user' => $result['user'],
                'token' => $result['token'],
            ]);
        } catch (\Bmvc\BAuth\Exceptions\AuthenticationException $e) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid credentials',
            ], 401);
        }
    }

    /**
     * @Route("/logout", name="logout", methods={"POST"})
     */
    public function logout(): JsonResponse
    {
        $auth = $this->bAuthService->getAuth();
        $auth->logout();

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/profile", name="profile", methods={"GET"})
     */
    public function profile(): JsonResponse
    {
        $auth = $this->bAuthService->getAuth();

        if (!$auth->isAuthenticated()) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        return $this->json(['user' => $auth->user()]);
    }

    /**
     * @Route("/refresh", name="refresh", methods={"POST"})
     */
    public function refreshToken(): JsonResponse
    {
        $auth = $this->bAuthService->getAuth();

        try {
            $newToken = $auth->refreshToken();

            return $this->json([
                'success' => true,
                'token' => $newToken,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 401);
        }
    }
}
```

## Middleware/Guard d'authentification

### Créer un Guard personnalisé

```bash
mkdir -p src/Security
touch src/Security/BAuthGuard.php
```

```php
<?php

namespace App\Security;

use App\Service\BAuthService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class BAuthGuard extends AbstractAuthenticator
{
    public function __construct(private BAuthService $bAuthService) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $auth = $this->bAuthService->getAuth();
        $tokenProvider = $auth->getTokenProvider();
        $token = $tokenProvider->extractFromRequest();

        if (!$token) {
            throw new AuthenticationException('No token found');
        }

        try {
            $payload = $auth->verifyToken($token);
            $userId = $payload['user_id'] ?? null;

            if (!$userId) {
                throw new AuthenticationException('Invalid token');
            }

            return new Passport(
                new UserBadge((string) $userId, function() use ($userId) {
                    // Retourner l'utilisateur depuis la base de données
                    $authProvider = $this->bAuthService->getAuth()->getAuthProvider();
                    return $authProvider->getUserById($userId);
                })
            );
        } catch (\Exception $e) {
            throw new AuthenticationException($e->getMessage());
        }
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => 'Authentication failed: ' . $exception->getMessage()
        ], Response::HTTP_UNAUTHORIZED);
    }
}
```

### Configurer le Guard dans `security.yaml`

```yaml
security:
  firewalls:
    api:
      pattern: ^/api
      stateless: true
      custom_authenticator: App\Security\BAuthGuard

  access_control:
    - { path: ^/api/auth/login, roles: PUBLIC_ACCESS }
    - { path: ^/api/auth, roles: IS_AUTHENTICATED_FULLY }
```

## Voter pour l'autorisation

### Créer un Voter

```bash
php bin/console make:voter AuthorizationVoter
```

```php
<?php

namespace App\Security;

use App\Service\BAuthService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AuthorizationVoter extends Voter
{
    public function __construct(private BAuthService $bAuthService) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return str_starts_with($attribute, 'AUTH_');
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $auth = $this->bAuthService->getAuth();

        if (!$auth->isAuthenticated()) {
            return false;
        }

        $permission = str_replace('AUTH_', '', $attribute);

        return $auth->can($permission);
    }
}
```

### Utiliser le Voter

```php
<?php

public function deleteUser($id): JsonResponse
{
    $this->denyAccessUnlessGranted('AUTH_delete_users');

    // Supprimer l'utilisateur
}
```

## Événements

### Dispatcher d'événements

```php
<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class UserLoggedInEvent extends Event
{
    public const NAME = 'user.logged_in';

    public function __construct(
        public array $user,
        public string $token
    ) {}
}

class UserLoggedOutEvent extends Event
{
    public const NAME = 'user.logged_out';

    public function __construct(
        public int $userId
    ) {}
}
```

### Enregistrer les listeners

```yaml
# config/services.yaml
services:
  App\EventListener\AuthListener:
    tags:
      - { name: kernel.event_listener, event: user.logged_in }
      - { name: kernel.event_listener, event: user.logged_out }
```

```php
<?php

namespace App\EventListener;

use App\Event\UserLoggedInEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AuthListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            UserLoggedInEvent::NAME => 'onUserLoggedIn',
        ];
    }

    public function onUserLoggedIn(UserLoggedInEvent $event): void
    {
        // Loguer la connexion, mettre à jour last_login, etc.
    }
}
```

## Tests

### Test unitaire avec PHPUnit

```php
<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    public function testLogin(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertJsonStructure([
            'success',
            'user',
            'token',
        ]);
    }

    public function testLoginInvalidCredentials(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testProtectedRoute(): void
    {
        $client = static::createClient();

        // Sem token
        $client->request('GET', '/api/profile');
        $this->assertResponseStatusCodeSame(401);
    }
}
```

## Configuration avancée

### Extensibilité avec DI

```yaml
# config/services.yaml
parameters:
  auth.jwt_secret: "%env(AUTH_JWT_SECRET)%"
  auth.jwt_expires_in: 3600

services:
  BAuth\Config:
    arguments:
      - jwt:
          secret: "%auth.jwt_secret%"
          expiresIn: "%auth.jwt_expires_in%"

  BAuth\Auth:
    arguments:
      - '@BAuth\Config'

  App\Security\CustomAuthProvider:
    arguments:
      - "@doctrine.orm.entity_manager"

  App\Service\BAuthService:
    arguments:
      - '@BAuth\Auth'
      - '@App\Security\CustomAuthProvider'
```

## Intégration avec Doctrine

### Repository personnalisé

```php
<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByEmailOrUsername(string $identifier): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :identifier')
            ->orWhere('u.username = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
```

Utilisez-le dans le provider :

```php
<?php

class CustomAuthProvider extends \Bmvc\BAuth\Providers\BaseAuthProvider
{
    public function __construct(
        $config,
        private EntityManagerInterface $em,
        private UserRepository $userRepository
    ) {
        parent::__construct($config);
    }

    public function getUserByIdentifier(string $identifier): ?array
    {
        $user = $this->userRepository->findByEmailOrUsername($identifier);
        return $user ? [...] : null;
    }
}
```

## Ressources supplémentaires

- [Guide d'utilisation complet](USAGE.md)
- [Référence API](API.md)
- [Exemples](../examples/)
- [Documentation Symfony Security](https://symfony.com/doc/current/security.html)
