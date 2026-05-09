<?php

/**
 * Exemple d'API REST avec BAuth
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use BAuth\Config;
use BAuth\Auth;
use BAuth\Examples\PDO\PDOAuthProvider;

header('Content-Type: application/json');

// Configuration
$config = new Config([
    'jwt' => [
        'secret' => $_ENV['AUTH_JWT_SECRET'] ?? 'test-secret',
        'expiresIn' => 3600,
    ]
]);

$pdo = new PDO(
    'mysql:host=localhost;dbname=bauth_db',
    'root',
    ''
);

$auth = new Auth($config);
$authProvider = new PDOAuthProvider($config, $pdo, 'users');
$auth->setAuthProvider($authProvider);

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

try {
    // ====== AUTHENTIFICATION ======

    if ($path === '/api/auth/register' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validation
        if (!isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            throw new Exception('Email et password requis');
        }

        $user = $authProvider->createUser([
            'email' => $data['email'],
            'username' => $data['username'] ?? $data['email'],
            'password' => $data['password']
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Utilisateur créé',
            'user' => $user
        ]);
        exit;
    }

    if ($path === '/api/auth/login' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            throw new Exception('Email et password requis');
        }

        $result = $auth->login($data['email'], $data['password']);

        echo json_encode([
            'success' => true,
            'user' => $result['user'],
            'token' => $result['token']
        ]);
        exit;
    }

    if ($path === '/api/auth/logout' && $method === 'POST') {
        $auth->logout();

        echo json_encode([
            'success' => true,
            'message' => 'Déconnecté'
        ]);
        exit;
    }

    if ($path === '/api/auth/refresh' && $method === 'POST') {
        if (!$auth->isAuthenticated()) {
            http_response_code(401);
            throw new Exception('Non authentifié');
        }

        $newToken = $auth->refreshToken();

        echo json_encode([
            'success' => true,
            'token' => $newToken
        ]);
        exit;
    }

    // ====== UTILISATEUR ======

    if ($path === '/api/user' && $method === 'GET') {
        if (!$auth->isAuthenticated()) {
            http_response_code(401);
            throw new Exception('Non authentifié');
        }

        $user = $auth->user();

        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
        exit;
    }

    if ($path === '/api/user' && $method === 'PUT') {
        if (!$auth->isAuthenticated()) {
            http_response_code(401);
            throw new Exception('Non authentifié');
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $auth->userId();

        $authProvider->updateUser($userId, $data);
        $updatedUser = $authProvider->getUserById($userId);

        echo json_encode([
            'success' => true,
            'message' => 'Profil mis à jour',
            'user' => $updatedUser
        ]);
        exit;
    }

    if ($path === '/api/user/change-password' && $method === 'POST') {
        if (!$auth->isAuthenticated()) {
            http_response_code(401);
            throw new Exception('Non authentifié');
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['old_password']) || !isset($data['new_password'])) {
            http_response_code(400);
            throw new Exception('Ancien et nouveau mot de passe requis');
        }

        $user = $auth->user();

        // Vérifier l'ancien mot de passe
        if (!$authProvider->validateCredentials($user, $data['old_password'])) {
            http_response_code(400);
            throw new Exception('Ancien mot de passe incorrect');
        }

        $userId = $auth->userId();
        $authProvider->updateUser($userId, [
            'password' => $data['new_password']
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Mot de passe changé'
        ]);
        exit;
    }

    // Si aucune route ne correspond
    http_response_code(404);
    throw new Exception('Route non trouvée');
} catch (Exception $e) {
    if (http_response_code() === 200) {
        http_response_code(400);
    }

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
