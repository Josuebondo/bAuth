<?php

namespace BAuth\Examples\Middleware;

use BAuth\Auth;
use BAuth\Exceptions\AuthenticationException;

/**
 * Middleware d'authentification générique
 */
class AuthMiddleware
{
    public function __construct(private Auth $auth) {}

    /**
     * Vérifier l'authentification
     */
    public function handle(): void
    {
        if (!$this->auth->isAuthenticated()) {
            throw new AuthenticationException('Authentification requise', 401);
        }
    }
}

/**
 * Middleware d'autorisation pour les rôles
 */
class RoleMiddleware
{
    private array $requiredRoles = [];

    public function __construct(private Auth $auth, string ...$roles)
    {
        $this->requiredRoles = $roles;
    }

    /**
     * Vérifier les rôles
     */
    public function handle(): void
    {
        if (!$this->auth->isAuthenticated()) {
            throw new AuthenticationException('Authentification requise', 401);
        }

        $hasRole = false;
        foreach ($this->requiredRoles as $role) {
            if ($this->auth->hasRole($role)) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            throw new AuthenticationException('Accès refusé', 403);
        }
    }
}

/**
 * Middleware d'autorisation pour les permissions
 */
class PermissionMiddleware
{
    private array $requiredPermissions = [];

    public function __construct(private Auth $auth, string ...$permissions)
    {
        $this->requiredPermissions = $permissions;
    }

    /**
     * Vérifier les permissions
     */
    public function handle(): void
    {
        if (!$this->auth->isAuthenticated()) {
            throw new AuthenticationException('Authentification requise', 401);
        }

        $hasPermission = false;
        foreach ($this->requiredPermissions as $permission) {
            if ($this->auth->can($permission)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            throw new AuthenticationException('Accès refusé', 403);
        }
    }
}

/**
 * Middleware pour vérifier les tokens JWT
 */
class JWTMiddleware
{
    public function __construct(private Auth $auth) {}

    /**
     * Vérifier le token JWT depuis le header Authorization
     */
    public function handle(): void
    {
        $tokenProvider = $this->auth->getTokenProvider();
        $token = $tokenProvider->extractFromRequest();

        if (!$token) {
            throw new AuthenticationException('Token non fourni', 401);
        }

        try {
            $payload = $this->auth->verifyToken($token);
        } catch (\Exception $e) {
            throw new AuthenticationException('Token invalide', 401);
        }
    }
}

// ====== EXEMPLE D'UTILISATION AVEC UN ROUTEUR SIMPLE ======

/**
 * Classe de routeur simple avec middleware
 */
class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler, array $middleware = []): self
    {
        return $this->register('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable $handler, array $middleware = []): self
    {
        return $this->register('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable $handler, array $middleware = []): self
    {
        return $this->register('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, callable $handler, array $middleware = []): self
    {
        return $this->register('DELETE', $path, $handler, $middleware);
    }

    private function register(string $method, string $path, callable $handler, array $middleware = []): self
    {
        $this->routes[$method][$path] = [
            'handler' => $handler,
            'middleware' => $middleware
        ];

        return $this;
    }

    public function dispatch(string $method, string $path): void
    {
        if (!isset($this->routes[$method][$path])) {
            http_response_code(404);
            echo json_encode(['error' => 'Route non trouvée']);
            return;
        }

        $route = $this->routes[$method][$path];

        try {
            // Exécuter le middleware
            foreach ($route['middleware'] as $middleware) {
                $middleware->handle();
            }

            // Exécuter le handler
            call_user_func($route['handler']);
        } catch (\Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}

// ====== EXEMPLE D'UTILISATION ======

/*
$router = new Router();

$authMiddleware = new AuthMiddleware($auth);
$adminMiddleware = new RoleMiddleware($auth, 'admin');
$editPostsMiddleware = new PermissionMiddleware($auth, 'edit_posts');

// Routes publiques
$router->post('/api/auth/login', function () {
    echo json_encode(['message' => 'Login']);
});

// Routes protégées
$router->get('/api/user', function () {
    echo json_encode(['user' => 'data']);
}, [$authMiddleware]);

// Routes administrateur
$router->delete('/api/users/{id}', function () {
    echo json_encode(['message' => 'User deleted']);
}, [$adminMiddleware]);

// Dispatcher
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$router->dispatch($method, $path);
*/
