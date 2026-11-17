<?php

declare (strict_types = 1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(
        private readonly PDO $connection
    ) {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, email, password, role
             FROM users
             WHERE email = :email
             LIMIT 1'
        );

        $statement->execute([
            'email' => $email,
        ]);

        $user = $statement->fetch();

        return $user === false ? null : $user;
    }
}
