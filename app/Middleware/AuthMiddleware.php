<?php

declare (strict_types = 1);

namespace App\Middleware;

use App\Contracts\RedirectorInterface;

final class AuthMiddleware
{
    public function __construct(
        private readonly RedirectorInterface $redirector
    ) {
    }

    public function handle(): void
    {
        if (isset($_SESSION['user'])) {
            return;
        }

        $_SESSION['login_error'] = 'Please login to continue.';

        $this->redirector->redirect('/login');
    }
}
