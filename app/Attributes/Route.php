<?php

declare (strict_types = 1);

namespace App\Attributes;

use Attribute;

#[Attribute(
    Attribute::TARGET_METHOD
     | Attribute::IS_REPEATABLE
)]
final readonly class Route
{
    public function __construct(
        public string $path,
        public array $methods = ['GET']
    ) {
    }
}
