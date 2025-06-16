<?php

declare(strict_types=1);

namespace App\Repository; // Note the namespace `App\App\Repository`

use PDO;
use App\Model\User;

class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function find(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT id, name, email, created_at FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return new User(
            id: $data['id'],
            name: $data['name'],
            email: $data['email'],
            createdAt: $data['created_at']
        );
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, email, created_at FROM users');
        $users = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = new User(
                id: $data['id'],
                name: $data['name'],
                email: $data['email'],
                createdAt: $data['created_at']
            );
        }
        return $users;
    }

    public function save(User $user): bool
    {
        if ($user->id) {
            // Update existing user
            $stmt = $this->pdo->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id');
            return $stmt->execute([
                ':name' => $user->name,
                ':email' => $user->email,
                ':id' => $user->id
            ]);
        } else {
            // Insert new user
            $stmt = $this->pdo->prepare('INSERT INTO users (name, email) VALUES (:name, :email)');
            $success = $stmt->execute([
                ':name' => $user->name,
                ':email' => $user->email
            ]);
            if ($success) {
                $user->id = (int)$this->pdo->lastInsertId();
            }
            return $success;
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
}
