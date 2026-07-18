<?php

namespace App\Models;

use App\Core\Database;

class Customer
{
    public static function all(?string $search = null): array
    {
        $db = Database::connection();

        if ($search) {
            $stmt = $db->prepare(
                'SELECT * FROM customers
                 WHERE deleted_at IS NULL
                 AND (name LIKE :s OR phone LIKE :s OR email LIKE :s OR address LIKE :s)
                 ORDER BY created_at DESC'
            );
            $like = '%' . $search . '%';
            $stmt->execute(['s' => $like]);
        } else {
            $stmt = $db->query('SELECT * FROM customers WHERE deleted_at IS NULL ORDER BY created_at DESC');
        }

        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM customers WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Matches by phone number, ignoring formatting differences (spaces,
     * dashes, parentheses, country code prefix). Used by the AI quick-capture
     * feature to recognize returning customers even if the number was
     * dictated/typed in a different format than what's stored.
     */
    public static function findByPhone(string $phone): ?array
    {
        $needle = self::normalizePhone($phone);
        if ($needle === '') {
            return null;
        }

        $db = Database::connection();
        $stmt = $db->query('SELECT * FROM customers WHERE deleted_at IS NULL AND phone IS NOT NULL AND phone != \'\'');
        foreach ($stmt->fetchAll() as $row) {
            if (self::normalizePhone($row['phone']) === $needle) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Fallback match by exact (case-insensitive) name, used only when no
     * phone number is available. Less reliable than phone matching —
     * common names can collide — so callers should treat this as a
     * best-effort match, not a guarantee.
     */
    public static function findByName(string $name): ?array
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM customers WHERE deleted_at IS NULL AND LOWER(name) = LOWER(:name) LIMIT 1');
        $stmt->execute(['name' => $name]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private static function normalizePhone(?string $phone): string
    {
        if (!$phone) {
            return '';
        }
        $digits = preg_replace('/\D/', '', $phone);
        // Compare on the last 10 digits so country-code prefixes (+90, 0, etc.)
        // don't cause a false mismatch.
        return substr($digits, -10);
    }

    public static function create(array $data, ?int $createdBy): int
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO customers (name, phone, email, address, notes, created_by)
             VALUES (:name, :phone, :email, :address, :notes, :created_by)'
        );
        $stmt->execute([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $createdBy,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'UPDATE customers SET name = :name, phone = :phone, email = :email,
             address = :address, notes = :notes WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public static function softDelete(int $id): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE customers SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
