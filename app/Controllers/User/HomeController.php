<?php

declare(strict_types=1);

namespace App\Controllers\User;

final class HomeController
{
    public function index(): void
    {
        require dirname(__DIR__, 3) . '/views/user/home.php';
    }
}
