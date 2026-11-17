<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\UnauthorizedException;
use App\Services\JwtService;
use PHPUnit\Framework\TestCase;

final class JwtServiceTest extends TestCase
{
    private const SECRET = 'test-jwt-secret-with-more-than-32-characters';

    public function testIssuedTokenCanBeVerified(): void
    {
        $jwt = new JwtService(self::SECRET);
        $token = $jwt->issue([
            'id' => 7,
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'role' => 'admin',
        ]);

        $claims = $jwt->verify($token);

        self::assertSame(7, $claims['sub']);
        self::assertSame('admin', $claims['role']);
        self::assertGreaterThan(time(), $claims['exp']);
    }

    public function testModifiedTokenIsRejected(): void
    {
        $jwt = new JwtService(self::SECRET);
        $token = $jwt->issue([
            'id' => 7,
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'role' => 'admin',
        ]);

        $token[strlen($token) - 1] = $token[strlen($token) - 1] === 'a'
            ? 'b'
            : 'a';

        $this->expectException(UnauthorizedException::class);
        $jwt->verify($token);
    }

    public function testShortSecretIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new JwtService('too-short');
    }
}
