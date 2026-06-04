#!/usr/bin/env bash
# =============================================================
#  MahenGold — Auto Installer (Mac / Linux + XAMPP)
# =============================================================

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

for XPATH in \
    "/Applications/XAMPP/xamppfiles" \
    "/opt/lampp" \
    "/usr/local/xampp/xamppfiles"; do
    if [[ -x "$XPATH/bin/php" ]]; then
        PHP_CMD="$XPATH/bin/php"
        MYSQL_CMD="$XPATH/bin/mysql"
        MYSQLADMIN_CMD="$XPATH/bin/mysqladmin"
        XAMPP_ROOT="$XPATH"
        break
    fi
done

if [[ -z "$PHP_CMD" ]]; then
    if command -v php &>/dev/null; then
        PHP_CMD="php"
        warn "XAMPP tidak ditemukan. Menggunakan php dari PATH."
    else
        fail "PHP tidak ditemukan! Install XAMPP dari https://www.apachefriends.org/"
    fi
fi

if [[ -z "$MYSQL_CMD" ]]; then
    command -v mysql &>/dev/null     && MYSQL_CMD="mysql"     || fail "MySQL tidak ditemukan."
    command -v mysqladmin &>/dev/null && MYSQLADMIN_CMD="mysqladmin"
fi

ok "PHP   : $PHP_CMD"
ok "MySQL : $MYSQL_CMD"

# ================================================================
# 2. CEK / JALANKAN MYSQL
#    — cek apakah proses mysqld/mariadbd sudah berjalan (pgrep).
#    — ping hanya sebagai konfirmasi; gagal ping tidak berarti MySQL mati,
#      bisa juga karena MySQL punya password root.
# ================================================================
echo ""
info "Memeriksa MySQL..."

MYSQL_PROC=false
if pgrep -x "mysqld"      >/dev/null 2>&1 || \
   pgrep -x "mariadbd"    >/dev/null 2>&1 || \
   pgrep -x "mysqld_safe" >/dev/null 2>&1; then
    MYSQL_PROC=true
fi

if $MYSQL_PROC; then
    # Proses ada — coba ping (opsional, gagal tidak apa-apa)
    if "$MYSQLADMIN_CMD" -u root ping >/dev/null 2>&1; then
        ok "MySQL berjalan dan terhubung!"
    else
        ok "MySQL proses berjalan."
        warn "Tidak bisa ping tanpa password."
        echo ""
        echo "   Jika MySQL punya password root, edit file .env:"
        echo "     database.default.password = IsiPasswordMySQLKamu"
        echo ""
        echo "   Tekan Enter untuk lanjut..."
        read -r
    fi
else
    warn "MySQL tidak berjalan. Mencoba start..."

    if [[ -n "$XAMPP_ROOT" ]]; then
        # Coba via XAMPP CLI
        for XAMPP_CLI in \
            "$XAMPP_ROOT/xampp" \
            "$XAMPP_ROOT/../xampp" \
            "/Applications/XAMPP/xamppfiles/lampp"; do
            if [[ -x "$XAMPP_CLI" ]]; then
                sudo "$XAMPP_CLI" startmysql 2>/dev/null && sleep 4 && break
            fi
        done
    fi

    # Cek ulang proses setelah start attempt
    if pgrep -x "mysqld" >/dev/null 2>&1 || pgrep -x "mariadbd" >/dev/null 2>&1; then
        ok "MySQL berhasil distart."
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
fi

# ================================================================
# 3. SETUP .env (BASE URL)
# ================================================================
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
FOLDER_NAME="$(basename "$SCRIPT_DIR")"

BASE_URL="http://localhost:8080/"
USE_SPARK=true

if echo "$SCRIPT_DIR" | grep -qi "htdocs"; then
    BASE_URL="http://localhost/${FOLDER_NAME}/public/"
    USE_SPARK=false
fi

info "Mengatur app.baseURL = $BASE_URL"
if [[ "$(uname)" == "Darwin" ]]; then
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
        composer install --no-dev --no-interaction --prefer-dist && COMPOSER_OK=true || true
    fi

    if ! $COMPOSER_OK && [[ -f "composer.phar" ]]; then
        info "Menjalankan: php composer.phar install"
        "$PHP_CMD" composer.phar install --no-dev --no-interaction --prefer-dist && COMPOSER_OK=true || true
    fi

    if ! $COMPOSER_OK; then
        echo ""
        echo "  [!] Composer tidak ditemukan!"
        echo ""
        echo "  Install Composer di Mac/Linux:"
        echo "    curl -sS https://getcomposer.org/installer | php"
        echo "    sudo mv composer.phar /usr/local/bin/composer"
        echo ""
        echo "  Lalu jalankan lagi: bash install.sh"
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
info "Membuat database mahengold_demo..."
if ! "$MYSQL_CMD" -u root -e \
    "CREATE DATABASE IF NOT EXISTS mahengold_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
    2>&1; then
    echo ""
    echo "  [ERROR] Gagal membuat database!"
    echo ""
    echo "  Kemungkinan penyebab:"
    echo "    - MySQL root punya password -> edit .env: database.default.password = <password>"
    echo "    - MySQL belum berjalan"
    echo ""
    exit 1
fi
ok "Database OK."

# ================================================================
# 6. MIGRASI
# ================================================================
echo ""
info "Menjalankan migrasi database..."
"$PHP_CMD" spark migrate -n || fail "Migrasi gagal. Cek error di atas."
ok "Migrasi selesai."

# ================================================================
# 7. SEEDER (hanya jika database masih kosong)
# ================================================================
echo ""
info "Memeriksa data awal..."
ROW_COUNT=$("$MYSQL_CMD" -u root -D mahengold_demo -N \
    -e "SELECT COUNT(*) FROM users;" 2>/dev/null || echo "0")
ROW_COUNT="${ROW_COUNT//[^0-9]/}"
[[ -z "$ROW_COUNT" ]] && ROW_COUNT="0"

if [[ "$ROW_COUNT" == "0" ]]; then
    info "Database kosong. Mengisi data demo..."
    if "$PHP_CMD" spark db:seed DatabaseSeeder; then
        ok "Data demo berhasil diisi."
        echo ""
        echo "     Login admin:"
        echo "       Email    : admin@mahengold.test"
        echo "       Username : admin"
        echo "       Password : admin123"
    else
        warn "Seeder gagal."
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
    echo "  Mode : php spark serve"
    echo ""
    echo "  [i] Tekan Ctrl+C untuk stop server."
    echo "============================================================"
    echo ""
    sleep 1
    open_browser "$BASE_URL"
    "$PHP_CMD" spark serve
else
    echo "  URL  : $BASE_URL"
    echo "  Mode : XAMPP Apache"
    echo ""
    echo "  [i] Pastikan Apache juga berjalan di XAMPP Manager!"
    echo "============================================================"
    echo ""
    open_browser "$BASE_URL"
fi
