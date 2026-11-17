<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\UnauthorizedException;
use JsonException;

final class JwtService
{
    public function __construct(
        private readonly string $secret,
        private readonly int $ttl = 3600
    ) {
        if (strlen($this->secret) < 32) {
            throw new \InvalidArgumentException(
                'JWT_SECRET must contain at least 32 characters.'
            );
        }
    }

    public function issue(array $user): string
    {
        $now = time();
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = [
            'sub' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'iat' => $now,
            'exp' => $now + $this->ttl,
        ];

        $segments = [
            $this->encode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->encode(json_encode($payload, JSON_THROW_ON_ERROR)),
        ];
        $signature = hash_hmac(
            'sha256',
            implode('.', $segments),
            $this->secret,
            true
        );
        $segments[] = $this->encode($signature);

        return implode('.', $segments);
    }

    public function verify(string $token): array
    {
        $segments = explode('.', $token);
        if (count($segments) !== 3) {
            throw new UnauthorizedException('Invalid token.');
        }

        [$header, $payload, $signature] = $segments;
        $expected = $this->encode(hash_hmac(
            'sha256',
            "{$header}.{$payload}",
            $this->secret,
            true
        ));

        if (! hash_equals($expected, $signature)) {
            throw new UnauthorizedException('Invalid token signature.');
        }

        try {
            $claims = json_decode(
                $this->decode($payload),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            throw new UnauthorizedException('Invalid token payload.');
        }

        if (! is_array($claims) || ($claims['exp'] ?? 0) < time()) {
            throw new UnauthorizedException('Token has expired.');
        }

        return $claims;
    }

    private function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function decode(string $value): string
    {
        $padding = strlen($value) % 4;
        if ($padding !== 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if ($decoded === false) {
            throw new UnauthorizedException('Invalid token encoding.');
        }

        return $decoded;
    }
}
