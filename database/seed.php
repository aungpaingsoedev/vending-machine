<?php

declare (strict_types = 1);

use App\Core\Database;
use Database\Seeders\ProductSeeder;
use Database\Seeders\UserSeeder;

require_once dirname(__DIR__) . '/bootstrap.php';

$config = require dirname(__DIR__) . '/config/database.php';

$database = new Database($config);

$productSeeder = new ProductSeeder(
    $database->getConnection()
);

$userSeeder = new UserSeeder(
    $database->getConnection()
);

$productSeeder->run();
$userSeeder->run();

echo "Database seeded successfully.\n";
