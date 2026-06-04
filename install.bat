@echo off
setlocal EnableDelayedExpansion
title MahenGold - Auto Installer (Windows XAMPP)

echo.
echo  ============================================================
echo    MahenGold ^| Auto Installer untuk Windows + XAMPP
echo  ============================================================
echo.

:: ================================================================
:: 1. DETEKSI PHP (XAMPP)
:: ================================================================
set "PHP_CMD="
set "XAMPP_ROOT="

:: Cek drive C, D, E, F untuk XAMPP
for %%D in (C D E F G) do (
    if exist "%%D:\xampp\php\php.exe" (
        set "PHP_CMD=%%D:\xampp\php\php.exe"
        set "XAMPP_ROOT=%%D:\xampp"
        goto :php_found
    )
)
:: Fallback: PHP sudah ada di PATH
where php >nul 2>&1
if not errorlevel 1 (
    set "PHP_CMD=php"
    echo [WARN] XAMPP tidak ditemukan di C/D/E/F, menggunakan PHP dari PATH.
    goto :php_found
)
echo [ERROR] PHP tidak ditemukan!
echo.
echo   Install XAMPP dari https://www.apachefriends.org/
echo   lalu jalankan install.bat lagi.
echo.
pause & exit /b 1
:php_found
echo [OK] PHP   : %PHP_CMD%

:: ================================================================
:: 2. DETEKSI MYSQL
:: ================================================================
set "MYSQL_CMD="
set "MYSQLADMIN_CMD="
if defined XAMPP_ROOT (
    set "MYSQL_CMD=%XAMPP_ROOT%\mysql\bin\mysql.exe"
    set "MYSQLADMIN_CMD=%XAMPP_ROOT%\mysql\bin\mysqladmin.exe"
) else (
    where mysql >nul 2>&1
    if not errorlevel 1 ( set "MYSQL_CMD=mysql" & set "MYSQLADMIN_CMD=mysqladmin" )
)
if not defined MYSQL_CMD (
    echo [ERROR] MySQL tidak ditemukan.
    pause & exit /b 1
)
echo [OK] MySQL : %MYSQL_CMD%

:: ================================================================
:: 3. CEK / JALANKAN MYSQL
:: ================================================================
echo.
echo [..] Memeriksa koneksi MySQL...
"%MYSQLADMIN_CMD%" -u root -e ping >nul 2>&1
if errorlevel 1 (
    echo [WARN] MySQL belum berjalan.
    if defined XAMPP_ROOT (
        echo [..] Mencoba start MySQL via XAMPP...
        start /B "" "%XAMPP_ROOT%\mysql\bin\mysqld.exe" --standalone >nul 2>&1
        timeout /t 5 /nobreak >nul
        "%MYSQLADMIN_CMD%" -u root -e ping >nul 2>&1
    )
    if errorlevel 1 (
        echo.
        echo  [!] MySQL tidak bisa dijalankan otomatis.
        echo.
        echo      Solusi: Buka XAMPP Control Panel, klik START pada MySQL,
        echo              kemudian jalankan install.bat lagi.
        echo.
        pause & exit /b 1
    )
)
echo [OK] MySQL berjalan!

:: ================================================================
:: 4. SETUP .env (BASE URL)
:: ================================================================
:: Deteksi apakah project ada di dalam htdocs
set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"
for %%F in ("%SCRIPT_DIR%") do set "FOLDER_NAME=%%~nxF"

set "BASE_URL=http://localhost:8080/"
set "USE_SPARK=1"
echo %SCRIPT_DIR% | findstr /i /c:"\htdocs\" >nul 2>&1
if not errorlevel 1 (
    set "BASE_URL=http://localhost/%FOLDER_NAME%/public/"
    set "USE_SPARK=0"
)

echo [..] Mengatur app.baseURL = %BASE_URL%
powershell -NoProfile -Command ^
    "$url = '%BASE_URL%'; $content = Get-Content '.env' -Raw; $content = $content -replace 'app\.baseURL\s*=\s*''[^'']*''', (\"app.baseURL = '\" + $url + \"'\"); Set-Content '.env' $content -NoNewline -Encoding UTF8"
echo [OK] .env diperbarui.

