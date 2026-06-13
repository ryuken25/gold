#!/usr/bin/env bash
# =============================================================
#  MahenGold — Smart Auto Installer (Mac / Linux + XAMPP)
#  Sekali jalan: bash setup-windows.sh   |   reset penuh: bash setup-windows.sh fresh
# =============================================================

RED='\033[0;31m'; YELLOW='\033[1;33m'; GREEN='\033[0;32m'; CYAN='\033[0;36m'; NC='\033[0m'
ok()   { echo -e "${GREEN}[OK]${NC} $*"; }
info() { echo -e "${CYAN}[..]${NC} $*"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $*"; }
fail() { echo -e "${RED}[ERROR]${NC} $*"; exit 1; }

echo ""
echo "============================================================"
echo "  MahenGold | Smart Auto Installer (Mac/Linux + XAMPP)"
echo "============================================================"
echo ""

# ================================================================
# 0. Pindah ke folder skrip + smart update (git pull)
#    "bash setup-windows.sh fresh" = rebuild DB + seed ulang.
# ================================================================
cd "$(cd "$(dirname "$0")" && pwd)" || exit 1
SCRIPT_DIR="$(pwd)"
FOLDER_NAME="$(basename "$SCRIPT_DIR")"

FRESH=false
[[ "${1:-}" == "fresh" ]] && FRESH=true
$FRESH && warn "Mode FRESH aktif: database akan dibuat ulang & di-seed ulang."

if command -v git &>/dev/null && git rev-parse --is-inside-work-tree &>/dev/null; then
    info "Menarik update terbaru (git pull)..."
    # Lindungi .env lokal: backup, bersihkan agar pull tidak bentrok, lalu pulihkan.
    [[ -f .env ]] && cp .env .env.local.bak
    git checkout -- .env 2>/dev/null || true
    if git pull --autostash --ff-only; then ok "Kode terbaru ditarik."
    else warn "git pull dilewati (perubahan lokal / non-fast-forward). Lanjut dengan kode saat ini."; fi
    [[ -f .env.local.bak ]] && { cp .env.local.bak .env; rm -f .env.local.bak; ok ".env lokal dipertahankan."; }
fi

# ================================================================
# 1. Deteksi PHP / MySQL (XAMPP dulu, lalu PATH)
# ================================================================
PHP_CMD=""; XAMPP_ROOT=""; MYSQL_CMD=""; MYSQLADMIN_CMD=""
for XPATH in "/Applications/XAMPP/xamppfiles" "/opt/lampp" "/usr/local/xampp/xamppfiles"; do
    if [[ -x "$XPATH/bin/php" ]]; then
        PHP_CMD="$XPATH/bin/php"
        [[ -x "$XPATH/bin/mysql" ]]      && MYSQL_CMD="$XPATH/bin/mysql"
        [[ -x "$XPATH/bin/mysqladmin" ]] && MYSQLADMIN_CMD="$XPATH/bin/mysqladmin"
        XAMPP_ROOT="$XPATH"
        break
    fi
done
if [[ -z "$PHP_CMD" ]]; then
    command -v php &>/dev/null && { PHP_CMD="php"; warn "XAMPP tidak ditemukan, pakai php dari PATH."; } \
        || fail "PHP tidak ditemukan! Install XAMPP: https://www.apachefriends.org/"
fi
[[ -z "$MYSQL_CMD" ]]      && { command -v mysql &>/dev/null      && MYSQL_CMD="mysql"           || fail "Perintah mysql tidak ditemukan."; }
[[ -z "$MYSQLADMIN_CMD" ]] && { command -v mysqladmin &>/dev/null && MYSQLADMIN_CMD="mysqladmin"; }
ok "PHP   : $PHP_CMD"
ok "MySQL : $MYSQL_CMD"

# ================================================================
# 2. Pastikan .env ada + baca konfigurasi database darinya
# ================================================================
echo ""
if [[ ! -f .env ]]; then
    if [[ -f .env.example ]]; then cp .env.example .env; ok ".env dibuat dari .env.example.";
    else fail ".env tidak ada dan .env.example juga tidak ada."; fi
else
    ok ".env sudah ada (tidak diubah)."
fi

# Ambil nilai dari .env (baris non-komentar 'key = value', buang kutip).
env_get() {
    local key escaped line
    key="$1"; escaped="$(printf '%s' "$key" | sed 's/\./\\./g')"
    line="$(grep -E "^[[:space:]]*${escaped}[[:space:]]*=" .env 2>/dev/null | grep -v '^[[:space:]]*#' | head -1)"
    [[ -z "$line" ]] && return 0
    printf '%s' "$line" | sed -E "s/^[^=]*=[[:space:]]*//; s/[[:space:]]+$//; s/^'(.*)'$/\1/; s/^\"(.*)\"$/\1/"
}

DB_HOST="$(env_get database.default.hostname)"; DB_HOST="${DB_HOST:-localhost}"
DB_NAME="$(env_get database.default.database)"; DB_NAME="${DB_NAME:-mahengold_demo}"
DB_USER="$(env_get database.default.username)"; DB_USER="${DB_USER:-root}"
DB_PASS="$(env_get database.default.password)"
DB_PORT="$(env_get database.default.port)";     DB_PORT="${DB_PORT:-3306}"
ok "Target DB : ${DB_USER}@${DB_HOST}:${DB_PORT}/${DB_NAME}"

# Helper koneksi MySQL pakai kredensial dari .env.
MYSQL_ARGS=(-h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER")
[[ -n "$DB_PASS" ]] && MYSQL_ARGS+=("-p${DB_PASS}")
mysql_exec()     { "$MYSQL_CMD" "${MYSQL_ARGS[@]}" "$@"; }
db_can_connect() { "$MYSQL_CMD" "${MYSQL_ARGS[@]}" -e "SELECT 1;" >/dev/null 2>&1; }
wait_mysql()     { local i; for i in $(seq 1 40); do db_can_connect && return 0; sleep 1; done; return 1; }
mysql_running()  { pgrep -x mysqld >/dev/null 2>&1 || pgrep -x mariadbd >/dev/null 2>&1 || pgrep -x mysqld_safe >/dev/null 2>&1; }

start_mysql() {
    local cli
    for cli in "$XAMPP_ROOT/xampp" "$XAMPP_ROOT/lampp" "$XAMPP_ROOT/manager-osx" \
               "/Applications/XAMPP/xamppfiles/lampp" "/opt/lampp/lampp"; do
        if [[ -x "$cli" ]]; then
            info "Start MySQL via: $cli (mungkin minta password sudo)"
            sudo "$cli" startmysql >/dev/null 2>&1 && return 0
        fi
    done
    if command -v mysqld_safe &>/dev/null; then ( mysqld_safe >/dev/null 2>&1 & ) ; return 0; fi
    return 1
}

# ================================================================
# 3. Pastikan MySQL hidup & bisa login (otomatis, tanpa interaksi)
# ================================================================
info "Memeriksa koneksi MySQL..."
if db_can_connect; then
    ok "MySQL terhubung."
else
    mysql_running || { warn "MySQL belum berjalan. Mencoba menjalankan otomatis..."; start_mysql; }
    info "Menunggu MySQL siap..."
    if wait_mysql; then
        ok "MySQL siap & terhubung."
    elif mysql_running; then
        echo ""
        echo "  MySQL berjalan tetapi gagal login sebagai '${DB_USER}'."
        echo "  Jika root MySQL kamu punya password, isi di .env:"
        echo "     database.default.password = <password_mysql_kamu>"
        echo "  lalu jalankan lagi: bash setup-windows.sh"
        fail "Login MySQL gagal."
    else
        echo ""
        echo "  Tidak bisa menjalankan MySQL otomatis."
        echo "  Buka XAMPP Manager -> START MySQL/MariaDB, lalu: bash setup-windows.sh"
        fail "MySQL tidak berjalan."
    fi
fi

# ================================================================
# 4. Tentukan URL & mode (TANPA mengubah .env)
# ================================================================
BASE_URL="http://localhost:8080/"; USE_SPARK=true
if echo "$SCRIPT_DIR" | grep -qi "htdocs"; then
    BASE_URL="http://localhost/${FOLDER_NAME}/public/"; USE_SPARK=false
fi

# ================================================================
# 5. Composer install (kalau vendor belum ada)
# ================================================================
echo ""
if [[ -f "vendor/autoload.php" ]]; then
    ok "Dependencies sudah ada (skip composer)."
else
    info "Menginstall dependencies (composer)..."
    COMPOSER_OK=false

    # 1) composer global
    if ! $COMPOSER_OK && command -v composer &>/dev/null; then
        composer install --no-dev --no-interaction --prefer-dist && COMPOSER_OK=true || true
    fi

    # 2) Belum ada composer & composer.phar -> unduh composer.phar otomatis
    if ! $COMPOSER_OK && [[ ! -f "composer.phar" ]]; then
        info "Composer tidak ditemukan. Mengunduh Composer otomatis..."
        if command -v curl &>/dev/null; then
            curl -sS https://getcomposer.org/installer | "$PHP_CMD" -- --install-dir=. --filename=composer.phar
        else
            "$PHP_CMD" -r "copy('https://getcomposer.org/installer','composer-setup.php');" \
                && "$PHP_CMD" composer-setup.php --install-dir=. --filename=composer.phar
            rm -f composer-setup.php
        fi
        [[ -f "composer.phar" ]] && ok "Composer terunduh (composer.phar)." || warn "Gagal mengunduh Composer."
    fi

    # 3) Pakai composer.phar
    if ! $COMPOSER_OK && [[ -f "composer.phar" ]]; then
        "$PHP_CMD" composer.phar install --no-dev --no-interaction --prefer-dist && COMPOSER_OK=true || true
    fi

    $COMPOSER_OK && [[ -f "vendor/autoload.php" ]] || {
        echo ""
        echo "  Gagal install dependencies otomatis."
        echo "  Pastikan ada koneksi internet (untuk unduh Composer), lalu jalankan lagi:"
        echo "    bash setup-windows.sh"
        fail "Composer gagal."
    }
    ok "Dependencies terinstall."
fi

# ================================================================
# 6. Buat / reset database
# ================================================================
echo ""
if $FRESH; then
    warn "FRESH: menghapus database ${DB_NAME} (semua data lama hilang)..."
    mysql_exec -e "DROP DATABASE IF EXISTS \`${DB_NAME}\`;" 2>/dev/null || true
fi
info "Memastikan database ${DB_NAME} ada..."
mysql_exec -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
    || fail "Gagal membuat database ${DB_NAME}."
ok "Database OK."

# ================================================================
# 7. Migrasi
# ================================================================
echo ""
info "Menjalankan migrasi database..."
"$PHP_CMD" spark migrate -n || fail "Migrasi gagal. Cek error di atas."
ok "Migrasi selesai."

# ================================================================
# 8. Seeder (idempotent — aman dijalankan berulang)
# ================================================================
echo ""
info "Memastikan data demo (seeder idempotent)..."
"$PHP_CMD" spark db:seed DatabaseSeeder || warn "Seeder exit non-zero (cek pesan di atas)."

# Verifikasi produk + fallback berlapis (re-seed -> impor SQL dump).
count_produk() { mysql_exec -D "$DB_NAME" -N -B -e "SELECT COUNT(*) FROM produk_emas WHERE status='aktif' AND deleted_at IS NULL;" 2>/dev/null | tr -dc '0-9'; }
PRODUK_COUNT="$(count_produk)"; PRODUK_COUNT="${PRODUK_COUNT:-0}"
if [[ "$PRODUK_COUNT" -eq 0 ]]; then
    warn "Produk masih 0 — menjalankan ulang seeder (verbose)..."
    "$PHP_CMD" spark db:seed MahenGoldSeeder || true
    PRODUK_COUNT="$(count_produk)"; PRODUK_COUNT="${PRODUK_COUNT:-0}"
fi
if [[ "$PRODUK_COUNT" -eq 0 && -f database/mahengold_demo.sql ]]; then
    warn "Masih 0 — impor database/mahengold_demo.sql (fallback anti-gagal)..."
    mysql_exec "$DB_NAME" < database/mahengold_demo.sql 2>/dev/null || warn "Impor SQL dump gagal."
    PRODUK_COUNT="$(count_produk)"; PRODUK_COUNT="${PRODUK_COUNT:-0}"
fi
if [[ "$PRODUK_COUNT" -gt 0 ]]; then
    ok "Produk aktif di database: ${PRODUK_COUNT}. Login: admin@mahengold.test / admin123"
else
    warn "Produk TETAP 0. Cek MySQL password / DB yang dibaca app. Coba: bash setup-windows.sh fresh"
fi

# ================================================================
# 9. Permissions writable/
# ================================================================
chmod -R 755 writable/ 2>/dev/null || true

# ================================================================
# 10. Jalankan aplikasi
# ================================================================
echo ""
echo "============================================================"
echo "  Instalasi Selesai!"
echo "============================================================"

open_browser() {
    if [[ "$(uname)" == "Darwin" ]]; then open "$1" 2>/dev/null || true
    elif command -v xdg-open &>/dev/null; then xdg-open "$1" 2>/dev/null || true; fi
}

echo "  URL : $BASE_URL"
if $USE_SPARK; then
    echo "  Mode: php spark serve   (Ctrl+C untuk berhenti)"
    echo "============================================================"
    echo ""
    sleep 1
    open_browser "$BASE_URL"
    "$PHP_CMD" spark serve
else
    echo "  Mode: XAMPP Apache (pastikan Apache juga START)"
    echo "============================================================"
    echo ""
    open_browser "$BASE_URL"
fi
