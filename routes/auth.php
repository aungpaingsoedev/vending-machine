<?php

declare(strict_types=1);

use App\Controllers\Auth\AuthController;
use App\Core\Router;

/** @var Router $router */
/** @var App\Services\AuthService $authService */

$authController = new AuthController($authService, $redirector);

$router->get('/login', [$authController, 'showLogin']);
$router->post('/login', [$authController, 'login']);
$router->post('/logout', [$authController, 'logout']);
