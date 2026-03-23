-- SQL to verify and fix the admin user
-- Run this in phpMyAdmin

-- 1. Check if user exists
SELECT id, email, roles, status, first_name, last_name FROM `user` WHERE email = 'admin@teachme.com';

-- 2. If user exists but password is wrong, update it with this hash:
-- Password: admin123
UPDATE `user` 
SET 
    password = '$2y$13$JsBUlXGhTD6qYc2ROfG8buKU8IYcmJXerWl6ghSh7KtgdpFILCmIO',
    roles = '["ROLE_ADMIN"]',
    status = 'active',
    updated_at = NOW()
WHERE email = 'admin@teachme.com';

-- 3. If user doesn't exist, create it:
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

-- 4. Verify the user after creation/update:
SELECT id, email, roles, status, first_name, last_name FROM `user` WHERE email = 'admin@teachme.com';






