<?php

namespace Bmvc\BAuth\Tests;

use Bmvc\BAuth\Config;
use Bmvc\BAuth\Auth;
use Bmvc\BAuth\Adapters\Generic\GenericAuthProvider;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    private Auth $auth;
    private GenericAuthProvider $authProvider;

    protected function setUp(): void
    {
        $config = new Config([
            'jwt' => [
                'secret' => 'test-secret-key',
                'expiresIn' => 3600,
            ]
        ]);

        $this->auth = new Auth($config);
        $this->authProvider = new GenericAuthProvider($config);

        // Mock users
        $users = [
            ['id' => 1, 'email' => 'test@example.com', 'password' => password_hash('password123', PASSWORD_BCRYPT)],
            ['id' => 2, 'email' => 'admin@example.com', 'password' => password_hash('admin123', PASSWORD_BCRYPT)],
        ];

        // Configurer les callbacks
        $this->authProvider
            ->setGetUserByEmailCallback(function ($email) use ($users) {
                foreach ($users as $user) {
                    if ($user['email'] === $email) {
                        return $user;
                    }
                }
                return null;
            })
            ->setGetUserByIdentifierCallback(function ($identifier) use ($users) {
                foreach ($users as $user) {
                    if ($user['email'] === $identifier) {
                        return $user;
                    }
                }
                return null;
            })
            ->setGetUserByIdCallback(function ($id) use ($users) {
                foreach ($users as $user) {
                    if ($user['id'] === $id) {
                        return $user;
                    }
                }
                return null;
            });

        $this->auth->setAuthProvider($this->authProvider);
    }

    public function testLoginSuccessful()
    {
        $result = $this->auth->login('test@example.com', 'password123');

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals('test@example.com', $result['user']['email']);
    }

    public function testIsAuthenticated()
    {
        $this->auth->login('test@example.com', 'password123');
        $this->assertTrue($this->auth->isAuthenticated());
    }

    public function testUserMethod()
    {
        $this->auth->login('test@example.com', 'password123');
        $user = $this->auth->user();

        $this->assertNotNull($user);
        $this->assertEquals('test@example.com', $user['email']);
    }

    public function testLogout()
    {
        $this->auth->login('test@example.com', 'password123');
        $this->assertTrue($this->auth->isAuthenticated());

        $this->auth->logout();
        $this->assertFalse($this->auth->isAuthenticated());
    }

    public function testTokenRefresh()
    {
        $this->auth->login('test@example.com', 'password123');
        $originalToken = $this->auth->token();

        $newToken = $this->auth->refreshToken();

        $this->assertNotEqual($originalToken, $newToken);
        $this->assertIsString($newToken);
    }
}
