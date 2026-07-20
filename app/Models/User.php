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

    /**
     * Active users available for job assignment (any role — a small shop
     * owner might do jobs themself too, not just "Employee"-role accounts).
     */
    public static function allActive(): array
    {
        $db = Database::connection();
        $stmt = $db->query(
            'SELECT u.id, u.name, u.email, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.is_active = 1 AND u.deleted_at IS NULL
             ORDER BY u.name'
        );
        return $stmt->fetchAll();
    }

    public static function allRoles(): array
    {
        $db = Database::connection();
        return $db->query('SELECT id, name FROM roles ORDER BY id')->fetchAll();
    }

    /**
     * Loose name match for resolving voice/text commands like "Alex'i ata"
     * to an actual user account. Returns ALL matches (not just one) so the
     * caller can distinguish "not found" from "ambiguous — multiple people
     * named Alex" rather than silently picking one.
     */
    public static function findByNameLike(string $name): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT id, name, email FROM users
             WHERE deleted_at IS NULL AND is_active = 1 AND name LIKE :name
             ORDER BY name'
        );
        $stmt->execute(['name' => '%' . $name . '%']);
        return $stmt->fetchAll();
    }
}
