-- Bir proje/lead icin planlanan on gorusme / inceleme / olcum randevulari.
-- Henuz bir teklif (estimate) veya is (job) yokken bile ("musteriyle bugun
-- saat 14:30'da guverteyi incelemeye gidiyoruz" gibi) planlanabilir - Job
-- kaydi ancak bir teklif "Kabul Edildi" olup "Ise Donustur" denince olusur,
-- bu yuzden randevular projects'e bagli, jobs'a degil.
CREATE TABLE IF NOT EXISTS appointments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NULL,
    scheduled_date DATE NOT NULL,
    scheduled_time TIME NULL,
    notes TEXT NULL,
    status ENUM('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_appointments_project (project_id),
    INDEX idx_appointments_date (scheduled_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
