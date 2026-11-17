<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\ProductRepositoryInterface;
use App\Contracts\RedirectorInterface;
use App\Contracts\TransactionRepositoryInterface;
use App\Controllers\User\ProductsController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Services\PurchaseService;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AuthenticationFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    public function testGuestIsRedirectedFromProductsToLogin(): void
    {
        $products = $this->createMock(ProductRepositoryInterface::class);
        $products->expects(self::never())->method('count');

        $purchaseService = new PurchaseService(
            $this->createStub(PDO::class),
            $products,
            $this->createStub(TransactionRepositoryInterface::class)
        );

        $redirector = new class implements RedirectorInterface {
            public function redirect(string $path): void
            {
                throw new RuntimeException($path);
            }
        };

        $controller = new ProductsController(
            $products,
            $purchaseService,
            new AuthMiddleware($redirector),
            $redirector
        );

        $router = new Router();
        $router->get('/products', [$controller, 'index']);

        try {
            $router->dispatch('GET', '/products');
            self::fail('The guest request was not redirected.');
        } catch (RuntimeException $exception) {
            self::assertSame('/login', $exception->getMessage());
        }

        self::assertSame(
            'Please login to continue.',
            $_SESSION['login_error']
        );
    }
}
