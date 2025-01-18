<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class TokenUtils
{
    private JWTTokenManagerInterface $jwtManager;

    public function __construct(JWTTokenManagerInterface $jwtManager)
    {
        $this->jwtManager = $jwtManager;
    }

    /**
     * Extracts the UUID from the JWT token in the Authorization header.
     *
     * @param Request $request
     * @return string|null The UUID if found, null otherwise.
     * @throws \InvalidArgumentException If the token is missing or invalid.
     */
    public function getUuidFromToken(Request $request): ?string
    {
        $authorizationHeader = $request->headers->get('Authorization');

        if (!$authorizationHeader || !str_starts_with($authorizationHeader, 'Bearer ')) {
            throw new \InvalidArgumentException('Missing or invalid Authorization header');
        }

        $token = substr($authorizationHeader, 7); // Remove "Bearer " from the header

        try {
            $decodedToken = $this->jwtManager->parse($token);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid token');
        }

        return $decodedToken['username'] ?? null;
    }
}