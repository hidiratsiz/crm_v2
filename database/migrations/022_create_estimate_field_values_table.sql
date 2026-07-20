CREATE TABLE IF NOT EXISTS estimate_field_values (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    estimate_id INT UNSIGNED NOT NULL,
    service_module_field_id INT UNSIGNED NOT NULL,
    value VARCHAR(255) NULL,
    computed_price DECIMAL(10,2) NOT NULL DEFAULT 0,

    FOREIGN KEY (estimate_id) REFERENCES estimates(id) ON DELETE CASCADE,
    FOREIGN KEY (service_module_field_id) REFERENCES service_module_fields(id) ON DELETE CASCADE,
    INDEX idx_efv_estimate (estimate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
