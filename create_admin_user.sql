-- SQL to create a new admin user
-- Run this in phpMyAdmin (select your database, then go to SQL tab)

-- Option 1: Insert new admin user (if it doesn't exist)
INSERT INTO `user` (email, roles, password, first_name, last_name, status, created_at)
VALUES (
    'admin@teachme.com',
    '["ROLE_ADMIN"]',
    '$2y$13$JsBUlXGhTD6qYc2ROfG8buKU8IYcmJXerWl6ghSh7KtgdpFILCmIO',
    'Admin',
    'User',
    'active',
    NOW()
);

-- Option 2: Update existing user to admin (if user already exists)
-- Uncomment and run this if the user already exists:
-- UPDATE `user` 
-- SET 
--     roles = '["ROLE_ADMIN"]',
--     status = 'active',
--     password = '$2y$13$JsBUlXGhTD6qYc2ROfG8buKU8IYcmJXerWl6ghSh7KtgdpFILCmIO',
--     updated_at = NOW()
-- WHERE email = 'admin@teachme.com';

-- Login Credentials:
-- Email: admin@teachme.com
-- Password: admin123
-- Role: ROLE_ADMIN
-- Status: active
