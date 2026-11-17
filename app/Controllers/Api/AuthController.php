<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\UserRepository;
use App\Services\JwtService;

final class AuthController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly JwtService $jwt
    ) {
    }

    public function login(): void
    {
        $input = $this->input();
        $user = $this->users->findByEmail(trim((string) ($input['email'] ?? '')));

        if ($user === null || ! password_verify(
            (string) ($input['password'] ?? ''),
            $user['password']
        )) {
            $this->json(['message' => 'Invalid email or password.'], 401);
            return;
        }

        $this->json([
            'token' => $this->jwt->issue($user),
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]);
    }

    private function input(): array
    {
        $decoded = json_decode(file_get_contents('php://input'), true);
        return is_array($decoded) ? $decoded : $_POST;
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_THROW_ON_ERROR);
    }
}
