ALTER TABLE job_employees
    ADD COLUMN daily_hours DECIMAL(4,1) NULL AFTER notified_at;
