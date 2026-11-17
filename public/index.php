<?php

declare(strict_types=1);

use App\Core\Router;
use App\Exceptions\HttpException;

require_once dirname(__DIR__) . '/bootstrap.php';

$router = new Router();

require dirname(__DIR__) . '/routes/web.php';

try {
    $router->dispatch(
        $_SERVER['REQUEST_METHOD'],
        $_SERVER['REQUEST_URI']
    );
} catch (HttpException $exception) {
    http_response_code($exception->statusCode());
    if (str_starts_with($_SERVER['REQUEST_URI'], '/api/')) {
        header('Content-Type: application/json');
        echo json_encode(['message' => $exception->getMessage()]);
    } else {
        echo htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
    }
} catch (InvalidArgumentException | RuntimeException $exception) {
    if (str_starts_with($_SERVER['REQUEST_URI'], '/api/')) {
        http_response_code(422);
        header('Content-Type: application/json');
        echo json_encode(['message' => $exception->getMessage()]);
    } else {
        throw $exception;
    }
}
