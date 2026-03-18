-- Admin Login Setup Script for Equiwork Demo
-- Run this script in your database administration tool (like phpMyAdmin)
-- to create a default admin account.

-- Assuming the `users` table is already created from schema.sql.
-- If you need to create the database from scratch, use schema.sql first.

-- Insert a default demo admin account into the `users` table
-- Email: admin@equiwork.com
-- Password: admin123
INSERT INTO users (username, email, password_hash, role_type) 
VALUES ('admin', 'admin@equiwork.com', '$2y$10$8Z9dAuDebmqrcqZr/lN2zOG33szxnzkYhvv.pddv9Ov0DmTBNzoWm', 'Admin')
ON DUPLICATE KEY UPDATE role_type='Admin';
