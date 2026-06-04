#!/usr/bin/env bash
# =============================================================
#  MahenGold — Auto Installer (Mac / Linux + XAMPP)
# =============================================================
set -euo pipefail

# Warna terminal
RED='\033[0;31m'; YELLOW='\033[1;33m'; GREEN='\033[0;32m'; CYAN='\033[0;36m'; NC='\033[0m'

ok()   { echo -e "${GREEN}[OK]${NC} $*"; }
info() { echo -e "${CYAN}[..]${NC} $*"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $*"; }
fail() { echo -e "${RED}[ERROR]${NC} $*"; exit 1; }

echo ""
echo "============================================================"
echo "  MahenGold | Auto Installer untuk Mac/Linux + XAMPP"
echo "============================================================"
echo ""

# ================================================================
# 1. DETEKSI PHP (XAMPP Mac atau sistem)
# ================================================================
PHP_CMD=""
XAMPP_ROOT=""
MYSQL_CMD=""
MYSQLADMIN_CMD=""

# XAMPP Mac — dua lokasi umum
for XPATH in "/Applications/XAMPP/xamppfiles" "/opt/lampp" "/usr/local/xampp/xamppfiles"; do
    if [[ -x "$XPATH/bin/php" ]]; then
        PHP_CMD="$XPATH/bin/php"
        MYSQL_CMD="$XPATH/bin/mysql"
        MYSQLADMIN_CMD="$XPATH/bin/mysqladmin"
        XAMPP_ROOT="$XPATH"
        break
    fi
done

# Fallback ke PHP di PATH
if [[ -z "$PHP_CMD" ]]; then
    if command -v php &>/dev/null; then
        PHP_CMD="php"
        warn "XAMPP tidak ditemukan. Menggunakan php dari PATH."
    else
        fail "PHP tidak ditemukan!\n\n  Install XAMPP dari https://www.apachefriends.org/\n  lalu jalankan: bash install.sh"
    fi
fi

# MySQL fallback ke PATH
if [[ -z "$MYSQL_CMD" ]]; then
    command -v mysql &>/dev/null && MYSQL_CMD="mysql" || fail "MySQL tidak ditemukan."
    command -v mysqladmin &>/dev/null && MYSQLADMIN_CMD="mysqladmin"
fi

ok "PHP   : $PHP_CMD"
ok "MySQL : $MYSQL_CMD"

# ================================================================
# 2. CEK / JALANKAN MYSQL
# ================================================================
echo ""
info "Memeriksa koneksi MySQL..."

MYSQL_RUNNING=false
if "$MYSQLADMIN_CMD" -u root ping &>/dev/null 2>&1; then
    MYSQL_RUNNING=true
fi

if ! $MYSQL_RUNNING; then
    warn "MySQL belum berjalan."
    if [[ -n "$XAMPP_ROOT" ]]; then
        info "Mencoba start XAMPP MySQL..."
        # XAMPP Mac
        if [[ -x "$XAMPP_ROOT/../manager-osx.app/Contents/MacOS/manager-osx" ]]; then
            "$XAMPP_ROOT/../manager-osx.app/Contents/MacOS/manager-osx" &
            sleep 4
        elif [[ -x "$XAMPP_ROOT/xampp" ]]; then
            sudo "$XAMPP_ROOT/xampp" startmysql 2>/dev/null || true
            sleep 3
        fi
    fi
    # Coba lagi
    if "$MYSQLADMIN_CMD" -u root ping &>/dev/null 2>&1; then
        MYSQL_RUNNING=true
        ok "MySQL berhasil dijalankan."
    else
        echo ""
        echo "  [!] MySQL tidak bisa dijalankan otomatis."
        echo ""
        echo "  Solusi:"
        echo "    1. Buka XAMPP Manager (Applications > XAMPP > manager-osx)"
        echo "    2. Klik 'Start' pada MySQL/MariaDB"
        echo "    3. Jalankan lagi: bash install.sh"
        echo ""
        exit 1
    fi
else
    ok "MySQL berjalan!"
fi

# ================================================================
# 3. SETUP .env (BASE URL)
# ================================================================
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
FOLDER_NAME="$(basename "$SCRIPT_DIR")"

BASE_URL="http://localhost:8080/"
USE_SPARK=true

# Cek apakah project ada di dalam htdocs
if echo "$SCRIPT_DIR" | grep -qi "htdocs"; then
    BASE_URL="http://localhost/${FOLDER_NAME}/public/"
    USE_SPARK=false
fi

info "Mengatur app.baseURL = $BASE_URL"
if [[ "$(uname)" == "Darwin" ]]; then
    # macOS: sed -i butuh argumen backup
    sed -i '' "s|app\.baseURL\s*=\s*'[^']*'|app.baseURL = '${BASE_URL}'|" .env
else
    sed -i "s|app\.baseURL\s*=\s*'[^']*'|app.baseURL = '${BASE_URL}'|" .env
fi
ok ".env diperbarui."

# ================================================================
# 4. COMPOSER INSTALL
# ================================================================
echo ""
info "Memeriksa Composer dependencies..."

if [[ -f "vendor/autoload.php" ]]; then
    ok "Vendor sudah ada, skip composer."
else
    COMPOSER_OK=false

    if command -v composer &>/dev/null; then
        info "Menjalankan: composer install"
        composer install --no-dev --no-interaction --prefer-dist && COMPOSER_OK=true
    fi

    if ! $COMPOSER_OK && [[ -f "composer.phar" ]]; then
        info "Menjalankan: php composer.phar install"
        "$PHP_CMD" composer.phar install --no-dev --no-interaction --prefer-dist && COMPOSER_OK=true
    fi

    if ! $COMPOSER_OK; then
        echo ""
        echo "  [!] Composer tidak ditemukan!"
        echo ""
        echo "  Cara install Composer di Mac:"
        echo "    curl -sS https://getcomposer.org/installer | php"
        echo "    sudo mv composer.phar /usr/local/bin/composer"
        echo ""
        echo "  Setelah itu jalankan lagi: bash install.sh"
        echo ""
        exit 1
    fi

    [[ -f "vendor/autoload.php" ]] || fail "Composer install gagal."
    ok "Dependencies terinstall."
fi

# ================================================================
# 5. BUAT DATABASE
# ================================================================
echo ""
info "Membuat database mahengold_demo (jika belum ada)..."
"$MYSQL_CMD" -u root -e \
    "CREATE DATABASE IF NOT EXISTS mahengold_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
    || fail "Gagal membuat database."
ok "Database OK."

# ================================================================
# 6. MIGRASI
# ================================================================
echo ""
info "Menjalankan migrasi database..."
"$PHP_CMD" spark migrate -n || fail "Migrasi gagal."
ok "Migrasi selesai."

# ================================================================
# 7. SEEDER (hanya jika database masih kosong)
# ================================================================
echo ""
info "Memeriksa apakah data demo sudah ada..."
ROW_COUNT=$("$MYSQL_CMD" -u root -D mahengold_demo -N -e "SELECT COUNT(*) FROM users;" 2>/dev/null || echo "0")
ROW_COUNT="${ROW_COUNT//[^0-9]/}"  # strip whitespace/newline

if [[ "$ROW_COUNT" == "0" ]] || [[ -z "$ROW_COUNT" ]]; then
    info "Database kosong. Mengisi data demo..."
    if "$PHP_CMD" spark db:seed DatabaseSeeder; then
        ok "Data demo berhasil diisi."
        echo ""
        echo "     Akun admin default:"
        echo "       Email    : admin@mahengold.test"
        echo "       Username : admin"
        echo "       Password : admin123"
    else
        warn "Seeder gagal. Data demo tidak diisi."
    fi
else
    ok "Data sudah ada (skip seeder)."
fi

# ================================================================
# 8. PERMISSIONS writable/
# ================================================================
chmod -R 755 writable/ 2>/dev/null || true

# ================================================================
# 9. BUKA APLIKASI
# ================================================================
echo ""
echo "============================================================"
echo "  Instalasi Selesai!"
echo "============================================================"
echo ""

open_browser() {
    local url="$1"
    if [[ "$(uname)" == "Darwin" ]]; then
        open "$url" 2>/dev/null || true
    elif command -v xdg-open &>/dev/null; then
        xdg-open "$url" 2>/dev/null || true
    fi
}

if $USE_SPARK; then
    echo "  URL  : $BASE_URL"
    echo "  Mode : php spark serve (server bawaan CI4)"
    echo ""
    echo "  [i] Tekan Ctrl+C untuk stop server."
    echo "============================================================"
    echo ""
    info "Membuka browser..."
    sleep 1
    open_browser "$BASE_URL"
    info "Menjalankan server..."
    "$PHP_CMD" spark serve
else
    echo "  URL  : $BASE_URL"
    echo "  Mode : XAMPP Apache"
    echo ""
    echo "  [i] Pastikan Apache berjalan di XAMPP Manager!"
    echo "============================================================"
    echo ""
    open_browser "$BASE_URL"
fi
