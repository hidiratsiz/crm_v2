<?php

namespace App\Services;

/**
 * Computes the price contribution of each dynamic field in a service
 * module, and the total, given the field definitions and the values the
 * user entered. This is the ONLY place pricing math happens — used both
 * for the live AJAX preview while filling out the form, and again on the
 * server when the estimate is actually saved (never trust a client-sent
 * total; always recompute here).
 */
class PricingEngine
{
    /**
     * @param array $fields Rows from service_module_fields (as assoc arrays)
     * @param array $values field_key => submitted value (string|bool|null)
     * @return array{lines: array<array{field_id:int, field_key:string, label:string, value:mixed, price:float}>, total: float}
     */
    public static function calculate(array $fields, array $values): array
    {
        $lines = [];
        $total = 0.0;

        foreach ($fields as $field) {
            $key = $field['field_key'];
            $rawValue = $values[$key] ?? null;
            $price = self::priceForField($field, $rawValue);

            $lines[] = [
                'field_id' => (int) $field['id'],
                'field_key' => $key,
                'label' => $field['label'],
                'value' => $rawValue,
                'price' => $price,
            ];

            $total += $price;
        }

        return ['lines' => $lines, 'total' => round($total, 2)];
    }

    private static function priceForField(array $field, $rawValue): float
    {
        switch ($field['pricing_method']) {
            case 'per_unit':
                $quantity = self::toFloat($rawValue);
                $unitPrice = self::toFloat($field['unit_price'] ?? 0);
                return $quantity > 0 ? round($quantity * $unitPrice, 2) : 0.0;

            case 'tiered':
                $quantity = self::toFloat($rawValue);
                if ($quantity <= 0) {
                    return 0.0;
                }
                $tiers = self::decodeJson($field['tiers_json'] ?? null);
                foreach ($tiers as $tier) {
                    $min = self::toFloat($tier['min'] ?? 0);
                    $max = array_key_exists('max', $tier) && $tier['max'] !== null && $tier['max'] !== ''
                        ? self::toFloat($tier['max'])
                        : null;
                    if ($quantity >= $min && ($max === null || $quantity <= $max)) {
                        return round(self::toFloat($tier['price'] ?? 0), 2);
                    }
                }
                return 0.0;

            case 'fixed':
                $checked = self::toBool($rawValue);
                return $checked ? round(self::toFloat($field['fixed_price'] ?? 0), 2) : 0.0;

            case 'dropdown_priced':
                if ($rawValue === null || $rawValue === '') {
                    return 0.0;
                }
                $options = self::decodeJson($field['options_json'] ?? null);
                foreach ($options as $option) {
                    if ((string) ($option['value'] ?? '') === (string) $rawValue) {
                        return round(self::toFloat($option['price'] ?? 0), 2);
                    }
                }
                return 0.0;

            case 'none':
            default:
                return 0.0;
        }
    }

    private static function decodeJson(?string $json): array
    {
        if (empty($json)) {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function toFloat($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        $cleaned = preg_replace('/[^0-9.\-]/', '', (string) $value);
        return $cleaned === '' ? 0.0 : (float) $cleaned;
    }

    private static function toBool($value): bool
    {
        return $value === '1' || $value === 1 || $value === true || $value === 'on' || $value === 'true';
    }
}
