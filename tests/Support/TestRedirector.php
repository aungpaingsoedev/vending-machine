<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\RedirectorInterface;

final class TestRedirector implements RedirectorInterface
{
    public function redirect(string $path): void
    {
        throw new RedirectResponse($path);
    }
}
