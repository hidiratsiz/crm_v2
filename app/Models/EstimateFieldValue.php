<?php

namespace App\Models;

use App\Core\Database;

class EstimateFieldValue
{
    public static function create(int $estimateId, int $fieldId, ?string $value, float $computedPrice): int
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO estimate_field_values (estimate_id, service_module_field_id, value, computed_price)
             VALUES (:estimate_id, :field_id, :value, :price)'
        );
        $stmt->execute([
            'estimate_id' => $estimateId,
            'field_id' => $fieldId,
            'value' => $value,
            'price' => $computedPrice,
        ]);
        return (int) $db->lastInsertId();
    }

    /**
     * Field values for an estimate, joined with their field definitions
     * (label, type) so the estimate view can render them without a second
     * round of lookups.
     */
    public static function allForEstimate(int $estimateId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT efv.*, smf.label, smf.field_type
             FROM estimate_field_values efv
             JOIN service_module_fields smf ON smf.id = efv.service_module_field_id
             WHERE efv.estimate_id = :eid
             ORDER BY smf.sort_order ASC'
        );
        $stmt->execute(['eid' => $estimateId]);
        return $stmt->fetchAll();
    }

    public static function deleteForEstimate(int $estimateId): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('DELETE FROM estimate_field_values WHERE estimate_id = :eid');
        $stmt->execute(['eid' => $estimateId]);
    }
}
