<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Middleware\ApiAuthMiddleware;
use App\Services\JwtService;
use PHPUnit\Framework\TestCase;

final class ApiAuthMiddlewareTest extends TestCase
{
    private JwtService $jwt;

    protected function setUp(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = '';
        $this->jwt = new JwtService(
            'test-jwt-secret-with-more-than-32-characters'
        );
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    public function testMissingBearerTokenIsRejected(): void
    {
        $this->expectException(UnauthorizedException::class);
        (new ApiAuthMiddleware($this->jwt))->authenticate();
    }

    public function testUserTokenCannotPerformAdminAction(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->token('user');

        $this->expectException(ForbiddenException::class);
        (new ApiAuthMiddleware($this->jwt))->requireAdmin();
    }

    public function testAdminTokenPassesAdminCheck(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->token('admin');

        $claims = (new ApiAuthMiddleware($this->jwt))->requireAdmin();

        self::assertSame('admin', $claims['role']);
        self::assertSame(1, $claims['sub']);
    }

    private function token(string $role): string
    {
        return $this->jwt->issue([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.test',
            'role' => $role,
        ]);
    }
}
