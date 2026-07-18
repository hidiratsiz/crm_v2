<?php

namespace App\Models;

use App\Core\Database;

class User
{
    public static function findByEmail(string $email): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT u.*, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = :email AND u.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function touchLastLogin(int $userId): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $userId]);
    }

    public static function all(): array
    {
        $db = Database::connection();
        $stmt = $db->query(
            'SELECT u.id, u.name, u.email, u.is_active, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.deleted_at IS NULL ORDER BY u.name'
        );
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO users (role_id, name, email, password_hash, is_active)
             VALUES (:role_id, :name, :email, :password_hash, 1)'
        );
        $stmt->execute([
            'role_id' => $data['role_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
        ]);
        return (int) $db->lastInsertId();
    }
}
