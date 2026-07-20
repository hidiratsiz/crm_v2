ALTER TABLE estimates
    ADD COLUMN service_module_id INT UNSIGNED NULL AFTER project_id,
    ADD CONSTRAINT fk_estimates_service_module
        FOREIGN KEY (service_module_id) REFERENCES service_modules(id) ON DELETE SET NULL;
