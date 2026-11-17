<?php

declare(strict_types=1);

namespace App\Core;

use App\Contracts\RedirectorInterface;

final class Redirector implements RedirectorInterface
{
    public function redirect(string $path): void
    {
        header("Location: {$path}");
        exit;
    }
}
