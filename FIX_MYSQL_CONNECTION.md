# Fix MySQL Connection Issue

## The Problem
- Connection is going to `172.18.0.1` (Docker network) instead of `127.0.0.1`
- MySQL is rejecting connection: "Access denied for user 'root'@'172.18.0.1' (using password: NO)"

## Solution Options

### Option 1: Set MySQL Password (Recommended)

1. **Open MySQL command line or phpMyAdmin SQL tab**

2. **Set password for root user:**
   ```sql
   ALTER USER 'root'@'localhost' IDENTIFIED BY 'your_password';
   ALTER USER 'root'@'%' IDENTIFIED BY 'your_password';
   FLUSH PRIVILEGES;
   ```

3. **Update `.env` file line 38:**
   ```env
   DATABASE_URL="mysql://root:your_password@127.0.0.1:3306/lsm_db?serverVersion=8.0&charset=utf8mb4"
   ```

### Option 2: Allow Root Without Password (Less Secure)

1. **Open MySQL command line or phpMyAdmin SQL tab**

2. **Allow root without password:**
   ```sql
   ALTER USER 'root'@'localhost' IDENTIFIED BY '';
   ALTER USER 'root'@'%' IDENTIFIED BY '';
   FLUSH PRIVILEGES;
   ```

3. **Keep `.env` as is:**
   ```env
   DATABASE_URL="mysql://root:@127.0.0.1:3306/lsm_db?serverVersion=8.0&charset=utf8mb4"
   ```

### Option 3: Use Different MySQL User

1. **Create a new MySQL user:**
   ```sql
   CREATE USER 'lsm_user'@'localhost' IDENTIFIED BY 'lsm_password';
   GRANT ALL PRIVILEGES ON lsm_db.* TO 'lsm_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. **Update `.env` file:**
   ```env
   DATABASE_URL="mysql://lsm_user:lsm_password@127.0.0.1:3306/lsm_db?serverVersion=8.0&charset=utf8mb4"
   ```

## Quick Fix (If using XAMPP/WAMP)

If you're using XAMPP or WAMP with default settings:

1. **Try this password first:** `root` (common default)
   ```env
   DATABASE_URL="mysql://root:root@127.0.0.1:3306/lsm_db?serverVersion=8.0&charset=utf8mb4"
   ```

2. **Or try:** `password`
   ```env
   DATABASE_URL="mysql://root:password@127.0.0.1:3306/lsm_db?serverVersion=8.0&charset=utf8mb4"
   ```

## After Fixing

1. **Clear Symfony cache:**
   ```bash
   php bin/console cache:clear
   ```

2. **Test connection:**
   ```bash
   php bin/console doctrine:database:create --if-not-exists
   ```

3. **Run migrations:**
   ```bash
   php bin/console doctrine:migrations:migrate
   ```






