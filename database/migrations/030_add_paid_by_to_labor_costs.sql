ALTER TABLE labor_costs
    ADD COLUMN paid_by INT UNSIGNED NULL AFTER note,
    ADD CONSTRAINT fk_labor_costs_paid_by FOREIGN KEY (paid_by) REFERENCES users(id) ON DELETE SET NULL;
