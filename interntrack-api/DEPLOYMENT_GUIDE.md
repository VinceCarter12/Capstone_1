# 🚀 InternTrack Production Deployment Guide
## Hostinger CyberPanel Deployment

**Domain:** api.interntrack.online  
**Date:** January 6, 2026  
**Laravel Version:** 11.x  
**Flutter Version:** 3.9.2  

---

## 📋 Table of Contents

1. [Prerequisites](#prerequisites)
2. [Part 1: Laravel API Deployment](#part-1-laravel-api-deployment)
3. [Part 2: Database Setup](#part-2-database-setup)
4. [Part 3: Flutter App Configuration](#part-3-flutter-app-configuration)
5. [Part 4: Testing & Verification](#part-4-testing--verification)
6. [Part 5: Post-Deployment Tasks](#part-5-post-deployment-tasks)
7. [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before you begin, ensure you have:

- ✅ Hostinger VPS with CyberPanel installed
- ✅ Domain `api.interntrack.online` pointed to your server IP (both root and www)
- ✅ SSH access to your server
- ✅ FTP/SFTP client (FileZilla recommended)
- ✅ SSL Certificate (Let's Encrypt - free via CyberPanel)

---

## Part 1: Laravel API Deployment

### Step 1.1: Access CyberPanel

1. Open your browser and go to: `https://YOUR_SERVER_IP:8090`
2. Login with your CyberPanel credentials
3. Navigate to **Websites** → **Create Website**

### Step 1.2: Create Website in CyberPanel

1. **Domain Name:** `api.interntrack.online`
2. **Email:** your-email@example.com
3. **Package:** Default
4. **PHP Version:** PHP 8.2
5. **Web Server:** OpenLiteSpeed (default)
6. Click **Create Website**

**Important:** After creation, verify:
- Document root should be: `/home/interntrackapi.online/public_html`
- PHP version is set to 8.2 (Check in **List Websites** → **PHP**)
- Do NOT enable HTTPS redirect yet (wait until SSL is issued)

### Step 1.3: Install SSL Certificate

**IMPORTANT:** Before issuing SSL, ensure:
- ✅ Domain DNS has propagated (wait 24-48 hours after pointing domain)
- ✅ Domain resolves to your server IP (test with `nslookup api.interntrack.online`)
- ✅ Port 80 (HTTP) is accessible (test by visiting `http://api.interntrack.online`) 

**Steps:**

1. Go to **SSL** → **Manage SSL**
2. Select `api.interntrack.online`
3. **SSL Type:** Let's Encrypt (free)
4. **Check both boxes:**
   - ☑️ Issue for: `api.interntrack.online`
   - ☑️ Include WWW: `www.api.interntrack.online`
5. Click **Issue SSL**
6. Wait for SSL to be issued (~30-60 seconds)
7. Verify success message: "SSL issued successfully"

**If SSL fails to issue, see [SSL Troubleshooting](#ssl-troubleshooting-guide) section below.**

### Step 1.4: Set Up SendGrid for Email (Free Tier)

InternTrack uses SendGrid SMTP to send password reset emails and notifications.

**Important Domain Note:**
- Your API is hosted at `api.interntrack.online`.
- Your public website/brand domain is `interntrack.online`.
- For best deliverability, authenticate and send email from `interntrack.online` (even if the API is on `api.interntrack.online`).

**Step 1.4.1: Create SendGrid Account**

1. Go to [SendGrid.com](https://sendgrid.com)
2. Click **Start for Free**
3. Sign up with your email (Free tier: 100 emails/day forever)
4. Verify your email address
5. Complete the onboarding wizard

**Step 1.4.2: Generate SendGrid API Key**

1. Login to [SendGrid Dashboard](https://app.sendgrid.com)
2. Navigate to **Settings** → **API Keys** (left sidebar)
3. Click **Create API Key** (top right)
4. **API Key Name:** `InternTrack Production`
5. **API Key Permissions:** Select **Restricted Access**
6. Expand **Mail Send** and toggle it to **FULL ACCESS**
7. Click **Create & View**
8. **IMPORTANT:** Copy the API key NOW (starts with `SG.`)
   - Example: `SG.xxxxxxxxxxxxxxxxxxx.yyyyyyyyyyyyyyyyyyyyyyyyyyyy`
   - You won't be able to see it again!
9. Save it securely (you'll need it for `.env` file)

**Step 1.4.3: Verify Sender Identity**

SendGrid requires sender verification on free tier:

1. Go to **Settings** → **Sender Authentication**
2. Click **Verify a Single Sender**
3. Fill in:
   - **From Name:** InternTrack
   - **From Email Address:** noreply@interntrack.online
   - **Reply To:** your-email@gmail.com (your personal email)
   - **Company Address:** Your company address
4. Click **Create**
5. Check your email and click the verification link
6. Wait for "Verified" status in SendGrid dashboard

**Note:** Without sender verification, SendGrid will reject all emails.

---

### Step 1.5: Prepare Laravel Project for Upload

On your local machine (Windows):

```powershell
# Navigate to your Laravel project
cd C:\Users\vince\Desktop\Capstone_\interntrack-api

# Install dependencies
composer install --optimize-autoloader --no-dev

# Clear any cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Step 1.6: Create Production .env File

Create a new file `c:\Users\vince\Desktop\Capstone_\interntrack-api\.env.production`:

```env
APP_NAME="InternTrack API"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://api.interntrack.online

# Frontend URL (for password reset emails)
FRONTEND_URL=https://api.interntrack.online

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

LOG_CHANNEL=stack
LOG_LEVEL=error

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=interntrack_db
DB_USERNAME=interntrack_user
DB_PASSWORD=YOUR_STRONG_PASSWORD_HERE

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

# SendGrid Mail Configuration (Free Tier: 100 emails/day)
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.your_sendgrid_api_key_here
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@interntrack.online"
MAIL_FROM_NAME="${APP_NAME}"

# IMPORTANT: Replace 'SG.your_sendgrid_api_key_here' with the API key from Step 1.4.2
# The username MUST be exactly 'apikey' (not your SendGrid username)
```

**Important:** Generate a new APP_KEY:
```powershell
php artisan key:generate --show
```
Copy the output and paste it in `APP_KEY=` above.

**Test SendGrid Configuration (Optional but Recommended):**

Before deploying, test SendGrid locally:

```powershell
# Update your local .env with SendGrid credentials
cp .env.production .env

# Start Laravel
php artisan serve

# In another terminal, send a test email
php artisan tinker
```

In tinker, run:
```php
Mail::raw('Test email from InternTrack', function($msg) {
    $msg->to('your-email@gmail.com')
        ->subject('SendGrid Test');
});
```

Check your inbox. If you receive the email, SendGrid is configured correctly!

---

### Step 1.7: Upload Files to Server

**Method A: Using FTP/SFTP (Recommended)**

1. Open FileZilla or WinSCP
2. Connect to your server:
   - **Host:** YOUR_SERVER_IP
   - **Username:** root (or your SSH user)
   - **Password:** Your SSH password
   - **Port:** 22

3. Navigate to: `/home/api.interntrack.online/public_html`

4. Upload all Laravel files EXCEPT:
   - `.git` folder
   - `node_modules`
   - `.env` (we'll create this separately)
   - `storage/logs/*` (empty it first)

**Method B: Using Git (Alternative)**

SSH into your server and run:

```bash
cd /home/api.interntrack.online
git clone YOUR_GITHUB_REPO_URL public_html
cd public_html
```

### Step 1.8: Set Up Laravel on Server via SSH

SSH into your server:

```bash
ssh root@YOUR_SERVER_IP
```

Then execute:

```bash
# Navigate to website root
cd /home/api.interntrack.online/public_html

# Copy production .env
cp .env.production .env

# Set correct permissions
chown -R api.interntrack.online:api.interntrack.online /home/api.interntrack.online/public_html
chmod -R 755 /home/api.interntrack.online/public_html
chmod -R 775 /home/api.interntrack.online/public_html/storage
chmod -R 775 /home/api.interntrack.online/public_html/bootstrap/cache

# Install composer dependencies (if not done locally)
composer install --optimize-autoloader --no-dev

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Part 2: Database Setup

### Step 2.1: Create Database in CyberPanel

1. Go to **Database** → **Create Database**
2. **Database Name:** `interntrack_db`
3. **Username:** `interntrack_user`
4. **Password:** (Generate a strong password)
5. Click **Create Database**

**Save these credentials!** You'll need them in the .env file.

### Step 2.2: Update .env with Database Credentials

SSH into server and edit .env:

```bash
cd /home/interntrackapi.online/public_html
nano .env
```

Update the DB_ variables with the credentials from Step 2.1.

Save and exit (Ctrl+X, Y, Enter).

### Step 2.3: Run Migrations

```bash
cd /home/interntrackapi.online/public_html

# Run migrations
php artisan migrate --force

# (Optional) Seed database with test data
php artisan db:seed --class=AttendanceSeeder
```

### Step 2.4: Create Storage Link

```bash
php artisan storage:link
```

This creates a symbolic link from `public/storage` to `storage/app/public` for selfie uploads.

---

## Part 3: Flutter App Configuration

### Step 3.1: Update API Base URL

On your local machine, open:
`c:\Users\vince\Desktop\Capstone_\interntrack\lib\services\api_config.dart`

Update to:

```dart
const String kDefaultApiBaseUrl = String.fromEnvironment(
  'INTERNTRACK_API_BASE',
  defaultValue: 'https://api.interntrack.online/api',
);
```

### Step 3.2: Configure Google Maps API Key

**For Android:**

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Create/Select your project
3. Enable "Maps SDK for Android"
4. Create API IN
5. Restrict key to Android apps

Open `c:\Users\vince\Desktop\Capstone_\interntrack\android\app\src\main\AndroidManifest.xml`:

```xml
<manifest ...>
    <application ...>
        <!-- Add this before </application> -->
        <meta-data
            android:name="com.google.android.geo.API_KEY"
            android:value="YOUR_GOOGLE_MAPS_API_KEY_HERE"/>
    </application>
</manifest>
```

### Step 3.3: Build Production APK

```powershell
cd C:\Users\vince\Desktop\Capstone_\interntrack

# Clean previous builds
flutter clean
flutter pub get

# Build release APK with production API URL
flutter build apk --release --dart-define=INTERNTRACK_API_BASE=https://interntrackapi.online/api

# OR Build App Bundle (for Play Store)
flutter build appbundle --release --dart-define=INTERNTRACK_API_BASE=https://interntrackapi.online/api
```

The APK will be in: `build\app\outputs\flutter-apk\app-release.apk`

### Step 3.4: Test on Physical Device

```powershell
# Install on connected device
flutter install

# Or manually install APK
adb install build\app\outputs\flutter-apk\app-release.apk
```

---

## Part 4: Testing & Verification

### Step 4.1: Test API Endpoints

Use Postman or your browser:

**1. Health Check:**
```
GET https://interntrackapi.online/api/health
```

**2. Login Test:**
```
POST https://interntrackapi.online/api/login
Content-Type: application/json

{
  "email": "test@example.com",
  "password": "password123"
}
```

**3. Get Geofence (requires auth):**
```
GET https://interntrackapi.online/api/company/geofence
Authorization: Bearer YOUR_TOKEN_HERE
```

### Step 4.2: Test Flutter App End-to-End

1. Open InternTrack app on device
2. Login with test credentials
3. Navigate to Home screen
4. Click **Clock In** button
5. Grant location & camera permissions
6. Verify map shows geofence
7. Move within geofence (or use mock location)
8. Take selfie
9. Submit clock-in
10. Verify success message
11. Check attendance in database

### Step 4.3: Verify Database

SSH into server:

```bash
mysql -u interntrack_user -p interntrack_db
```

Enter password, then:

```sql
-- Check users
SELECT id, fname, lname, email, role FROM users;

-- Check attendances
SELECT * FROM attendances ORDER BY recorded_at DESC LIMIT 10;

-- Check companies and geofences
SELECT * FROM companies;
SELECT * FROM company_geofences;

-- Exit
exit;
```

---

## Part 5: Post-Deployment Tasks

### Step 5.1: Set Up Automated Backups

In CyberPanel:
1. Go to **Backup** → **Create Backup**
2. Select `interntrackapi.online`
3. Set schedule (daily recommended)
4. Choose backup destination

### Step 5.2: Configure Cron Jobs

SSH into server:

```bash
crontab -e
```

Add Laravel scheduler:

```cron
* * * * * cd /home/interntrackapi.online/public_html && php artisan schedule:run >> /dev/null 2>&1
```

### Step 5.3: Set Up Monitoring

**1. Enable Error Logging:**

Edit `.env`:
```env
LOG_CHANNEL=daily
LOG_LEVEL=error
```

**2. Monitor Storage Usage:**

```bash
cd /home/interntrackapi.online/public_html/storage/app/public
du -sh attendance_selfies/
```

### Step 5.4: Security Hardening

**1. Disable Directory Listing:**

Create/Edit `.htaccess` in `/home/interntrackapi.online/public_html/public`:

```apache
Options -Indexes

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

**2. Protect .env File:**

Already protected by Laravel, but verify:

```bash
chmod 600 /home/interntrackapi.online/public_html/.env
```

**3. Set Up Firewall:**

```bash
# Allow HTTP/HTTPS
ufw allow 80/tcp
ufw allow 443/tcp

# Allow SSH
ufw allow 22/tcp

# Enable firewall
ufw enable
```

---

## Troubleshooting

### Issue 1: 500 Internal Server Error

**Solution:**

```bash
cd /home/interntrackapi.online/public_html
php artisan config:clear
php artisan cache:clear
chmod -R 775 storage bootstrap/cache
chown -R interntrackapi.online:interntrackapi.online storage bootstrap/cache
```

Check logs:
```bash
tail -f /home/interntrackapi.online/public_html/storage/logs/laravel.log
```

### Issue 2: Database Connection Error

**Solution:**

1. Verify database exists:
```bash
mysql -u root -p
SHOW DATABASES;
```

2. Check .env credentials match database

3. Test connection:
```bash
php artisan tinker
DB::connection()->getPdo();
```

### Issue 3: Selfie Upload Fails

**Solution:**

```bash
# Check storage link
ls -la /home/interntrackapi.online/public_html/public/storage

# If missing, recreate
cd /home/interntrackapi.online/public_html
php artisan storage:link

# Set permissions
chmod -R 775 storage/app/public
```

### Issue 4: CORS Errors in Flutter App

**Solution:**

Install CORS package on Laravel:

```bash
composer require fruitcake/laravel-cors
```

Then publish config:
```bash
php artisan vendor:publish --tag="cors"
```

Edit `config/cors.php`:
```php
return [
    'paths' => ['api/*'],
    'allowed_origins' => ['*'],
    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],
];
```

### Issue 5: SSL Certificate Not Working / Cannot Issue SSL for interntrackapi.online

This is a comprehensive guide to fix SSL certificate issues.

---

## 🔒 SSL Troubleshooting Guide

### Step 1: Verify DNS Configuration

**Problem:** Domain must point to your server IP before SSL can be issued.

**Solution:**

**A. Check DNS from your local machine:**

```powershell
# Windows Command Prompt or PowerShell
nslookup interntrackapi.online
nslookup www.interntrackapi.online
```

**Expected Output:**
```
Server:  UnKnown
Address:  192.168.x.x

Non-authoritative answer:
Name:    interntrackapi.online
Address:  YOUR_SERVER_IP_HERE
```

**B. Check DNS propagation online:**

Visit: https://www.whatsmydns.net/#A/interntrackapi.online

- Should show your server IP in green checkmarks worldwide
- If red X's or different IPs appear, DNS hasn't propagated yet
- **Wait 24-48 hours** after changing DNS records

**C. Verify A Records in domain registrar:**

1. Login to your domain registrar (Hostinger, GoDaddy, Namecheap, etc.)
2. Go to DNS Management
3. Verify these records exist:

| Type | Name | Value | TTL |
|------|------|-------|-----|
| A | @ | YOUR_SERVER_IP | 3600 |
| A | www | YOUR_SERVER_IP | 3600 |

4. If missing or incorrect, update them and wait 24-48 hours

---

### Step 2: Check CAA Records

**Problem:** CAA (Certification Authority Authorization) records may block Let's Encrypt.

**Solution:**

**A. Check CAA records from Windows:**

```powershell
# Use nslookup to query CAA records
nslookup -type=CAA interntrackapi.online
```

**B. Check online:**

Visit: https://mxtoolbox.com/SuperTool.aspx?action=caa%3ainterntrackapi.online&run=toolpage

**Interpretation:**

- **No CAA records found:** ✅ Good! Let's Encrypt can issue SSL.
- **CAA records exist:** Check if they allow Let's Encrypt:
  ```
  interntrackapi.online. CAA 0 issue "letsencrypt.org"
  ```
  If you see different CA (like "digicert.com"), you need to add Let's Encrypt.

**C. Fix CAA records (if needed):**

1. Login to domain registrar DNS management
2. Add CAA record:
   - **Type:** CAA
   - **Name:** @
   - **Tag:** issue
   - **Value:** letsencrypt.org
3. Save and wait 1-2 hours for propagation

---

### Step 3: Verify Ports 80 and 443 Are Open

**Problem:** Let's Encrypt needs port 80 to verify domain ownership.

**Solution:**

**A. Test port 80 from your machine:**

```powershell
# Windows PowerShell
Test-NetConnection -ComputerName interntrackapi.online -Port 80
Test-NetConnection -ComputerName interntrackapi.online -Port 443
```

**Expected Output:**
```
TcpTestSucceeded : True
```

**B. Test HTTP access in browser:**

Visit: `http://interntrackapi.online` (note: HTTP, not HTTPS)

- Should show CyberPanel default page or your site
- If "Can't reach this site" or timeout: Port 80 is blocked

**C. Check firewall on server (SSH):**

```bash
# Check if firewall is blocking ports
sudo ufw status

# If ports 80/443 are not listed, add them:
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw reload

# Verify
sudo ufw status
```

**D. Check cloud provider firewall:**

If using Hostinger VPS, AWS, DigitalOcean, etc.:

1. Login to hosting control panel
2. Go to Firewall / Security Groups
3. Ensure inbound rules allow:
   - Port 80 (HTTP) from 0.0.0.0/0
   - Port 443 (HTTPS) from 0.0.0.0/0

---

### Step 4: Check CyberPanel SSL Logs

**Problem:** SSL issuance failed but you don't know why.

**Solution:**

SSH into server and check logs:

```bash
# CyberPanel SSL logs
tail -f /usr/local/lsws/logs/error.log

# Acme (Let's Encrypt) logs
tail -f /root/.acme.sh/acme.sh.log

# Check if certificate files exist
ls -la /etc/letsencrypt/live/interntrackapi.online/
```

**Common errors:**

- **"DNS problem: NXDOMAIN"** → Domain doesn't resolve (Step 1)
- **"CAA record prevents issuance"** → CAA blocks Let's Encrypt (Step 2)
- **"Connection refused on port 80"** → Firewall issue (Step 3)
- **"Rate limit exceeded"** → Too many attempts, wait 1 hour

---

### Step 5: Try Manual SSL Issuance

**Problem:** CyberPanel SSL manager fails.

**Solution A: Use acme.sh directly (on server via SSH):**

```bash
# Install acme.sh if not already installed
curl https://get.acme.sh | sh

# Force reload
~/.acme.sh/acme.sh --upgrade --auto-upgrade

# Issue certificate manually
~/.acme.sh/acme.sh --issue \
  -d interntrackapi.online \
  -d www.interntrackapi.online \
  -w /home/interntrackapi.online/public_html

# If successful, install certificate
~/.acme.sh/acme.sh --installcert \
  -d interntrackapi.online \
  --certpath /etc/letsencrypt/live/interntrackapi.online/cert.pem \
  --keypath /etc/letsencrypt/live/interntrackapi.online/privkey.pem \
  --fullchainpath /etc/letsencrypt/live/interntrackapi.online/fullchain.pem

# Restart web server
systemctl restart lsws
```

**Solution B: Try certbot (alternative to acme.sh):**

```bash
# Install certbot
sudo apt update
sudo apt install certbot -y

# Issue certificate
sudo certbot certonly --webroot \
  -w /home/interntrackapi.online/public_html \
  -d interntrackapi.online \
  -d www.interntrackapi.online

# Follow prompts
# Certificate will be saved to /etc/letsencrypt/live/interntrackapi.online/

# Configure in CyberPanel:
# Go to SSL -> Manage SSL -> interntrackapi.online
# Click "Upload Certificate" and provide paths above
```

---

### Step 6: Clear CyberPanel SSL Cache

**Problem:** Previous failed SSL attempts are cached.

**Solution:**

```bash
# SSH into server

# Remove SSL cache
rm -rf /root/.acme.sh/interntrackapi.online
rm -rf /etc/letsencrypt/live/interntrackapi.online
rm -rf /etc/letsencrypt/archive/interntrackapi.online
rm -rf /etc/letsencrypt/renewal/interntrackapi.online.conf

# Restart CyberPanel services
systemctl restart lscpd
systemctl restart lsws

# Wait 5 minutes, then try issuing SSL again in CyberPanel
```

---

### Step 7: Alternative SSL Options (If Let's Encrypt Fails)

#### Option A: Use ZeroSSL via CyberPanel

1. Go to https://zerossl.com and create free account
2. In CyberPanel: **SSL** → **Manage SSL**
3. Select `interntrackapi.online`
4. Change **SSL Type** from "Let's Encrypt" to **"ZeroSSL"**
5. Enter your ZeroSSL email
6. Click **Issue SSL**
7. ZeroSSL also free and often works when Let's Encrypt fails

#### Option B: Use Cloudflare SSL Proxy (Quick Fix)

**Pros:** SSL works immediately, free, no server changes needed
**Cons:** Traffic routes through Cloudflare, not true end-to-end encryption

**Steps:**

1. Go to https://cloudflare.com and create free account
2. Add site: `interntrackapi.online`
3. Cloudflare will give you 2 nameservers (e.g., `john.ns.cloudflare.com`)
4. Go to your domain registrar
5. Change nameservers to Cloudflare's nameservers
6. Wait 24 hours for nameserver propagation
7. In Cloudflare dashboard:
   - Go to **SSL/TLS** → **Overview**
   - Select **"Flexible"** (HTTP to Cloudflare, HTTPS to visitors)
   - Or **"Full"** if you have self-signed cert on server
8. Your site now has HTTPS via Cloudflare!

**Note:** Your Flutter app will connect to `https://interntrackapi.online` but traffic is proxied through Cloudflare.

#### Option C: Upload Custom SSL Certificate

If you have an SSL certificate from another provider (GoDaddy, Namecheap, etc.):

1. Obtain certificate files:
   - `certificate.crt` (your certificate)
   - `private.key` (private key)
   - `ca_bundle.crt` (intermediate certificates)

2. In CyberPanel:
   - Go to **SSL** → **Manage SSL**
   - Select `interntrackapi.online`
   - Scroll to **"Upload Certificate"**
   - Paste contents of each file
   - Click **Save**

3. Restart web server:
   ```bash
   systemctl restart lsws
   ```

---

### Step 8: Verify SSL After Installation

**Test SSL is working:**

**A. Browser test:**

Visit: `https://interntrackapi.online`

- Should show padlock icon in address bar
- Click padlock → Connection is secure
- Certificate issued by: Let's Encrypt / ZeroSSL

**B. Online SSL checker:**

Visit: https://www.ssllabs.com/ssltest/analyze.html?d=interntrackapi.online

- Should get **A** or **A+** rating
- Certificate should be valid
- No errors or warnings

**C. Test API endpoint:**

```powershell
# Windows PowerShell
Invoke-WebRequest -Uri https://interntrackapi.online/api/health
```

Should return 200 OK (or JSON response, not SSL error)

**D. Test in Flutter app:**

1. Update API base URL to `https://interntrackapi.online/api`
2. Rebuild APK
3. Install on device
4. Try login
5. Should work without SSL certificate errors

---

### Quick SSL Troubleshooting Checklist

Print this and check off:

- [ ] Domain resolves to server IP (`nslookup interntrackapi.online`)
- [ ] DNS propagated worldwide (whatsmydns.net shows green)
- [ ] Waited at least 24 hours after changing DNS
- [ ] CAA records allow Let's Encrypt (or no CAA records)
- [ ] Port 80 is open and accessible (`http://interntrackapi.online` works)
- [ ] Port 443 is open in firewall
- [ ] No rate limiting from Let's Encrypt (max 5 attempts/hour)
- [ ] CyberPanel website exists for domain
- [ ] Document root is correct
- [ ] Checked SSL logs for specific errors
- [ ] Tried clearing SSL cache
- [ ] Tried alternative SSL provider (ZeroSSL)

---

### Issue 6: Flutter App Can't Connect to API

**Checklist:**

1. ✅ API URL is `https://api.interntrack.online/api` (with `/api`)
2. ✅ SSL is working (test in browser)
3. ✅ Device has internet connection
4. ✅ API endpoints return 200 status
5. ✅ No firewall blocking requests

---

## 📞 Support & Resources

**CyberPanel Documentation:**  
https://docs.cyberpanel.net/

**Laravel Deployment Guide:**  
https://laravel.com/docs/deployment

**Flutter Production Build:**  
https://docs.flutter.dev/deployment/android

**Hostinger Support:**  
https://www.hostinger.com/tutorials/

---

## ✅ Deployment Checklist

Print this and check off as you go:

- [ ] CyberPanel website created for `interntrackapi.online`
- [ ] Domain DNS points to server IP (root and www)
- [ ] DNS propagation completed (24-48 hours)
- [ ] SendGrid account created and verified
- [ ] SendGrid API key generated and saved
- [ ] SendGrid sender identity verified (noreply@interntrackapi.online)
- [ ] SSL certificate issued and working (Let's Encrypt or ZeroSSL)
- [ ] HTTPS redirect enabled in CyberPanel
- [ ] Laravel files uploaded to server
- [ ] Production `.env` file configured with SendGrid
- [ ] Database created in CyberPanel
- [ ] Migrations run successfully
- [ ] Storage link created
- [ ] File permissions set correctly
- [ ] API endpoints tested with Postman (HTTPS)
- [ ] SendGrid test email sent successfully
- [ ] Flutter app `api_config.dart` updated to `https://interntrackapi.online/api`
- [ ] Google Maps API key added to Android manifest
- [ ] Production APK built with correct API URL
- [ ] End-to-end clock-in flow tested
- [ ] Database records verified
- [ ] Automated backups configured
- [ ] Cron jobs set up
- [ ] Error logging enabled
- [ ] Security hardening completed
- [ ] SSL certificate tested (A rating on ssllabs.com)
- [ ] Documentation updated with production details

---

## 🎉 Congratulations!

Your InternTrack application is now live at:
- **API:** https://interntrackapi.online
- **API Documentation:** https://interntrackapi.online/api
- **Mobile App:** Installed on devices
- **Email System:** SendGrid (100 emails/day free)

**Next Steps:**
1. Create admin accounts
2. Import student data
3. Set up companies and geofences
4. Train users on the system
5. Monitor error logs daily for first week
6. Monitor SendGrid email delivery (Settings → Activity in SendGrid dashboard)
7. Set up email alerts for failed deliveries
8. Collect feedback and iterate

**Important Monitoring:**
- **SendGrid Dashboard:** https://app.sendgrid.com/statistics
  - Check email delivery rates
  - Monitor bounces and spam reports
  - Free tier: 100 emails/day (3,000/month)
  - Set up alerts at 80% usage

- **SSL Certificate Renewal:**
  - Let's Encrypt auto-renews every 90 days
  - Check renewal logs: `~/.acme.sh/acme.sh.log`
  - Test renewal: `~/.acme.sh/acme.sh --renew -d interntrackapi.online --force`

---

**Created by:** Vince Delostrico  
**Project:** InternTrack - Attendance & Location Tracking System  
**Updated:** January 6, 2026  
**Version:** 2.0 - Production with SendGrid & SSL Troubleshooting  
