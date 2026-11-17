<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Controllers\Admin\ProductsController as AdminProductsController;
use App\Controllers\Admin\TransactionsController as AdminTransactionsController;
use App\Controllers\Auth\AuthController;
use App\Controllers\User\ProductsController as UserProductsController;
use App\Controllers\User\TransactionsController as UserTransactionsController;
use App\Core\Database;
use App\Core\Router;
use App\Exceptions\ForbiddenException;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Repositories\ProductRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\PurchaseService;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\RedirectResponse;
use Tests\Support\TestRedirector;
use Throwable;

final class ApplicationFlowsFeatureTest extends TestCase
{
    private PDO $connection;
    private ProductRepository $products;
    private TransactionRepository $transactions;
    private TestRedirector $redirector;
    private AuthMiddleware $authMiddleware;
    private AdminMiddleware $adminMiddleware;

    protected function setUp(): void
    {
        $_GET = [];
        $_POST = [];
        $_SESSION = [];

        $databaseName = $_ENV['DB_DATABASE'] ?? '';
        if (! str_ends_with($databaseName, '_test')) {
            $this->markTestSkipped('Feature tests require the test database.');
        }

        try {
            $config = require dirname(__DIR__, 2) . '/config/database.php';
            $this->connection = (new Database($config))->getConnection();
        } catch (Throwable $exception) {
            $this->markTestSkipped('Test database is unavailable.');
        }

        $this->createTables();
        $this->clearTables();

        $this->products = new ProductRepository($this->connection);
        $this->transactions = new TransactionRepository($this->connection);
        $this->redirector = new TestRedirector();
        $this->authMiddleware = new AuthMiddleware($this->redirector);
        $this->adminMiddleware = new AdminMiddleware($this->authMiddleware);
    }

