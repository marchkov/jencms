UPDATE users SET role = 'administrator' WHERE role IS NULL OR role = '';
CREATE INDEX idx_users_role ON users(role);
