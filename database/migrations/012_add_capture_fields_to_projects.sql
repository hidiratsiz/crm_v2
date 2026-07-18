ALTER TABLE projects
    ADD COLUMN notes TEXT NULL AFTER status,
    ADD COLUMN raw_input TEXT NULL AFTER notes,
    ADD COLUMN ai_summary VARCHAR(255) NULL AFTER raw_input;