:: ================================================================
:: 5. COMPOSER INSTALL
:: ================================================================
echo.
echo [..] Memeriksa Composer dependencies...
if exist "vendor\autoload.php" (
    echo [OK] Vendor sudah ada, skip composer.
) else (
    set "COMPOSER_OK=0"
    where composer >nul 2>&1
    if not errorlevel 1 (
        echo [..] Menjalankan: composer install
        composer install --no-dev --no-interaction --prefer-dist
        set "COMPOSER_OK=1"
    )
    if "!COMPOSER_OK!"=="0" if exist "composer.phar" (
        echo [..] Menjalankan: php composer.phar install
        "%PHP_CMD%" composer.phar install --no-dev --no-interaction --prefer-dist
        set "COMPOSER_OK=1"
    )
    if "!COMPOSER_OK!"=="0" (
        echo.
        echo  [!] Composer tidak ditemukan!
        echo.
        echo      1. Download Composer dari https://getcomposer.org/download/
        echo         (pilih "Composer-Setup.exe" untuk Windows)
        echo      2. Install Composer, restart CMD
        echo      3. Jalankan install.bat lagi
        echo.
        pause & exit /b 1
    )
    if not exist "vendor\autoload.php" (
        echo [ERROR] Composer install gagal.
        pause & exit /b 1
    )
    echo [OK] Dependencies terinstall.
)

:: ================================================================
:: 6. BUAT DATABASE
:: ================================================================
echo.
echo [..] Membuat database mahengold_demo (jika belum ada)...
"%MYSQL_CMD%" -u root -e "CREATE DATABASE IF NOT EXISTS mahengold_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1
if errorlevel 1 (
    echo [ERROR] Gagal membuat database. Cek koneksi MySQL.
    pause & exit /b 1
)
echo [OK] Database OK.

:: ================================================================
:: 7. MIGRASI
:: ================================================================
echo.
echo [..] Menjalankan migrasi database...
"%PHP_CMD%" spark migrate -n 2>&1
if errorlevel 1 (
    echo [ERROR] Migrasi gagal. Cek error di atas.
    pause & exit /b 1
)
echo [OK] Migrasi selesai.

:: ================================================================
:: 8. SEEDER (hanya jika database masih kosong)
:: ================================================================
echo.
echo [..] Memeriksa apakah data demo sudah ada...
"%MYSQL_CMD%" -u root -D mahengold_demo -N -e "SELECT COUNT(*) FROM users;" >"%TEMP%\mg_check.txt" 2>nul
set "ROW_COUNT=0"
set /p ROW_COUNT=<"%TEMP%\mg_check.txt"
del "%TEMP%\mg_check.txt" >nul 2>&1

if "%ROW_COUNT%"=="0" (
    echo [..] Database kosong. Mengisi data demo awal...
    "%PHP_CMD%" spark db:seed DatabaseSeeder 2>&1
    if errorlevel 1 (
        echo [WARN] Seeder gagal. Data demo tidak diisi.
    ) else (
        echo [OK] Data demo berhasil diisi.
        echo.
        echo      Akun admin default:
        echo        Email    : admin@mahengold.test
        echo        Username : admin
        echo        Password : admin123
    )
) else (
    echo [OK] Data sudah ada ^(skip seeder^).
)

:: ================================================================
:: 9. PERMISSIONS writable/
:: ================================================================
icacls "writable" /grant Everyone:(OI)(CI)F /T >nul 2>&1

:: ================================================================
:: 10. BUKA APLIKASI
:: ================================================================
echo.
echo  ============================================================
echo    Instalasi Selesai!
echo  ============================================================
echo.

if "%USE_SPARK%"=="1" (
    echo   URL  : %BASE_URL%
    echo   Mode : php spark serve ^(server bawaan CI4^)
    echo.
    echo   [i] Tekan Ctrl+C untuk stop server.
    echo  ============================================================
    echo.
    echo [..] Membuka browser...
    timeout /t 2 /nobreak >nul
    start "" "%BASE_URL%"
    echo [..] Menjalankan server...
    "%PHP_CMD%" spark serve
) else (
    echo   URL  : %BASE_URL%
    echo   Mode : XAMPP Apache
    echo.
    echo   [i] Pastikan Apache berjalan di XAMPP Control Panel!
    echo  ============================================================
    echo.
    start "" "%BASE_URL%"
    pause
)

endlocal
