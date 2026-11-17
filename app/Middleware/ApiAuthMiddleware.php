<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Services\JwtService;

final class ApiAuthMiddleware
{
    public function __construct(private readonly JwtService $jwt)
    {
    }

    public function authenticate(): array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (! preg_match('/^Bearer\s+(\S+)$/i', $header, $matches)) {
            throw new UnauthorizedException('Bearer token is required.');
        }

        return $this->jwt->verify($matches[1]);
    }

    public function requireAdmin(): array
    {
        $user = $this->authenticate();
        if (($user['role'] ?? null) !== 'admin') {
            throw new ForbiddenException('Admin access required.');
        }

        return $user;
    }
}
