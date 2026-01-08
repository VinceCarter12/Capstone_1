# 🚀 InternTrack API Subdomain Deployment Guide
## CyberPanel Deployment with api.interntrack.online

**Main Domain:** interntrack.online (Website/Frontend)  
**API Subdomain:** api.interntrack.online (Laravel Backend)  
**Date:** January 6, 2026  
**Laravel Version:** 11.x  

---

## 📋 Table of Contents

1. [Domain Strategy: Why Use a Subdomain?](#domain-strategy-why-use-a-subdomain)
2. [Implementation Plan](#implementation-plan)
3. [Part 1: DNS Configuration](#part-1-dns-configuration)
4. [Part 2: CyberPanel Subdomain Setup](#part-2-cyberpanel-subdomain-setup)
5. [Part 3: SSL Certificate for Subdomain](#part-3-ssl-certificate-for-subdomain)
6. [Part 4: Laravel API Deployment](#part-4-laravel-api-deployment)
7. [Part 5: SendGrid Email Configuration](#part-5-sendgrid-email-configuration)
8. [Part 6: Flutter App Configuration](#part-6-flutter-app-configuration)
9. [SSL Troubleshooting Guide](#ssl-troubleshooting-guide)
10. [Testing & Verification](#testing--verification)

---

## Domain Strategy: Why Use a Subdomain?

### ✅ Recommended: `api.interntrack.online` (Subdomain)

**This is the BEST practice for your backend API.** Here's why:

| Aspect | Subdomain (api.interntrack.online) | Main Domain (interntrack.online) |
|--------|-------------------------------------|-----------------------------------|
| **Separation** | ✅ Clear separation of frontend/backend | ❌ Mixed concerns |
| **Scalability** | ✅ Can move API to different server | ❌ Tied to same server |
| **CORS** | ✅ Proper cross-origin setup | ⚠️ Same-origin complications |
| **SSL** | ✅ Independent certificate | ⚠️ Shared certificate |
| **Industry Standard** | ✅ api.twitter.com, api.github.com | ❌ Less common |
| **Maintenance** | ✅ Update API without affecting website | ❌ Risk affecting website |
| **Load Balancing** | ✅ Easy to implement later | ❌ Complex |

### Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    interntrack.online                        │
│                    (Main Domain)                             │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────────┐    ┌─────────────────────────────┐ │
│  │   Website/Admin     │    │   Flutter Mobile App        │ │
│  │   (PHP/HTML)        │    │   (Dart)                    │ │
│  │                     │    │                             │ │
│  │   interntrack.online│    │   Uses API subdomain        │ │
│  └──────────┬──────────┘    └─────────────┬───────────────┘ │
│             │                             │                  │
│             │         HTTPS Requests      │                  │
│             └──────────────┬──────────────┘                  │
│                            ▼                                 │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │              api.interntrack.online                      │ │
│  │              (API Subdomain)                             │ │
│  │                                                          │ │
│  │   ┌────────────────────────────────────────────────┐    │ │
│  │   │           Laravel 11 Backend                    │    │ │
│  │   │   • Authentication (Sanctum)                   │    │ │
│  │   │   • Attendance API                             │    │ │
│  │   │   • Geofence Management                        │    │ │
│  │   │   • File Uploads (Selfies)                     │    │ │
│  │   │   • SendGrid Email Integration                 │    │ │
│  │   └────────────────────────────────────────────────┘    │ │
│  └─────────────────────────────────────────────────────────┘ │
│                            │                                 │
│                            ▼                                 │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │                   MySQL Database                         │ │
│  │                   interntrack_db                         │ │
│  └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

---

## Implementation Plan

### Phase 1: Infrastructure Setup (Day 1)
| Task | Duration | Status |
|------|----------|--------|
| Configure DNS A record for api.interntrack.online | 30 min | ⬜ |
| Wait for DNS propagation | 1-24 hours | ⬜ |
| Create subdomain in CyberPanel | 15 min | ⬜ |
| Issue SSL certificate for subdomain | 30 min | ⬜ |

### Phase 2: Backend Deployment (Day 1-2)
| Task | Duration | Status |
|------|----------|--------|
| Prepare Laravel project for production | 30 min | ⬜ |
| Upload Laravel files to server | 1 hour | ⬜ |
| Configure production .env file | 15 min | ⬜ |
| Create database and run migrations | 30 min | ⬜ |
| Set file permissions | 15 min | ⬜ |

### Phase 3: Email Configuration (Day 2)
| Task | Duration | Status |
|------|----------|--------|
| Create SendGrid account | 15 min | ⬜ |
| Generate SendGrid API key | 10 min | ⬜ |
| Verify sender identity | 30 min | ⬜ |
| Configure Laravel mail settings | 15 min | ⬜ |
| Test email sending | 15 min | ⬜ |

### Phase 4: Flutter Integration (Day 2-3)
| Task | Duration | Status |
|------|----------|--------|
| Update API base URL in Flutter | 10 min | ⬜ |
| Build production APK | 15 min | ⬜ |
| End-to-end testing | 1 hour | ⬜ |
| Fix any issues found | Variable | ⬜ |

### Phase 5: Production Hardening (Day 3)
| Task | Duration | Status |
|------|----------|--------|
| Enable HTTPS redirect | 10 min | ⬜ |
| Configure automated backups | 30 min | ⬜ |
| Set up cron jobs | 15 min | ⬜ |
| Security hardening | 30 min | ⬜ |
| Documentation update | 30 min | ⬜ |

---

## Part 1: DNS Configuration

### Step 1.1: Add A Record for Subdomain

**Login to your domain registrar** (where you bought interntrack.online):

1. Go to **DNS Management** or **DNS Zone Editor**
2. Add a new **A Record**:

| Type | Host/Name | Value | TTL |
|------|-----------|-------|-----|
| A | api | YOUR_SERVER_IP | 3600 |

**Example:**
```
Type: A
Host: api
Points to: 185.xxx.xxx.xxx (your VPS IP)
TTL: 3600 (or Auto)
```

**Important Notes:**
- Only enter `api` as the host, NOT `api.interntrack.online`
- The registrar automatically appends `.interntrack.online`
- TTL of 3600 = 1 hour cache

### Step 1.2: Verify DNS Propagation

**Wait at least 30 minutes**, then verify:

**Windows PowerShell:**
```powershell
# Check if subdomain resolves
nslookup api.interntrack.online

# Expected output:
# Name:    api.interntrack.online
# Address:  YOUR_SERVER_IP
```

**Online DNS Checker:**
Visit: https://www.whatsmydns.net/#A/api.interntrack.online

- Wait until most locations show green checkmarks
- If some show red X, wait longer (can take up to 48 hours)

### Step 1.3: DNS Records Summary

Your DNS should look like this:

| Type | Host | Value | Purpose |
|------|------|-------|---------|
| A | @ | YOUR_SERVER_IP | Main website |
| A | www | YOUR_SERVER_IP | www subdomain |
| A | api | YOUR_SERVER_IP | **API subdomain** |

---

## Part 2: CyberPanel Subdomain Setup

### Step 2.1: Access CyberPanel

1. Open browser: `https://YOUR_SERVER_IP:8090`
2. Login with admin credentials

### Step 2.2: Create Subdomain as Website

**IMPORTANT:** In CyberPanel, subdomains must be created as **separate websites**, NOT as child domains!

**Method A: Create as New Website (Recommended)**

1. Go to **Websites** → **Create Website**
2. Fill in:
   - **Domain Name:** `api.interntrack.online`
   - **Email:** your-email@example.com
   - **Package:** Default
   - **PHP Version:** PHP 8.2
3. Click **Create Website**

**Verify Creation:**
- Go to **Websites** → **List Websites**
- You should see `api.interntrack.online` listed
- Document root: `/home/api.interntrack.online/public_html`

### Step 2.3: Configure PHP Version

1. Go to **Websites** → **List Websites**
2. Find `api.interntrack.online`
3. Click on **PHP** column
4. Select **PHP 8.2** (required for Laravel 11)
5. Click **Save**

### Step 2.4: Verify Directory Structure

SSH into your server:

```bash
ssh root@YOUR_SERVER_IP

# Check directory exists
ls -la /home/api.interntrack.online/

# Expected structure:
# drwxr-xr-x  public_html
# drwxr-xr-x  logs
```

---

## Part 3: SSL Certificate for Subdomain

### Step 3.1: Pre-SSL Checklist

Before attempting SSL, verify ALL of these:

```bash
# On your server via SSH:

# 1. Check DNS resolves to your server
dig api.interntrack.online +short
# Should return YOUR_SERVER_IP

# 2. Check website exists in CyberPanel
ls -la /home/api.interntrack.online/public_html/
# Should exist

# 3. Check port 80 is accessible
curl -I http://api.interntrack.online
# IMPORTANT: Any HTTP status (200/301/302/403/404) can be OK here.
# What matters is:
#   (1) You get an HTTP response quickly (no timeout/connection refused)
#   (2) The response is coming from YOUR VPS web server.
#
# If you see `Server: LiteSpeed` or `Server: OpenLiteSpeed`, you are likely hitting CyberPanel/OpenLiteSpeed.
# If you see `Server: openresty` (often with `Via: 1.1 google` or other CDN headers), you are probably hitting
# Hostinger/Cloudflare/another proxy instead of your VPS, and SSL issuance will fail until DNS/proxy is fixed.

# 4. Check no CAA blocking
dig api.interntrack.online CAA +short
# Should be empty or include letsencrypt.org
```

### If `curl -I http://api.interntrack.online` returns `Server: openresty` (like Hostinger edge)

This almost always means **`api.interntrack.online` is not pointing to your CyberPanel VPS**.

Run these checks:

**1) Confirm what IP the subdomain resolves to (from the VPS):**

```bash
dig +short api.interntrack.online A
dig +short api.interntrack.online AAAA
```

- The **A record** must equal your **VPS public IP**.
- If an **AAAA record** exists but your VPS doesn’t serve IPv6, remove the AAAA record (it can break SSL issuance).

**2) Confirm from your local Windows PC (uses different DNS cache paths):**

```powershell
nslookup api.interntrack.online
nslookup api.interntrack.online 8.8.8.8
```

**3) Fix DNS at your DNS provider (one of these is usually the root cause):**

- If you manage DNS in **Hostinger hPanel** (domain uses Hostinger nameservers):
   - Add/Update: **A record** `api` → `YOUR_VPS_IP`
   - Remove conflicting records for `api` (extra A records, CNAME, AAAA)
   - Disable any “Website Builder/CDN/Proxy” feature that hijacks subdomains (if enabled)

- If you manage DNS in **Cloudflare**:
   - Create: **A record** `api` → `YOUR_VPS_IP`
   - Set it to **DNS only** (grey cloud) while issuing SSL (prevents proxy validation issues)

After changing DNS, wait for propagation (can be minutes to hours), then re-run:

```bash
curl -I http://api.interntrack.online
```

When the `Server:` header shows LiteSpeed/OpenLiteSpeed (or your expected server), retry issuing SSL in CyberPanel.

### Step 3.2: Issue SSL Certificate

**In CyberPanel:**

1. Go to **SSL** → **Manage SSL**
2. Select **api.interntrack.online** from dropdown
3. **SSL Type:** Let's Encrypt
4. **Check ONLY:**
   - ☑️ Issue for: `api.interntrack.online`
   - ☐ Include WWW: Leave UNCHECKED (subdomains don't have www)
5. Click **Issue SSL**
6. Wait 30-60 seconds
7. Look for: **"SSL issued successfully"**

### Step 3.3: Verify SSL

**In browser:**
Visit: `https://api.interntrack.online`

- Should show padlock icon
- No certificate warnings

**Using PowerShell:**
```powershell
# Test SSL connection
Invoke-WebRequest -Uri https://api.interntrack.online -UseBasicParsing
```

---

## Part 4: Laravel API Deployment

### Step 4.1: Prepare Laravel Project Locally

On your Windows machine:

```powershell
cd C:\Users\vince\Desktop\Capstone_\interntrack-api

# Install production dependencies
composer install --optimize-autoloader --no-dev

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Generate APP_KEY (save this!)
php artisan key:generate --show
```

### Step 4.2: Create Production .env File

Create `C:\Users\vince\Desktop\Capstone_\interntrack-api\.env.production`:

```env
APP_NAME="InternTrack API"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://api.interntrack.online

# Frontend URL for CORS and emails
FRONTEND_URL=https://interntrack.online

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

LOG_CHANNEL=daily
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

# SendGrid Configuration (See Part 5)
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.your_sendgrid_api_key_here
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@interntrack.online"
MAIL_FROM_NAME="${APP_NAME}"

# Sanctum (for API authentication)
SANCTUM_STATEFUL_DOMAINS=interntrack.online,api.interntrack.online
```

### Step 4.3: Upload Files to Server

**Using FileZilla/WinSCP:**

1. Connect to server via SFTP (port 22)
2. Navigate to: `/home/api.interntrack.online/public_html`
3. Delete default files (index.html, etc.)
4. Upload ALL Laravel files EXCEPT:
   - `.git` folder
   - `node_modules` folder
   - `.env` file (create on server)
   - `storage/logs/*` contents

### Step 4.4: Configure Laravel on Server

SSH into server:

```bash
ssh root@YOUR_SERVER_IP

# Navigate to API directory
cd /home/api.interntrack.online/public_html

# Create .env file
nano .env
# Paste your .env.production contents, save (Ctrl+X, Y, Enter)

# Set ownership
chown -R api.interntrack.online:api.interntrack.online /home/api.interntrack.online/public_html

# Set permissions
chmod -R 755 /home/api.interntrack.online/public_html
chmod -R 775 /home/api.interntrack.online/public_html/storage
chmod -R 775 /home/api.interntrack.online/public_html/bootstrap/cache

# Create storage link for selfie uploads
php artisan storage:link

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 4.5: Create Database

**In CyberPanel:**

1. Go to **Databases** → **Create Database**
2. Select website: `api.interntrack.online`
3. **Database Name:** `interntrack_db`
4. **Database User:** `interntrack_user`
5. **Password:** Generate strong password
6. Click **Create Database**

**Save these credentials!**

### Step 4.6: Run Migrations

```bash
cd /home/api.interntrack.online/public_html

# Run migrations
php artisan migrate --force

# (Optional) Seed initial data
php artisan db:seed
```

---

## Part 5: SendGrid Email Configuration

### Step 5.1: Create SendGrid Account

1. Go to https://sendgrid.com
2. Click **Start for Free**
3. Sign up (Free tier: 100 emails/day)
4. Verify your email address
5. Complete onboarding wizard

### Step 5.2: Generate API Key

1. Login to SendGrid Dashboard
2. Go to **Settings** → **API Keys**
3. Click **Create API Key**
4. **Name:** `InternTrack Production`
5. **Permissions:** Restricted Access
   - Expand **Mail Send** → Set to **Full Access**
6. Click **Create & View**
7. **COPY THE KEY NOW!** (You won't see it again)
   - Format: `SG.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`

### Step 5.3: Verify Sender Identity

**Required for free tier!**

1. Go to **Settings** → **Sender Authentication**
2. Click **Verify a Single Sender**
3. Fill in:
   - **From Name:** InternTrack
   - **From Email:** noreply@interntrack.online
   - **Reply To:** your-email@gmail.com
   - **Company Address:** Your address
4. Click **Create**
5. Check your inbox for verification email
6. Click verification link
7. Wait for "Verified" status

### Step 5.4: Update .env with SendGrid

SSH into server and update .env:

```bash
cd /home/api.interntrack.online/public_html
nano .env
```

Update mail section:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.YOUR_ACTUAL_API_KEY_HERE
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@interntrack.online"
MAIL_FROM_NAME="InternTrack"
```

**Important:**
- `MAIL_USERNAME` must be exactly `apikey` (literal text)
- `MAIL_PASSWORD` is your SendGrid API key (starts with `SG.`)

Clear config cache:

```bash
php artisan config:cache
```

### Step 5.5: Test Email Sending

```bash
php artisan tinker
```

In tinker:

```php
Mail::raw('Test email from InternTrack API', function($msg) {
    $msg->to('your-email@gmail.com')
        ->subject('SendGrid Test - Production');
});
```

Check your inbox. If received, SendGrid is working!

---

## Part 6: Flutter App Configuration

### Step 6.1: Update API Base URL

Edit `C:\Users\vince\Desktop\Capstone_\interntrack\lib\services\api_config.dart`:

```dart
// Production API URL (subdomain)
const String kDefaultApiBaseUrl = String.fromEnvironment(
  'INTERNTRACK_API_BASE',
  defaultValue: 'https://api.interntrack.online/api',
);
```

### Step 6.2: Build Production APK

```powershell
cd C:\Users\vince\Desktop\Capstone_\interntrack

# Clean and get dependencies
flutter clean
flutter pub get

# Build release APK with production URL
flutter build apk --release --dart-define=INTERNTRACK_API_BASE=https://api.interntrack.online/api
```

APK location: `build\app\outputs\flutter-apk\app-release.apk`

---

## SSL Troubleshooting Guide

### 🔴 Common SSL Issues for api.interntrack.online

#### Issue: "This website already exists as child domain. [404]" when creating `api.interntrack.online`

This means you previously created `api.interntrack.online` as a **Child Domain** under `interntrack.online`.
That often causes SSL to be issued but not installed to the correct vhost (resulting in a self-signed cert on 443).

**Goal:** Remove the *child domain entry* and recreate `api.interntrack.online` as its own **Website**.
This does **not** remove or break your main site `interntrack.online`.

**Step 1: Locate and delete the Child Domain in CyberPanel**

In CyberPanel, try one of these (menu names vary by version):

- **Websites → List Websites → Manage** (`interntrack.online`) → find **Child Domains** → delete `api.interntrack.online`
- OR **Websites → List Child Domains** → delete `api.interntrack.online`

If CyberPanel asks, choose to delete the child domain/vhost. If you have files there you need, back them up first.

**Step 2 (Optional but recommended): Back up existing child-domain files**

Child domains often live under the main site’s directory. Common paths:

- `/home/interntrack.online/public_html/api`
- `/home/interntrack.online/public_html/api.interntrack.online`

You can quickly search:

```bash
ls -la /home/interntrack.online/public_html/
```

If you see an `api` folder with important files, copy them somewhere safe before deleting the child domain.

**Step 3: Create `api.interntrack.online` as a Website**

- CyberPanel → **Websites → Create Website**
   - Domain: `api.interntrack.online`
   - PHP: 8.2

Now it should appear under **Websites → List Websites** as its own entry.

**Step 4: Re-issue SSL and reload LiteSpeed**

- CyberPanel → **SSL → Manage SSL** → select `api.interntrack.online` → Issue SSL (don’t include WWW)

Then on the server:

```bash
systemctl restart lsws
```

Verify:

```bash
curl -Iv https://api.interntrack.online
```

#### Issue 0: SSL says "issued successfully" but HTTPS still doesn’t work

Sometimes CyberPanel/ACME will successfully **issue** a certificate, but your browser still can’t load `https://api.interntrack.online` (or it loads HTTP only). Use this checklist.

**A) Confirm DNS points to the VPS (again)**

On the VPS:

```bash
dig +short api.interntrack.online A
dig +short api.interntrack.online AAAA
```

- `A` must be your VPS IP.
- If `AAAA` exists and you don’t serve IPv6 on the VPS, remove the AAAA record.

**B) Confirm port 443 is open and LiteSpeed is listening**

```bash
ss -ltnp | grep -E ':443\b'
systemctl status lsws --no-pager
```

- If nothing is listening on 443, restart LiteSpeed:

```bash
systemctl restart lsws
```

**C) Test HTTPS from the VPS (bypasses your local browser cache)**

```bash
curl -Iv https://api.interntrack.online
```

Look for:
- A successful TLS handshake
- `server: LiteSpeed` (or OpenLiteSpeed)

If you see a TLS/cert error, also check which cert is being served:

```bash
openssl s_client -connect api.interntrack.online:443 -servername api.interntrack.online </dev/null 2>/dev/null | openssl x509 -noout -subject -issuer -dates
```

**D) Make sure HTTPS redirect is enabled (optional but common expectation)**

If HTTPS works but you type `http://api.interntrack.online` and it stays on HTTP, enable redirect:

- CyberPanel → Websites → List Websites → Manage (`api.interntrack.online`)
- Enable **Force HTTPS Redirect**

**E) If you use Cloudflare / Proxy mode**

If DNS is proxied (orange cloud), HTTPS behavior depends on Cloudflare SSL mode:
- Set Cloudflare **SSL/TLS mode** to **Full** (or **Full (strict)** after cert is installed correctly)
- While issuing SSL, prefer **DNS only** (grey cloud) to avoid challenge/routing issues

**F) About this log line**

If you see: `Websites matching query does not exist` but also see `SSL successfully issued for api.interntrack.online`, it usually means one attempt targeted a domain not created as a website in CyberPanel, while another attempt succeeded. Confirm `api.interntrack.online` exists under **Websites → List Websites**.

#### Issue 1: "Domain doesn't exist" or "NXDOMAIN"

**Cause:** DNS not configured or not propagated.

**Fix:**

```powershell
# Check DNS from Windows
nslookup api.interntrack.online

# If it says "can't find" or "NXDOMAIN":
# 1. Verify A record exists in domain registrar
# 2. Wait 24-48 hours for propagation
# 3. Try different DNS (8.8.8.8):
nslookup api.interntrack.online 8.8.8.8
```

#### Issue 2: "Website doesn't exist in CyberPanel"

**Cause:** Subdomain not created as separate website.

**Fix:**

1. In CyberPanel → **Websites** → **List Websites**
2. Look for `api.interntrack.online`
3. If not listed, create it:
   - **Websites** → **Create Website**
   - Domain: `api.interntrack.online`
   - Click Create

#### Issue 3: "Connection refused on port 80"

**Cause:** Firewall blocking HTTP.

**Fix:**

```bash
# SSH into server

# Check firewall status
sudo ufw status

# Allow port 80
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw reload

# Test locally
curl -I http://api.interntrack.online
```

#### Issue 4: "Too many requests" or "Rate limited"

**Cause:** Too many failed SSL attempts.

**Fix:**

```bash
# Wait 1 hour, then try again
# Or use ZeroSSL instead of Let's Encrypt

# Clear SSL cache
rm -rf /root/.acme.sh/api.interntrack.online
rm -rf /etc/letsencrypt/live/api.interntrack.online

# Restart services
systemctl restart lscpd
systemctl restart lsws

# Wait 5 minutes, try SSL again
```

#### Issue 5: "CAA record prevents issuance"

**Cause:** CAA DNS record blocking Let's Encrypt.

**Fix:**

```bash
# Check CAA records
dig api.interntrack.online CAA +short

# If shows another CA (not letsencrypt.org):
# Add CAA record in domain registrar:
# Type: CAA
# Host: api (or @)
# Value: 0 issue "letsencrypt.org"
```

#### Issue 6: SSL Works for Main Domain but Not Subdomain

**Cause:** Wildcard certificate needed or separate certificate required.

**Fix:**

Option A: Issue separate certificate for subdomain (recommended):
1. In CyberPanel → SSL → Manage SSL
2. Select `api.interntrack.online` specifically
3. Issue SSL

Option B: Use wildcard certificate:
```bash
# SSH into server
~/.acme.sh/acme.sh --issue \
  -d interntrack.online \
  -d '*.interntrack.online' \
  --dns dns_cf \
  --dnssleep 120
```

### 🛠️ Manual SSL Issuance (If CyberPanel Fails)

```bash
# SSH into server

# Method 1: Using acme.sh
~/.acme.sh/acme.sh --issue \
  -d api.interntrack.online \
  -w /home/api.interntrack.online/public_html

# Method 2: Using certbot
sudo apt install certbot -y
sudo certbot certonly --webroot \
  -w /home/api.interntrack.online/public_html \
  -d api.interntrack.online

# After certificate is issued, configure in CyberPanel:
# SSL → Manage SSL → api.interntrack.online → Upload Certificate
```

### 🔧 Quick SSL Diagnostic Script

Create and run this on your server:

```bash
#!/bin/bash
echo "=== SSL Diagnostic for api.interntrack.online ==="

echo -e "\n1. DNS Resolution:"
dig api.interntrack.online +short

echo -e "\n2. HTTP Port 80 Test:"
curl -I http://api.interntrack.online 2>/dev/null | head -n1

echo -e "\n3. HTTPS Port 443 Test:"
curl -I https://api.interntrack.online 2>/dev/null | head -n1

echo -e "\n4. Document Root:"
ls -la /home/api.interntrack.online/public_html/ 2>/dev/null | head -n5

echo -e "\n5. SSL Certificate Files:"
ls -la /etc/letsencrypt/live/api.interntrack.online/ 2>/dev/null

echo -e "\n6. Firewall Status:"
ufw status | grep -E "80|443"

echo -e "\n7. CAA Records:"
dig api.interntrack.online CAA +short

echo -e "\n=== End Diagnostic ==="
```

---

## Testing & Verification

### API Endpoint Tests

**1. Health Check:**
```
GET https://api.interntrack.online/api/health
Expected: 200 OK
```

**2. Login Test:**
```
POST https://api.interntrack.online/api/login
Content-Type: application/json

{
  "email": "test@example.com",
  "password": "password123"
}
```

**3. Test with PowerShell:**
```powershell
# Health check
Invoke-RestMethod -Uri "https://api.interntrack.online/api/health"

# Login test
$body = @{
    email = "test@example.com"
    password = "password123"
} | ConvertTo-Json

Invoke-RestMethod -Uri "https://api.interntrack.online/api/login" `
    -Method POST `
    -ContentType "application/json" `
    -Body $body
```

### SSL Verification

**Online SSL Test:**
https://www.ssllabs.com/ssltest/analyze.html?d=api.interntrack.online

**Expected:** A or A+ rating

---

## ✅ Deployment Checklist

### DNS & Domain
- [ ] A record created for `api` subdomain
- [ ] DNS propagated (check whatsmydns.net)
- [ ] Subdomain resolves to server IP

### CyberPanel
- [ ] Website created for `api.interntrack.online`
- [ ] PHP 8.2 selected
- [ ] Document root verified

### SSL Certificate
- [ ] SSL issued successfully
- [ ] HTTPS working in browser
- [ ] No certificate warnings

### Laravel Backend
- [ ] Files uploaded to `/home/api.interntrack.online/public_html`
- [ ] Production `.env` configured
- [ ] File permissions set (755/775)
- [ ] Storage link created
- [ ] Database created and migrated
- [ ] Caches generated

### SendGrid
- [ ] Account created
- [ ] API key generated
- [ ] Sender identity verified
- [ ] Test email sent successfully

### Flutter App
- [ ] API URL updated to `https://api.interntrack.online/api`
- [ ] Production APK built
- [ ] End-to-end test passed

### Security
- [ ] APP_DEBUG=false
- [ ] Strong database password
- [ ] HTTPS redirect enabled
- [ ] Firewall configured

---

## 📞 Quick Reference

| Resource | URL/Path |
|----------|----------|
| Main Website | https://interntrack.online |
| API Base URL | https://api.interntrack.online/api |
| CyberPanel | https://YOUR_IP:8090 |
| API Document Root | /home/api.interntrack.online/public_html |
| Laravel Logs | /home/api.interntrack.online/public_html/storage/logs |
| SendGrid Dashboard | https://app.sendgrid.com |

---

**Created by:** Vince Delostrico  
**Project:** InternTrack - Attendance & Location Tracking System  
**Date:** January 6, 2026  
**Version:** 1.0 - Subdomain Deployment Guide
