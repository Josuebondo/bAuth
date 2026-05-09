<?php

/**
 * Exemple d'utilisation rapide avec PDO
 * 
 * Structure de base de données requise:
 * 
 * CREATE TABLE users (
 *     id INT PRIMARY KEY AUTO_INCREMENT,
 *     email VARCHAR(255) UNIQUE NOT NULL,
 *     username VARCHAR(255) UNIQUE,
 *     password VARCHAR(255) NOT NULL,
 *     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 * );
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use BAuth\Config;
use BAuth\Auth;
use BAuth\Examples\PDO\PDOAuthProvider;

// Connexion à la base de données
$pdo = new PDO(
    'mysql:host=localhost;dbname=bauth_db',
    'root',
    ''
);

// Configuration
$config = new Config([
    'jwt' => [
        'secret' => 'votre-clé-secrète-très-importante',
        'expiresIn' => 3600,
    ]
]);

// Création de l'instance Auth
$auth = new Auth($config);

// Configuration du fournisseur d'authentification
$authProvider = new PDOAuthProvider($config, $pdo, 'users');
$auth->setAuthProvider($authProvider);

// ============================================
// Exemple 1: Créer un nouvel utilisateur
// ============================================

try {
    $newUser = $authProvider->createUser([
        'email' => 'john@example.com',
        'username' => 'john',
        'password' => 'password123'
    ]);
    echo "Utilisateur créé: " . json_encode($newUser) . "\n";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}

// ============================================
// Exemple 2: Connecter un utilisateur
// ============================================

try {
    $result = $auth->login('john@example.com', 'password123');
    echo "Connecté avec succès!\n";
    echo "User: " . $result['user']['email'] . "\n";
    echo "Token: " . substr($result['token'], 0, 20) . "...\n";
} catch (Exception $e) {
    echo "Erreur de connexion: " . $e->getMessage() . "\n";
}

// ============================================
// Exemple 3: Vérifier l'authentification
// ============================================

if ($auth->isAuthenticated()) {
    $user = $auth->user();
    echo "Utilisateur actuel: " . $user['email'] . "\n";
}

// ============================================
// Exemple 4: Utiliser le token JWT
// ============================================

$token = $auth->token();
echo "Token JWT: " . substr($token, 0, 20) . "...\n";

// Renouveler le token
$newToken = $auth->refreshToken();
echo "Nouveau token: " . substr($newToken, 0, 20) . "...\n";

// ============================================
// Exemple 5: Déconnexion
// ============================================

$auth->logout();
echo "Déconnecté\n";
echo "Est authentifié: " . ($auth->isAuthenticated() ? 'Oui' : 'Non') . "\n";

// ============================================
// Exemple 6: Vérifier et décoder un token
// ============================================

try {
    $payload = $auth->verifyToken($token);
    echo "Payload du token: " . json_encode($payload) . "\n";
} catch (Exception $e) {
    echo "Erreur de vérification du token: " . $e->getMessage() . "\n";
}
