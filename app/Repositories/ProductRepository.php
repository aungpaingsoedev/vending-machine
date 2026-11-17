<?php

declare (strict_types = 1);

namespace App\Repositories;

use App\Contracts\ProductRepositoryInterface;
use PDO;

final class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private readonly PDO $connection
    ) {
    }

    public function count(): int
    {
        $statement = $this->connection->query(
            'SELECT COUNT(*) FROM products'
        );

        return (int) $statement->fetchColumn();
    }

    public function findPaginated(
        int $page,
        int $perPage,
        string $sort = 'name',
        string $direction = 'asc'
    ): array {
        $allowedColumns = [
            'name'     => 'name',
            'price'    => 'price',
            'quantity' => 'quantity_available',
        ];

        $orderBy        = $allowedColumns[$sort] ?? 'name';
        $orderDirection = $direction === 'desc' ? 'DESC' : 'ASC';
        $offset         = ($page - 1) * $perPage;

        $statement = $this->connection->prepare(
            "SELECT id, name, price, quantity_available
         FROM products
         ORDER BY {$orderBy} {$orderDirection}
         LIMIT :limit OFFSET :offset"
        );

        $statement->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function create(
        string $name,
        string $price,
        int $quantity
    ): int {
        $statement = $this->connection->prepare(
            'INSERT INTO products (
            name,
            price,
            quantity_available
        ) VALUES (
            :name,
            :price,
            :quantity
        )'
        );

        $statement->execute([
            'name'     => $name,
            'price'    => $price,
            'quantity' => $quantity,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, price, quantity_available
         FROM products
         WHERE id = :id
         LIMIT 1'
        );

        $statement->execute([
            'id' => $id,
        ]);

        $product = $statement->fetch();

        return $product === false ? null : $product;
    }

    public function update(
        int $id,
        string $name,
        string $price,
        int $quantity
    ): void {
        $statement = $this->connection->prepare(
            'UPDATE products
         SET
            name = :name,
            price = :price,
            quantity_available = :quantity
         WHERE id = :id'
        );

        $statement->execute([
            'id'       => $id,
            'name'     => $name,
            'price'    => $price,
            'quantity' => $quantity,
        ]);
    }

    public function delete(int $id): void
    {
        $statement = $this->connection->prepare(
            'DELETE FROM products
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
        ]);
    }

    public function findByIdForUpdate(int $id): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, price, quantity_available
             FROM products
             WHERE id = :id
             LIMIT 1
             FOR UPDATE'
        );

        $statement->execute([
            'id' => $id,
        ]);

        $product = $statement->fetch();

        return $product === false ? null : $product;
    }

    public function decreaseStock(
        int $id,
        int $quantity
    ): bool {
        $statement = $this->connection->prepare(
            'UPDATE products
             SET quantity_available =
                 quantity_available - :decrease_quantity
             WHERE id = :id
               AND quantity_available >= :minimum_quantity'
        );

        $statement->execute([
            'id' => $id,
            'decrease_quantity' => $quantity,
            'minimum_quantity' => $quantity,
        ]);

        return $statement->rowCount() === 1;
    }
}
