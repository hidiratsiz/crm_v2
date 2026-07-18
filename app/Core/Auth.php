<?php

namespace App\Core;

use App\Models\User;

class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);

        if (!$user || !$user['is_active']) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Regenerate session ID on privilege change (prevents session fixation)
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['role_name'] = $user['role_name'];

        User::touchLastLogin($user['id']);

        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function roleName(): ?string
    {
        return $_SESSION['role_name'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::roleName() === 'Admin';
    }

    /**
     * Checks a permission key (e.g. 'customers.edit') against the DB
     * for the currently logged-in user's role.
     */
    public static function can(string $permissionKey): bool
    {
        if (!self::check()) {
            return false;
        }
        if (self::isAdmin()) {
            return true; // Admin bypasses granular checks
        }

        static $cache = [];
        $roleId = $_SESSION['role_id'];

        if (!isset($cache[$roleId])) {
            $db = Database::connection();
            $stmt = $db->prepare(
                'SELECT p.`key` FROM permissions p
                 JOIN role_permissions rp ON rp.permission_id = p.id
                 WHERE rp.role_id = :role_id'
            );
            $stmt->execute(['role_id' => $roleId]);
            $cache[$roleId] = array_column($stmt->fetchAll(), 'key');
        }

        return in_array($permissionKey, $cache[$roleId], true);
    }
}
