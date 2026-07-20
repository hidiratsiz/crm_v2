CREATE TABLE IF NOT EXISTS service_module_fields (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_module_id INT UNSIGNED NOT NULL,
    field_key VARCHAR(100) NOT NULL,
    label VARCHAR(150) NOT NULL,
    field_type ENUM('number', 'checkbox', 'dropdown', 'text') NOT NULL,

    -- Pricing behaviour, meaning depends on field_type:
    --   number     -> 'per_unit' (unit_price x value) or 'tiered' (tiers_json breakpoints)
    --   checkbox   -> 'fixed' (adds fixed_price when checked) or 'none'
    --   dropdown   -> 'dropdown_priced' (each option in options_json carries its own price) or 'none'
    --   text       -> always 'none' (descriptive only, never priced)
    pricing_method ENUM('per_unit', 'tiered', 'fixed', 'dropdown_priced', 'none') NOT NULL DEFAULT 'none',

    unit_price DECIMAL(10,2) NULL,
    fixed_price DECIMAL(10,2) NULL,
    tiers_json JSON NULL,
    options_json JSON NULL,

    is_required TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (service_module_id) REFERENCES service_modules(id) ON DELETE CASCADE,
    INDEX idx_smf_module (service_module_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
