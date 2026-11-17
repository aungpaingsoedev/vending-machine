<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\TransactionRepositoryInterface;
use PDO;

final class TransactionRepository implements TransactionRepositoryInterface
{
    public function __construct(
        private readonly PDO $connection
    ) {
    }

    public function create(
        int $userId,
        int $productId,
        int $quantity,
        string $unitPrice,
        string $totalPrice
    ): int {
        $statement = $this->connection->prepare(
            'INSERT INTO transactions (
                user_id,
                product_id,
                quantity,
                unit_price,
                total_price
            ) VALUES (
                :user_id,
                :product_id,
                :quantity,
                :unit_price,
                :total_price
            )'
        );

        $statement->execute([
            'user_id' => $userId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function findByUserId(int $userId): array
    {
        $statement = $this->connection->prepare(
            'SELECT
                transactions.id,
                products.name AS product_name,
                transactions.quantity,
                transactions.unit_price,
                transactions.total_price,
                transactions.created_at
             FROM transactions
             INNER JOIN products
                ON products.id = transactions.product_id
             WHERE transactions.user_id = :user_id
             ORDER BY transactions.created_at DESC'
        );

        $statement->execute([
            'user_id' => $userId,
        ]);

        return $statement->fetchAll();
    }

    public function findAll(): array
    {
        $statement = $this->connection->query(
            'SELECT
                transactions.id,
                users.name AS user_name,
                users.email AS user_email,
                products.name AS product_name,
                transactions.quantity,
                transactions.unit_price,
                transactions.total_price,
                transactions.created_at
             FROM transactions
             INNER JOIN users
                ON users.id = transactions.user_id
             INNER JOIN products
                ON products.id = transactions.product_id
             ORDER BY transactions.created_at DESC,
                      transactions.id DESC'
        );

        return $statement->fetchAll();
    }
}
