<?php

declare (strict_types = 1);

use App\Core\Database;
use App\Core\Redirector;
use App\Core\Router;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Repositories\ProductRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\PurchaseService;

/** @var Router $router */

// Database configuration
$config = require dirname(__DIR__) . '/config/database.php';

$database = new Database($config);
$connection = $database->getConnection();

$productRepository = new ProductRepository($connection);
$transactionRepository = new TransactionRepository($connection);
$purchaseService = new PurchaseService(
    $connection,
    $productRepository,
    $transactionRepository
);
$userRepository = new UserRepository($connection);
$authService = new AuthService($userRepository);
$redirector = new Redirector();
$authMiddleware = new AuthMiddleware($redirector);
$adminMiddleware = new AdminMiddleware($authMiddleware);

require __DIR__ . '/auth.php';
require __DIR__ . '/user.php';
require __DIR__ . '/admin.php';
require __DIR__ . '/api.php';
