ALTER TABLE jobs
    ADD COLUMN start_time TIME NULL AFTER start_date,
    ADD COLUMN duration_hours DECIMAL(5,2) NULL AFTER start_time;
