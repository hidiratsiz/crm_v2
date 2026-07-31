CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    method ENUM('cash','card','bank_transfer','check') NOT NULL DEFAULT 'cash',
    note VARCHAR(255) NULL,
    received_by INT UNSIGNED NULL,
    paid_at DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_payments_job (job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
