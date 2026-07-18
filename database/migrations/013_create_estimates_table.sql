CREATE TABLE IF NOT EXISTS estimates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    option_number INT UNSIGNED NOT NULL DEFAULT 1,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status ENUM('draft','sent','accepted','rejected') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_estimates_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
