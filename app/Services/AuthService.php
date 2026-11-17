<?php

declare (strict_types = 1);

namespace App\Services;

use App\Repositories\UserRepository;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    public function login(string $email, string $password): bool
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            return false;
        }

        if (! password_verify($password, $user['password'])) {
            return false;
        }

        if (session_status() === PHP_SESSION_ACTIVE && ! headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['user'] = [
            'id'    => (int) $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ];

        return true;
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE && ! headers_sent()) {
            session_regenerate_id(true);
        }
    }
}
