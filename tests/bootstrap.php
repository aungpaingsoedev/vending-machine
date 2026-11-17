<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$projectRoot = dirname(__DIR__);

if (is_file($projectRoot . '/.env.testing')) {
    Dotenv\Dotenv::createMutable($projectRoot)->safeLoad();

    Dotenv\Dotenv::createMutable(
        $projectRoot,
        '.env.testing'
    )->safeLoad();
}
