# Password Reset Fix Guide

## Problem
The forgot password functionality is returning a 500 Server Error because:
1. The database connection is not available
2. Password reset tokens need to be stored in the database

## Solutions

### Option 1: For Local Development (Using SQLite)

1. **Copy the local environment file:**
   ```bash
   copy .env.local .env
   ```

2. **Create SQLite database:**
   ```bash
   cd database
   type nul > database.sqlite
   cd ..
   ```

3. **Run migrations:**
   ```bash
   php artisan migrate
   ```

4. **Start the development server:**
   ```bash
   php artisan serve
   ```

### Option 2: For Local Development (Using MySQL)

1. **Install MySQL/MariaDB or XAMPP**

2. **Start MySQL service:**
   - If using XAMPP: Start the MySQL module
   - If using standalone MySQL: Start the MySQL service

3. **Create the database:**
   ```sql
   CREATE DATABASE interntrack;
   ```

4. **Update .env file:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=interntrack
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Run migrations:**
   ```bash
   php artisan migrate
   ```

### Option 3: For Production (Current Setup)

The production `.env` file is already configured. Ensure:

1. **Database server is running** at the configured host (127.0.0.1 or 72.61.115.6)

2. **Database exists** and credentials are correct

3. **Migrations have been run:**
   ```bash
   php artisan migrate
   ```

4. **Mail is configured** properly (SendGrid or other SMTP service)

## Code Changes Made

The `AuthController::forgotPassword()` method has been updated with better error handling:

- Added try-catch blocks to handle database connection errors
- Added specific error messages for database issues
- Added logging for debugging

## Testing the Fix

### Test Database Connection:
```bash
php artisan db:show
```

### Test Forgot Password API:
```bash
POST http://localhost:8000/api/forgot-password
Content-Type: application/json

{
  "email": "test@pup.edu.ph"
}
```

### Expected Success Response:
```json
{
  "message": "Password reset link sent to your email"
}
```

### Check Logs:
If using `MAIL_MAILER=log`, check `storage/logs/laravel.log` for the password reset link.

## Additional Notes

- The `password_reset_tokens` table is created by the default Laravel migration
- Ensure the table exists: `php artisan migrate:status`
- For local testing, using `MAIL_MAILER=log` will write emails to the log file instead of sending them
