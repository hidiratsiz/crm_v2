INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE `key` IN ('customers.view','customers.create','customers.edit','dashboard.view');

INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE `key` IN ('customers.view','dashboard.view');

INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE `key` IN ('dashboard.view');

INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions WHERE `key` IN ('customers.view','dashboard.view');
