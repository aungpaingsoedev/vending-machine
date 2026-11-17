<?php

declare (strict_types = 1);

namespace Database\Seeders;

use PDO;

final class UserSeeder
{
    public function __construct(
        private readonly PDO $connection
    ) {
    }

    public function run(): void
    {
        $users = [
            [
                'name'     => 'Admin',
                'email'    => 'admin@admin.com',
                'password' => 'Admin@123',
                'role'     => 'admin',
            ],
            [
                'name'     => 'User',
                'email'    => 'user@user.com',
                'password' => 'User@123',
                'role'     => 'user',
            ],
        ];

        $statement = $this->connection->prepare(
            'INSERT INTO users (name, email, password, role)
             SELECT :name, :email, :password, :role
             WHERE NOT EXISTS (
                 SELECT 1
                 FROM users
                 WHERE email = :existing_email
             )'
        );

        foreach ($users as $user) {
            $statement->execute([
                'name'           => $user['name'],
                'email'          => $user['email'],
                'password'       => password_hash(
                    $user['password'],
                    PASSWORD_DEFAULT
                ),
                'role'           => $user['role'],
                'existing_email' => $user['email'],
            ]);
        }
    }
}
