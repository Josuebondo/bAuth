<?php

namespace Bmvc\BAuth\Providers;

use Bmvc\BAuth\Config;
use Bmvc\BAuth\Contracts\TokenProviderInterface;
use Bmvc\BAuth\Exceptions\InvalidTokenException;
use Firebase\JWT\JWT;

/**
 * Fournisseur de tokens JWT
 * Compatible avec firebase/jwt v5.x et v6.x+
 */
class JWTProvider implements TokenProviderInterface
{
    public function __construct(private Config $config) {}

    /**
     * Générer un token JWT
     */
    public function generate(array $payload, ?int $expiresIn = null): string
    {
        $expiresIn = $expiresIn ?? $this->config->get('jwt.expiresIn', 3600);
        $issuedAt = time();
        $expire = $issuedAt + $expiresIn;

        $payload['iat'] = $issuedAt;
        $payload['exp'] = $expire;

        $secret = $this->config->get('jwt.secret');
        $algorithm = $this->config->get('jwt.algorithm', 'HS256');

        return JWT::encode($payload, $secret, $algorithm);
    }

    /**
     * Vérifier et décoder un token JWT
     */
    public function verify(string $token): ?array
    {
        try {
            $secret = $this->config->get('jwt.secret');
            $algorithm = $this->config->get('jwt.algorithm', 'HS256');

            // Support for both firebase/jwt v5.x and v6.x+
            if (class_exists('Firebase\JWT\Key')) {
                $decoded = JWT::decode($token, new \Firebase\JWT\Key($secret, $algorithm));
            } else {
                $decoded = JWT::decode($token, $secret, [$algorithm]);
            }

            return (array) $decoded;
        } catch (\Exception $e) {
            throw new InvalidTokenException($e->getMessage());
        }
    }

    /**
     * Extraire le token d'une requête (Bearer token)
     */
    public function extractFromRequest(): ?string
    {
        $headers = $this->getAuthorizationHeader();

        if ($headers && preg_match('/Bearer\s+(\S+)/', $headers, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Renouveler un token
     */
    public function refresh(string $token): string
    {
        $payload = $this->verify($token);
        unset($payload['exp'], $payload['iat']);

        return $this->generate($payload);
    }

    /**
     * Récupérer le header Authorization
     */
    private function getAuthorizationHeader(): ?string
    {
        $headers = [];

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }

        return $headers['Authorization'] ?? null;
    }
}
