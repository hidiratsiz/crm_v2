<?php

namespace App\Models;

use App\Core\Database;

class ServiceModule
{
    public static function create(string $name, ?string $description): int
    {
        $db = Database::connection();
        $slug = self::generateUniqueSlug($name);

        $stmt = $db->prepare(
            'INSERT INTO service_modules (name, slug, description, is_active) VALUES (:name, :slug, :description, 1)'
        );
        $stmt->execute(['name' => $name, 'slug' => $slug, 'description' => $description]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, string $name, ?string $description): void
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'UPDATE service_modules SET name = :name, description = :description WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'name' => $name, 'description' => $description]);
    }

    public static function find(int $id): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM service_modules WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function all(): array
    {
        $db = Database::connection();
        return $db->query('SELECT * FROM service_modules WHERE deleted_at IS NULL ORDER BY name')->fetchAll();
    }

    public static function allActive(): array
    {
        $db = Database::connection();
        return $db->query('SELECT * FROM service_modules WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name')->fetchAll();
    }

    public static function setActive(int $id, bool $active): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE service_modules SET is_active = :active WHERE id = :id');
        $stmt->execute(['id' => $id, 'active' => $active ? 1 : 0]);
    }

    public static function softDelete(int $id): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE service_modules SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private static function generateUniqueSlug(string $name): string
    {
        $base = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        if ($base === '') {
            $base = 'servis';
        }

        $db = Database::connection();
        $slug = $base;
        $suffix = 1;
        while (true) {
            $stmt = $db->prepare('SELECT COUNT(*) c FROM service_modules WHERE slug = :slug');
            $stmt->execute(['slug' => $slug]);
            if ((int) $stmt->fetch()['c'] === 0) {
                return $slug;
            }
            $suffix++;
            $slug = $base . '-' . $suffix;
        }
    }
}
