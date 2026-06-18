#!/bin/bash
# ============================================================
#   MahenGold | Auto Installer untuk Linux / macOS
# ============================================================
#   Jalankan: chmod +x auto_install.sh && ./auto_install.sh
#   Tambah "fresh" untuk rebuild DB: ./auto_install.sh fresh
# ============================================================

set -e
cd "$(dirname "$0")"

FRESH=0
if [ "$1" = "fresh" ]; then
    FRESH=1
    echo "[WARN] Mode FRESH aktif: database dibuat ulang & di-seed ulang."
fi

# ---- 0. Git pull (opsional) ----
if command -v git &>/dev/null && git rev-parse --is-inside-work-tree &>/dev/null; then
    echo "[..] Menarik update terbaru (git pull)..."
    git pull --autostash --ff-only 2>/dev/null || echo "[WARN] git pull dilewati."
fi

# ---- 1. Pastikan .env ada ----
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        echo "[OK] .env dibuat dari .env.example."
    else
        echo "[ERROR] .env dan .env.example tidak ada."
        exit 1
    fi
else
    echo "[OK] .env sudah ada."
fi

# ---- 2. Deteksi PHP ----
PHP_CMD=""
if command -v php &>/dev/null; then
    PHP_CMD="php"
elif [ -x /usr/bin/php8.2 ]; then
    PHP_CMD="/usr/bin/php8.2"
elif [ -x /usr/bin/php8.1 ]; then
    PHP_CMD="/usr/bin/php8.1"
fi

if [ -z "$PHP_CMD" ]; then
    echo "[ERROR] PHP tidak ditemukan!"
    echo "  Ubuntu/Debian: sudo apt install php php-mysql php-mbstring php-xml php-gd php-curl"
    echo "  macOS:         brew install php"
    exit 1
fi
echo "[OK] PHP: $($PHP_CMD -r 'echo PHP_VERSION;')"

# ---- 3. Deteksi MySQL ----
MYSQL_CMD=""
if command -v mysql &>/dev/null; then
    MYSQL_CMD="mysql"
elif [ -x /usr/local/bin/mysql ]; then
    MYSQL_CMD="/usr/local/bin/mysql"
fi

if [ -z "$MYSQL_CMD" ]; then
    echo "[ERROR] MySQL tidak ditemukan!"
    echo "  Ubuntu/Debian: sudo apt install mysql-server"
    echo "  macOS:         brew install mysql && brew services start mysql"
    exit 1
fi
echo "[OK] MySQL: $MYSQL_CMD"

# ---- 4. Buat database ----
if [ "$FRESH" -eq 1 ]; then
    echo "[WARN] FRESH: menghapus database mahengold_demo..."
    $MYSQL_CMD -u root -e "DROP DATABASE IF EXISTS mahengold_demo;" 2>/dev/null || true
fi

echo "[..] Membuat database mahengold_demo..."
$MYSQL_CMD -u root -e "CREATE DATABASE IF NOT EXISTS mahengold_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
if [ $? -ne 0 ]; then
    echo "[ERROR] Gagal membuat database!"
    echo "  Pastikan MySQL berjalan dan root bisa login tanpa password."
    echo "  Atau edit .env: database.default.password = <password>"
    exit 1
fi
echo "[OK] Database OK."

# ---- 5. Composer install ----
echo ""
echo "[..] Memeriksa Composer dependencies..."
if [ ! -f vendor/autoload.php ]; then
    if command -v composer &>/dev/null; then
        echo "[..] Menjalankan: composer install"
        composer install --no-dev --no-interaction --prefer-dist
    else
        echo "[ERROR] Composer tidak ditemukan!"
        echo "  Install: https://getcomposer.org/download/"
        exit 1
    fi
fi
echo "[OK] Dependencies siap."

# ---- 6. Migrasi ----
echo ""
echo "[..] Menjalankan migrasi database..."
$PHP_CMD spark migrate
if [ $? -ne 0 ]; then
    echo "[ERROR] Migrasi gagal."
    exit 1
fi
echo "[OK] Migrasi selesai."

# ---- 7. Seeder ----
echo ""
echo "[..] Menjalankan seeder..."
$PHP_CMD spark db:seed DatabaseSeeder 2>/dev/null || echo "[WARN] Seeder exit non-zero."
echo "[OK] Data demo siap."
echo "     Login admin: admin@mahengold.test / admin123"

# ---- 8. Verifikasi produk ----
PRODUK=$($MYSQL_CMD -u root -N -B -e "SELECT COUNT(*) FROM mahengold_demo.produk_emas WHERE status='aktif';" 2>/dev/null || echo "0")
if [ "$PRODUK" = "0" ] && [ -f database/mahengold_demo.sql ]; then
    echo "[WARN] Produk masih 0 - mengimpor database/mahengold_demo.sql (fallback)..."
    $MYSQL_CMD -u root mahengold_demo < database/mahengold_demo.sql 2>/dev/null
    PRODUK=$($MYSQL_CMD -u root -N -B -e "SELECT COUNT(*) FROM mahengold_demo.produk_emas WHERE status='aktif';" 2>/dev/null || echo "0")
fi
echo "[OK] Produk aktif: $PRODUK"

# ---- 9. Permissions writable ----
chmod -R 777 writable 2>/dev/null || true

# ---- 10. Selesai ----
echo ""
echo "============================================================"
echo "  Instalasi Selesai!"
echo "============================================================"
echo ""
echo "  URL  : http://localhost:8080/"
echo "  Mode : php spark serve"
echo ""
echo "  [i] Jalankan: $PHP_CMD spark serve"
echo "============================================================"
