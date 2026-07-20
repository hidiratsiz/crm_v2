INSERT INTO permissions (`key`, description) VALUES
('service_modules.manage', 'Create and edit dynamic service modules and pricing rules');

INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE `key` = 'service_modules.manage';
