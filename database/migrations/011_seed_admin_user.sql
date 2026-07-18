-- Default admin user
-- E-posta: admin@jobpro.local | Sifre: Admin123!
-- ONEMLI: Ilk giristen hemen sonra bu sifreyi degistirin.
INSERT INTO users (role_id, name, email, password_hash, is_active)
VALUES (1, 'System Admin', 'admin@jobpro.local', '$2y$10$2TJVS5DFbVhzOUpqkD8QPugeolrbp2H3xFkO1d7UpLIoUkfGZdYkW', 1);
