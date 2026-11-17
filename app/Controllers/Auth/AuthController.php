<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Contracts\RedirectorInterface;
use App\Services\AuthService;

final class AuthController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly RedirectorInterface $redirector
    ) {
    }

    public function showLogin(): void
    {
        if (isset($_SESSION['user'])) {
            $this->redirectToDashboard();
        }

        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        require dirname(__DIR__, 3) . '/views/auth/login.php';
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $_SESSION['login_error'] = 'Email and password are required.';
            $this->redirector->redirect('/login');
            return;
        }

        if (!$this->authService->login($email, $password)) {
            $_SESSION['login_error'] = 'Invalid email or password.';
            $this->redirector->redirect('/login');
            return;
        }

        $this->redirectToDashboard();
    }

    public function logout(): void
    {
        $this->authService->logout();

        $this->redirector->redirect('/login');
    }

    private function redirectToDashboard(): void
    {
        $path = ($_SESSION['user']['role'] ?? null) === 'admin'
            ? '/admin'
            : '/products';

        $this->redirector->redirect($path);
    }
}
