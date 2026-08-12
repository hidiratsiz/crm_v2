ALTER TABLE jobs
    ADD COLUMN end_date DATE NULL AFTER start_date,
    ADD COLUMN end_time TIME NULL AFTER start_time;
