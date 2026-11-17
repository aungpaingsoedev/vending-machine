<?php

declare (strict_types = 1);

namespace Database\Seeders;

use PDO;

final class ProductSeeder
{
    public function __construct(
        private readonly PDO $connection
    ) {
    }

    public function run(): void
    {
        $products = [
            [
                'name'     => 'Coke',
                'price'    => '3.990',
                'quantity' => 10,
            ],
            [
                'name'     => 'Pepsi',
                'price'    => '6.885',
                'quantity' => 10,
            ],
            [
                'name'     => 'Water',
                'price'    => '0.500',
                'quantity' => 10,
            ],
        ];

        $statement = $this->connection->prepare(
            'INSERT INTO products (name, price, quantity_available)
             SELECT :name, :price, :quantity
             WHERE NOT EXISTS (
                 SELECT 1
                 FROM products
                 WHERE name = :existing_name
             )'
        );

        foreach ($products as $product) {
            $statement->execute([
                'name'          => $product['name'],
                'price'         => $product['price'],
                'quantity'      => $product['quantity'],
                'existing_name' => $product['name'],
            ]);
        }
    }
}
