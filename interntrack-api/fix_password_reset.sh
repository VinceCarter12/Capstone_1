#!/bin/bash

# Password Reset Fix Script for InternTrack API
# This script will verify and fix common password reset issues

echo "=========================================="
echo "InternTrack API - Password Reset Fix"
echo "=========================================="
echo ""

# Change to the API directory
cd /home/inte_interntrack/api.interntrack.online

echo "1. Checking database connection..."
php artisan db:show 2>&1 | grep -q "MySQL" && echo "✓ Database connected" || echo "✗ Database connection failed"
echo ""

echo "2. Checking if password_reset_tokens table exists..."
php artisan migrate:status | grep password_reset_tokens
if [ $? -eq 0 ]; then
    echo "✓ Table exists"
else
    echo "⚠ Table might be missing. Running migrations..."
    php artisan migrate --force
fi
echo ""

echo "3. Checking mail configuration..."
echo "MAIL_MAILER: $(grep MAIL_MAILER .env | cut -d '=' -f2)"
echo "MAIL_HOST: $(grep MAIL_HOST .env | cut -d '=' -f2)"
echo "MAIL_PORT: $(grep MAIL_PORT .env | cut -d '=' -f2)"
echo "MAIL_FROM_ADDRESS: $(grep MAIL_FROM_ADDRESS .env | cut -d '=' -f2)"
echo ""

echo "4. Clearing application cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✓ Cache cleared"
echo ""

echo "5. Caching fresh configuration..."
php artisan config:cache
echo "✓ Config cached"
echo ""

echo "6. Testing mail configuration..."
echo "Enter your email address to send a test email:"
read test_email
if [ ! -z "$test_email" ]; then
    php artisan mail:test "$test_email"
else
    echo "Skipping mail test (no email provided)"
fi
echo ""

echo "=========================================="
echo "Fix script completed!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. If the test email failed, check your SendGrid API key"
echo "2. Verify the API key at: https://app.sendgrid.com/settings/api_keys"
echo "3. If the key is invalid, generate a new one and update .env"
echo "4. Run this script again to test"
echo ""
