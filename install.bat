@echo off
setlocal EnableDelayedExpansion
title MahenGold - Auto Installer (Windows XAMPP)

echo.
echo  ============================================================
echo    MahenGold ^| Auto Installer untuk Windows + XAMPP
echo  ============================================================
echo.

:: ================================================================
:: 0. SMART UPDATE — pindah ke folder skrip, tarik update terbaru
::    Jalankan "install.bat fresh" untuk rebuild DB + seed ulang.
:: ================================================================
cd /d "%~dp0"

set "FRESH=0"
if /I "%~1"=="fresh" set "FRESH=1"
if "%FRESH%"=="1" echo [WARN] Mode FRESH aktif: database dibuat ulang ^& di-seed ulang.

where git >nul 2>&1
if errorlevel 1 goto :skip_pull
git rev-parse --is-inside-work-tree >nul 2>&1
if errorlevel 1 goto :skip_pull
echo [..] Menarik update terbaru ^(git pull^)...
git pull --autostash --ff-only
if errorlevel 1 (echo [WARN] git pull dilewati ^(perubahan lokal / non-fast-forward^). Lanjut dengan kode saat ini.) else (echo [OK] Kode terbaru ditarik.)
:skip_pull

:: ================================================================
:: 1. DETEKSI PHP (XAMPP)
:: ================================================================
set "PHP_CMD="
set "XAMPP_ROOT="

for %%D in (C D E F G) do (
    if exist "%%D:\xampp\php\php.exe" (
        set "PHP_CMD=%%D:\xampp\php\php.exe"
        set "XAMPP_ROOT=%%D:\xampp"
        goto :php_found
    )
)
where php >nul 2>&1
if not errorlevel 1 (
    set "PHP_CMD=php"
    echo [WARN] XAMPP tidak ditemukan di C/D/E/F, pakai PHP dari PATH.
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
::    — cek proses dulu (tasklist), baru cek koneksi.
::    — kalau proses ada tapi ping gagal = MySQL punya password root
::      (tetap lanjut, biarkan spark migrate yang kasih error detail)
:: ================================================================
echo.
echo [..] Memeriksa MySQL...

:: Cek proses mysqld / mysqld_nt / mariadbd
set "MYSQL_PROC=0"
for %%P in (mysqld.exe mysqld_nt.exe mariadbd.exe) do (
    tasklist /FI "IMAGENAME eq %%P" 2>nul | find /i "%%P" >nul 2>&1
    if not errorlevel 1 set "MYSQL_PROC=1"
)

:: Cek Windows service mysql / mariadb
if "%MYSQL_PROC%"=="0" (
    sc query mysql  2>nul | find /i "RUNNING" >nul 2>&1
    if not errorlevel 1 set "MYSQL_PROC=1"
)
if "%MYSQL_PROC%"=="0" (
    sc query mariadb 2>nul | find /i "RUNNING" >nul 2>&1
    if not errorlevel 1 set "MYSQL_PROC=1"
)

if "%MYSQL_PROC%"=="1" (
    :: Proses ada — cek apakah bisa konek tanpa password
    "%MYSQLADMIN_CMD%" -u root ping >nul 2>&1
    if not errorlevel 1 (
        echo [OK] MySQL berjalan dan terhubung!
    ) else (
        echo [OK] MySQL proses berjalan.
        echo [WARN] Tidak bisa konek tanpa password.
        echo.
        echo   Jika MySQL kamu punya password root, edit file .env:
        echo     database.default.password = IsiPasswordMySQLKamu
        echo.
        echo   Tekan sembarang tombol untuk lanjut...
        pause >nul
    )
    goto :mysql_ready
)

:: Proses tidak ada — coba start lewat XAMPP
echo [WARN] MySQL tidak berjalan. Mencoba start otomatis...
if defined XAMPP_ROOT (
    start /B "" "%XAMPP_ROOT%\mysql\bin\mysqld.exe" --standalone >nul 2>&1
    timeout /t 5 /nobreak >nul
    tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | find /i "mysqld.exe" >nul 2>&1
    if not errorlevel 1 (
        echo [OK] MySQL berhasil distart.
        goto :mysql_ready
    )
)
echo.
echo  [!] MySQL tidak bisa dijalankan otomatis.
echo.
echo      Solusi: Buka XAMPP Control Panel ^> klik START pada MySQL
echo              kemudian jalankan install.bat lagi.
echo.
pause & exit /b 1
:mysql_ready

:: ================================================================
:: 4. SETUP .env (BASE URL)
:: ================================================================
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

echo.
echo [..] Mengatur app.baseURL = %BASE_URL%
powershell -NoProfile -Command ^
    "$url = '%BASE_URL%'; $c = Get-Content '.env' -Raw; $c = $c -replace 'app\.baseURL\s*=\s*''[^'']*''', (\"app.baseURL = '\" + $url + \"'\"); Set-Content '.env' $c -NoNewline -Encoding UTF8"
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
        if not errorlevel 1 set "COMPOSER_OK=1"
    )
    if "!COMPOSER_OK!"=="0" if exist "composer.phar" (
        echo [..] Menjalankan: php composer.phar install
        "%PHP_CMD%" composer.phar install --no-dev --no-interaction --prefer-dist
        if not errorlevel 1 set "COMPOSER_OK=1"
    )
    if "!COMPOSER_OK!"=="0" (
        echo.
        echo  [!] Composer tidak ditemukan!
        echo.
        echo      1. Download dari https://getcomposer.org/download/
        echo         ^(pilih "Composer-Setup.exe"^)
        echo      2. Install, restart CMD
        echo      3. Jalankan install.bat lagi
        echo.
        pause & exit /b 1
    )
    if not exist "vendor\autoload.php" (
        echo [ERROR] Composer install gagal. Cek error di atas.
        pause & exit /b 1
    )
    echo [OK] Dependencies terinstall.
)

