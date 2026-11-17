<?php

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;

final class RedirectResponse extends RuntimeException
{
    public function __construct(
        public readonly string $path
    ) {
        parent::__construct("Redirected to {$path}");
    }
}
