<?php

namespace Bmvc\BAuth\Contracts;

/**
 * Interface pour la gestion des tokens JWT
 */
interface TokenProviderInterface
{
    /**
     * Générer un token
     */
    public function generate(array $payload, ?int $expiresIn = null): string;

    /**
     * Vérifier et décoder un token
     */
    public function verify(string $token): ?array;

    /**
     * Extraire le token d'une requête
     */
    public function extractFromRequest(): ?string;

    /**
     * Renouveler un token
     */
    public function refresh(string $token): string;
}
