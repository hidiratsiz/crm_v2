ALTER TABLE payments
    ADD COLUMN paid_at DATE NULL AFTER received_by;
