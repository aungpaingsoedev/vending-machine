<?php

declare (strict_types = 1);

namespace App\Middleware;

use App\Exceptions\ForbiddenException;

final class AdminMiddleware
{
    public function __construct(
        private readonly AuthMiddleware $authMiddleware
    ) {
    }

    public function handle(): void
    {
        $this->authMiddleware->handle();

        if (($_SESSION['user']['role'] ?? null) === 'admin') {
            return;
        }

        throw new ForbiddenException('403 - Admin access required.');
    }
}
