<?php

declare(strict_types=1);

namespace App\Exceptions;

final class ForbiddenException extends HttpException
{
    public function __construct(string $message = '403 - Access denied.')
    {
        parent::__construct(403, $message);
    }
}