:: ================================================================
:: 6. BUAT DATABASE
:: ================================================================
echo.
if "%FRESH%"=="1" (
    echo [WARN] FRESH: menghapus database mahengold_demo ^(semua data lama hilang^)...
    "%MYSQL_CMD%" -u root -e "DROP DATABASE IF EXISTS mahengold_demo;" 2>nul
)
echo [..] Membuat database mahengold_demo...
"%MYSQL_CMD%" -u root -e "CREATE DATABASE IF NOT EXISTS mahengold_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1
if errorlevel 1 (
    echo.
    echo [ERROR] Gagal membuat database!
    echo.
    echo   Kemungkinan penyebab:
    echo     - MySQL root punya password ^-^> edit .env: database.default.password = ^<password^>
    echo     - MySQL belum berjalan
    echo.
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
echo [..] Memeriksa data awal...
"%MYSQL_CMD%" -u root -D mahengold_demo -N -e "SELECT COUNT(*) FROM users;" >"%TEMP%\mg_check.txt" 2>nul
set "ROW_COUNT=0"
set /p ROW_COUNT=<"%TEMP%\mg_check.txt"
del "%TEMP%\mg_check.txt" >nul 2>&1

:: Bersihkan whitespace
set "ROW_COUNT=%ROW_COUNT: =%"
if "%ROW_COUNT%"=="" set "ROW_COUNT=0"

if "%ROW_COUNT%"=="0" (
    echo [..] Database kosong. Mengisi data demo...
    "%PHP_CMD%" spark db:seed DatabaseSeeder 2>&1
    if errorlevel 1 (
        echo [WARN] Seeder gagal.
    ) else (
        echo [OK] Data demo berhasil diisi.
        echo.
        echo      Login admin:
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
    echo   Mode : php spark serve
    echo.
    echo   [i] Tekan Ctrl+C untuk stop server.
    echo  ============================================================
    echo.
    timeout /t 2 /nobreak >nul
    start "" "%BASE_URL%"
    "%PHP_CMD%" spark serve
) else (
    echo   URL  : %BASE_URL%
    echo   Mode : XAMPP Apache
    echo.
    echo   [i] Pastikan Apache juga berjalan di XAMPP!
    echo  ============================================================
    echo.
    start "" "%BASE_URL%"
    pause
)

endlocal
