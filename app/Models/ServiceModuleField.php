<?php

namespace App\Models;

use App\Core\Database;

class ServiceModuleField
{
    public static function create(array $data): int
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM service_module_fields WHERE service_module_id = :mid'
        );
        $stmt->execute(['mid' => $data['service_module_id']]);
        $nextOrder = (int) $stmt->fetch()['next_order'];

        $stmt = $db->prepare(
            'INSERT INTO service_module_fields
             (service_module_id, field_key, label, field_type, pricing_method, unit_price, fixed_price, tiers_json, options_json, is_required, sort_order)
             VALUES (:service_module_id, :field_key, :label, :field_type, :pricing_method, :unit_price, :fixed_price, :tiers_json, :options_json, :is_required, :sort_order)'
        );
        $stmt->execute([
            'service_module_id' => $data['service_module_id'],
            'field_key' => $data['field_key'],
            'label' => $data['label'],
            'field_type' => $data['field_type'],
            'pricing_method' => $data['pricing_method'] ?? 'none',
            'unit_price' => $data['unit_price'] ?? null,
            'fixed_price' => $data['fixed_price'] ?? null,
            'tiers_json' => $data['tiers_json'] ?? null,
            'options_json' => $data['options_json'] ?? null,
            'is_required' => !empty($data['is_required']) ? 1 : 0,
            'sort_order' => $nextOrder,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM service_module_fields WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function allForModule(int $serviceModuleId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT * FROM service_module_fields WHERE service_module_id = :mid ORDER BY sort_order ASC'
        );
        $stmt->execute(['mid' => $serviceModuleId]);
        return $stmt->fetchAll();
    }

    public static function delete(int $id): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('DELETE FROM service_module_fields WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
