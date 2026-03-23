# Database Configuration Fix

## The Issue
Your project is configured for PostgreSQL, but you're using MySQL. You need to update your `.env` file.

## Solution

1. **Open your `.env` file** in the project root

2. **Find the DATABASE_URL line** and change it from PostgreSQL to MySQL:

### Current (PostgreSQL - WRONG):
```
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"
```

### Change to (MySQL - CORRECT):
```
DATABASE_URL="mysql://root:your_password@127.0.0.1:3306/lsm_db?serverVersion=8.0&charset=utf8mb4"
```

**Replace:**
- `root` with your MySQL username (usually `root`)
- `your_password` with your MySQL password (leave empty if no password: `root:@127.0.0.1`)
- `lsm_db` with your database name (from phpMyAdmin)

### Example if no password:
```
DATABASE_URL="mysql://root:@127.0.0.1:3306/lsm_db?serverVersion=8.0&charset=utf8mb4"
```

3. **Save the file**

4. **Clear cache:**
```bash
php bin/console cache:clear
```

5. **Test the connection:**
```bash
php bin/console doctrine:schema:validate
```

## Alternative: If you want to use PostgreSQL

Install the PostgreSQL PDO driver:
```bash
# For Windows with XAMPP/WAMP, edit php.ini and uncomment:
; extension=pdo_pgsql
; extension=pgsql
```

Then restart your web server.






