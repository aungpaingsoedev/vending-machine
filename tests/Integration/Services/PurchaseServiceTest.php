<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Core\Database;
use App\Repositories\ProductRepository;
use App\Repositories\TransactionRepository;
use App\Services\PurchaseService;
use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;

final class PurchaseServiceTest extends TestCase
{
    private PDO $connection;

    protected function setUp(): void
    {
        $databaseName = $_ENV['DB_DATABASE'] ?? '';

        if (! str_ends_with($databaseName, '_test')) {
            $this->markTestSkipped(
                'Create .env.testing and use a database ending in _test.'
            );
        }

        try {
            $config = require dirname(__DIR__, 3) . '/config/database.php';
            $this->connection = (new Database($config))->getConnection();
        } catch (Throwable $exception) {
            $this->markTestSkipped(
                'Test database is unavailable: ' . $exception->getMessage()
            );
        }

        $this->createTables();
        $this->clearTables();
    }

    protected function tearDown(): void
    {
        if (isset($this->connection)) {
            $this->clearTables();
        }
    }

    public function testPurchaseUpdatesStockAndCreatesTransaction(): void
    {
        $this->connection->exec(
            "INSERT INTO users (id, name, email, password, role)
             VALUES (1, 'Test User', 'user@example.test', 'not-used', 'user')"
        );

        $productRepository = new ProductRepository($this->connection);
        $transactionRepository = new TransactionRepository($this->connection);

        $productId = $productRepository->create('Coke', '3.990', 10);

        $service = new PurchaseService(
            $this->connection,
            $productRepository,
            $transactionRepository
        );

        $result = $service->purchase(1, $productId, 2);
        $product = $productRepository->findById($productId);
        $transactions = $transactionRepository->findByUserId(1);

        self::assertNotNull($product);
        self::assertSame(8, (int) $product['quantity_available']);
        self::assertSame('Coke', $result['product']);
        self::assertSame('7.980', $result['total_price']);
        self::assertCount(1, $transactions);
        self::assertSame(2, (int) $transactions[0]['quantity']);
        self::assertSame('7.980', $transactions[0]['total_price']);
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
