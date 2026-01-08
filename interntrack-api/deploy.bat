@echo off
echo ================================
echo  Deploy AuthController to Production
echo ================================
echo.
echo Server: 72.61.115.6
echo Path: /home/api.interntrack.online/public_html/app/Http/Controllers/Api/
echo.
echo This will upload the fixed AuthController.php to production
echo.
set /p USERNAME="Enter SSH Username (usually root): "
echo.
echo Uploading file...
scp app\Http\Controllers\Api\AuthController.php %USERNAME%@72.61.115.6:/home/api.interntrack.online/public_html/app/Http/Controllers/Api/
echo.
if %ERRORLEVEL% EQU 0 (
    echo Upload successful!
    echo.
    echo Now clearing Laravel cache on production...
    ssh %USERNAME%@72.61.115.6 "cd /home/api.interntrack.online/public_html && php artisan config:clear && php artisan cache:clear && echo 'Cache cleared successfully!'"
    echo.
    echo ================================
    echo  Deployment Complete!
    echo ================================
    echo.
    echo The forgot password fix is now live on production.
    echo Test it from your Flutter app.
) else (
    echo.
    echo Upload failed! Please check your SSH credentials and try again.
    echo.
    echo Alternative: Use FileZilla or CyberPanel File Manager
    echo See DEPLOY_NOW.md for instructions
)
echo.
pause
