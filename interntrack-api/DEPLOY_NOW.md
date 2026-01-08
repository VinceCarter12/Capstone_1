# Production Deployment Instructions
## Deploy AuthController Fix to api.interntrack.online

### Files Changed:
- `app/Http/Controllers/Api/AuthController.php` - Fixed forgot password error handling

---

## Quick Deploy via SCP (Recommended)

```powershell
# From interntrack-api directory, run:
scp app/Http/Controllers/Api/AuthController.php root@72.61.115.6:/home/api.interntrack.online/public_html/app/Http/Controllers/Api/

# Then SSH to production and clear cache:
ssh root@72.61.115.6
cd /home/api.interntrack.online/public_html
php artisan config:clear
php artisan cache:clear
exit
```

---

## Option 2: Deploy via CyberPanel File Manager

1. **Login to CyberPanel:**
   - URL: https://72.61.115.6:8090
   - Select domain: `api.interntrack.online`

2. **Navigate to File Manager:**
   - Go to: `public_html/app/Http/Controllers/Api/`

3. **Backup existing file:**
   - Download `AuthController.php` or rename to `AuthController.php.backup`

4. **Upload new file:**
   - Click "Upload"
   - Select: `C:\Users\vince\Desktop\Capstone_\interntrack-api\app\Http\Controllers\Api\AuthController.php`

5. **Clear Laravel cache (Terminal in CyberPanel):**
   ```bash
   cd /home/api.interntrack.online/public_html
   php artisan config:clear
   php artisan cache:clear
   ```

---

## Option 3: Deploy via FileZilla/WinSCP

1. **Connect via SFTP:**
   - Host: 72.61.115.6
   - Protocol: SFTP (SSH)
   - Port: 22
   - Username: root (or your SSH user)

2. **Navigate to:**
   - Remote: `/home/api.interntrack.online/public_html/app/Http/Controllers/Api/`
   - Local: `C:\Users\vince\Desktop\Capstone_\interntrack-api\app\Http\Controllers\Api\`

3. **Backup and Upload:**
   - Download existing `AuthController.php` (backup)
   - Upload new `AuthController.php`

4. **Clear cache via SSH:**
   ```bash
   ssh root@72.61.115.6
   cd /home/api.interntrack.online/public_html
   php artisan config:clear
   php artisan cache:clear
   ```

---

## Changes Summary

### What was fixed:
1. ✅ Added missing `Log` facade import
2. ✅ Fixed `\Swift_TransportException` → `\Symfony\Component\Mailer\Exception\TransportExceptionInterface`
3. ✅ Improved error handling for database and mail errors
4. ✅ Added detailed error logging
5. ✅ Better security (removed email enumeration)

### What the user will see:
- **Before:** "Server Error" (500)
- **After:** Clear error messages based on the actual problem:
  - Database errors: "Database connection error"
  - Mail errors: "Unable to send email"
  - With proper logging for debugging

---

## Verify Deployment

After deployment, test the forgot password endpoint:

```powershell
curl -X POST https://api.interntrack.online/api/forgot-password `
  -H "Content-Type: application/json" `
  -H "Accept: application/json" `
  -d '{"email":"test@pup.edu.ph"}'
```

Expected response (success):
```json
{
  "message": "If an account exists with this email, a password reset link will be sent."
}
```

Or check production logs:
```bash
ssh root@72.61.115.6
tail -f /home/api.interntrack.online/public_html/storage/logs/laravel.log
```

---

## Rollback (if needed)

If something goes wrong, restore the backup:
```bash
cd /home/api.interntrack.online/public_html/app/Http/Controllers/Api/
mv AuthController.php AuthController.php.new
mv AuthController.php.backup AuthController.php
php artisan config:clear
php artisan cache:clear
```