    protected function tearDown(): void
    {
        if (isset($this->connection)) {
            $this->clearTables();
        }

        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    public function testAdminLoginRedirectsToAdminDashboard(): void
    {
        $this->insertUser(1, 'Admin', 'admin@example.test', 'admin');
        $_POST = ['email' => 'admin@example.test', 'password' => 'secret'];

        $redirect = $this->dispatchLogin();

        self::assertSame('/admin', $redirect->path);
        self::assertSame('admin', $_SESSION['user']['role']);
    }

    public function testUserLoginRedirectsToProducts(): void
    {
        $this->insertUser(1, 'User One', 'user@example.test', 'user');
        $_POST = ['email' => 'user@example.test', 'password' => 'secret'];

        $redirect = $this->dispatchLogin();

        self::assertSame('/products', $redirect->path);
        self::assertSame('user', $_SESSION['user']['role']);
    }

    public function testAdminCanCreateUpdateAndDeleteProduct(): void
    {
        $this->loginSession(1, 'Admin', 'admin');
        $controller = $this->adminProductsController();
        $router = new Router();
        $router->post('/admin/products', [$controller, 'store']);
        $router->post('/admin/products/{id}', [$controller, 'update']);
        $router->post('/admin/products/{id}/delete', [$controller, 'destroy']);

        $_POST = ['name' => 'Water', 'price' => '1.500', 'quantity' => '10'];
        self::assertSame('/admin/products', $this->redirectFrom(
            fn() => $router->dispatch('POST', '/admin/products')
        )->path);

        $productId = (int) $this->connection->lastInsertId();
        $_POST = ['name' => 'Cold Water', 'price' => '2.000', 'quantity' => '8'];
        self::assertSame('/admin/products', $this->redirectFrom(
            fn() => $router->dispatch('POST', "/admin/products/{$productId}")
        )->path);
        self::assertSame('Cold Water', $this->products->findById($productId)['name']);

        self::assertSame('/admin/products', $this->redirectFrom(
            fn() => $router->dispatch('POST', "/admin/products/{$productId}/delete")
        )->path);
        self::assertNull($this->products->findById($productId));
    }

    public function testNormalUserCannotAccessAdminRoute(): void
    {
        $this->loginSession(1, 'User One', 'user');
        $router = new Router();
        $router->get('/admin', [$this->adminProductsController(), 'index']);

        $this->expectException(ForbiddenException::class);
        $router->dispatch('GET', '/admin');
    }

    public function testSuccessfulPurchaseReducesStockAndCreatesTransaction(): void
    {
        $this->insertUser(1, 'User One', 'user@example.test', 'user');
        $this->loginSession(1, 'User One', 'user');
        $productId = $this->products->create('Coke', '3.990', 10);
        $_POST = ['quantity' => '2'];

        $router = $this->purchaseRouter();
        $redirect = $this->redirectFrom(
            fn() => $router->dispatch('POST', "/products/{$productId}/purchase")
        );

        self::assertSame("/products/{$productId}/purchase", $redirect->path);
        self::assertSame(8, (int) $this->products->findById($productId)['quantity_available']);
        self::assertCount(1, $this->transactions->findByUserId(1));
    }

    public function testInsufficientStockDoesNotChangeDatabase(): void
    {
        $this->insertUser(1, 'User One', 'user@example.test', 'user');
        $this->loginSession(1, 'User One', 'user');
        $productId = $this->products->create('Coke', '3.990', 1);
        $_POST = ['quantity' => '2'];

        $this->redirectFrom(
            fn() => $this->purchaseRouter()->dispatch(
                'POST',
                "/products/{$productId}/purchase"
            )
        );

        self::assertSame(1, (int) $this->products->findById($productId)['quantity_available']);
        self::assertSame('Insufficient product stock.', $_SESSION['purchase_error']);
        self::assertCount(0, $this->transactions->findByUserId(1));
    }

    public function testUserTransactionsPageOnlyShowsOwnTransactions(): void
    {
        $this->insertUser(1, 'User One', 'one@example.test', 'user');
        $this->insertUser(2, 'User Two', 'two@example.test', 'user');
        $productOne = $this->products->create('Coke', '3.000', 10);
        $productTwo = $this->products->create('Secret Juice', '4.000', 10);
        $this->transactions->create(1, $productOne, 1, '3.000', '3.000');
        $this->transactions->create(2, $productTwo, 1, '4.000', '4.000');
        $this->loginSession(1, 'User One', 'user');

        $router = new Router();
        $router->get('/transactions', [
            new UserTransactionsController($this->transactions, $this->authMiddleware),
            'index',
        ]);

        ob_start();
        $router->dispatch('GET', '/transactions');
        $output = (string) ob_get_clean();

        self::assertStringContainsString('Coke', $output);
        self::assertStringNotContainsString('Secret Juice', $output);
    }

    public function testAdminTransactionsPageShowsAllTransactions(): void
    {
        $this->insertUser(1, 'User One', 'one@example.test', 'user');
        $this->insertUser(2, 'User Two', 'two@example.test', 'user');
        $productId = $this->products->create('Coke', '3.000', 10);
        $this->transactions->create(1, $productId, 1, '3.000', '3.000');
        $this->transactions->create(2, $productId, 2, '3.000', '6.000');
        $this->loginSession(9, 'Admin', 'admin');

        $router = new Router();
        $router->get('/admin/transactions', [
            new AdminTransactionsController($this->transactions, $this->adminMiddleware),
            'index',
        ]);

        ob_start();
        $router->dispatch('GET', '/admin/transactions');
        $output = (string) ob_get_clean();

        self::assertStringContainsString('User One', $output);
        self::assertStringContainsString('User Two', $output);
    }

    public function testLogoutClearsSessionAndRedirectsToLogin(): void
    {
        $this->loginSession(1, 'User One', 'user');
        $controller = $this->authController();
        $router = new Router();
        $router->post('/logout', [$controller, 'logout']);

        $redirect = $this->redirectFrom(
            fn() => $router->dispatch('POST', '/logout')
        );

        self::assertSame('/login', $redirect->path);
        self::assertSame([], $_SESSION);
    }

    public function testInvalidProductFormReturnsValidationErrors(): void
    {
        $this->loginSession(1, 'Admin', 'admin');
        $_POST = ['name' => '', 'price' => '0', 'quantity' => '-1'];
        $router = new Router();
        $router->post('/admin/products', [$this->adminProductsController(), 'store']);

        $redirect = $this->redirectFrom(
            fn() => $router->dispatch('POST', '/admin/products')
        );

        self::assertSame('/admin/products/create', $redirect->path);
        self::assertArrayHasKey('name', $_SESSION['product_errors']);
        self::assertArrayHasKey('price', $_SESSION['product_errors']);
        self::assertArrayHasKey('quantity', $_SESSION['product_errors']);
    }

    private function authController(): AuthController
    {
        return new AuthController(
            new AuthService(new UserRepository($this->connection)),
            $this->redirector
        );
    }

    private function dispatchLogin(): RedirectResponse
    {
        $router = new Router();
        $router->post('/login', [$this->authController(), 'login']);

        return $this->redirectFrom(fn() => $router->dispatch('POST', '/login'));
    }

    private function adminProductsController(): AdminProductsController
    {
        return new AdminProductsController(
            $this->products,
            $this->adminMiddleware,
            $this->redirector
        );
    }

    private function purchaseRouter(): Router
    {
        $service = new PurchaseService(
            $this->connection,
            $this->products,
            $this->transactions
        );
        $controller = new UserProductsController(
            $this->products,
            $service,
            $this->authMiddleware,
            $this->redirector
        );
        $router = new Router();
        $router->registerController($controller);

        return $router;
    }

    private function redirectFrom(callable $action): RedirectResponse
    {
        try {
            $action();
        } catch (RedirectResponse $redirect) {
            return $redirect;
        }

        self::fail('Expected a redirect response.');
    }

    private function insertUser(
        int $id,
        string $name,
        string $email,
        string $role
    ): void {
        $statement = $this->connection->prepare(
            'INSERT INTO users (id, name, email, password, role)
             VALUES (:id, :name, :email, :password, :role)'
        );
        $statement->execute([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'password' => password_hash('secret', PASSWORD_DEFAULT),
            'role' => $role,
        ]);
    }

    private function loginSession(int $id, string $name, string $role): void
    {
        $_SESSION['user'] = [
            'id' => $id,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '@example.test',
            'role' => $role,
        ];
    }

    private function createTables(): void
    {
        $this->connection->exec(
            "CREATE TABLE IF NOT EXISTS users (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'user',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB"
        );
        $this->connection->exec(
            "CREATE TABLE IF NOT EXISTS products (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                price DECIMAL(10, 3) NOT NULL,
                quantity_available INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB"
        );
        $this->connection->exec(
            "CREATE TABLE IF NOT EXISTS transactions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                product_id BIGINT UNSIGNED NOT NULL,
                quantity INT UNSIGNED NOT NULL,
                unit_price DECIMAL(10, 3) NOT NULL,
                total_price DECIMAL(10, 3) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT transactions_user_fk
                    FOREIGN KEY (user_id) REFERENCES users(id),
                CONSTRAINT transactions_product_fk
                    FOREIGN KEY (product_id) REFERENCES products(id)
            ) ENGINE=InnoDB"
        );
    }

    private function clearTables(): void
    {
        $this->connection->exec('DELETE FROM transactions');
        $this->connection->exec('DELETE FROM products');
        $this->connection->exec('DELETE FROM users');
    }
}
