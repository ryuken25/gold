#!/usr/bin/env bash
# =============================================================
#  MahenGold — Smart Setup untuk macOS + XAMPP (1x jalan)
#  Pakai:  bash setup-mac.sh        |  reset penuh:  bash setup-mac.sh fresh
# =============================================================

RED='\033[0;31m'; YELLOW='\033[1;33m'; GREEN='\033[0;32m'; CYAN='\033[0;36m'; NC='\033[0m'
ok()   { echo -e "${GREEN}[OK]${NC} $*"; }
info() { echo -e "${CYAN}[..]${NC} $*"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $*"; }
fail() { echo -e "${RED}[ERROR]${NC} $*"; exit 1; }

echo ""
echo "============================================================"
echo "  MahenGold | Smart Setup untuk macOS + XAMPP"
echo "============================================================"
echo ""

# ================================================================
# 0. Pindah ke folder skrip + git pull (lindungi .env lokal)
# ================================================================
cd "$(cd "$(dirname "$0")" && pwd)" || exit 1
SCRIPT_DIR="$(pwd)"
FOLDER_NAME="$(basename "$SCRIPT_DIR")"

FRESH=false
[[ "${1:-}" == "fresh" ]] && FRESH=true
$FRESH && warn "Mode FRESH aktif: database dibuat ulang & di-seed ulang."

if command -v git &>/dev/null && git rev-parse --is-inside-work-tree &>/dev/null; then
    info "Menarik update terbaru (git pull)..."
    [[ -f .env ]] && cp .env .env.local.bak
    git checkout -- .env 2>/dev/null || true
    if git pull --autostash --ff-only; then ok "Kode terbaru ditarik."
    else warn "git pull dilewati (perubahan lokal / non-fast-forward). Lanjut dengan kode saat ini."; fi
    [[ -f .env.local.bak ]] && { cp .env.local.bak .env; rm -f .env.local.bak; ok ".env lokal dipertahankan."; }
fi

# ================================================================
# 1. Deteksi PHP / MySQL (XAMPP macOS, lalu PATH)
# ================================================================
PHP_CMD=""; XAMPP_ROOT=""; MYSQL_CMD=""
for XPATH in "/Applications/XAMPP/xamppfiles" "/usr/local/xampp/xamppfiles" "/opt/lampp"; do
    if [[ -x "$XPATH/bin/php" ]]; then
        PHP_CMD="$XPATH/bin/php"
        [[ -x "$XPATH/bin/mysql" ]] && MYSQL_CMD="$XPATH/bin/mysql"
        XAMPP_ROOT="$XPATH"
        break
    fi
done
if [[ -z "$PHP_CMD" ]]; then
    command -v php &>/dev/null && { PHP_CMD="php"; warn "XAMPP tidak ditemukan, pakai php dari PATH."; } \
        || fail "PHP tidak ditemukan! Install XAMPP for macOS: https://www.apachefriends.org/"
fi
[[ -z "$MYSQL_CMD" ]] && { command -v mysql &>/dev/null && MYSQL_CMD="mysql" || fail "Perintah mysql tidak ditemukan."; }
ok "PHP   : $PHP_CMD"
ok "MySQL : $MYSQL_CMD"

# ================================================================
# 2. Pastikan .env ada + FIX host DB untuk macOS (127.0.0.1)
#    'localhost' di macOS memakai socket yg sering gagal -> pakai TCP.
# ================================================================
echo ""
if [[ ! -f .env ]]; then
    if [[ -f .env.mac ]]; then cp .env.mac .env; ok ".env dibuat dari .env.mac (credential lokal).";
    elif [[ -f .env.example ]]; then cp .env.example .env; ok ".env dibuat dari .env.example.";
    else fail ".env / .env.mac / .env.example tidak ada."; fi
else
    ok ".env sudah ada (tidak diubah, kecuali host DB di bawah bila perlu)."
fi

if grep -qE '^[[:space:]]*database\.default\.hostname[[:space:]]*=[[:space:]]*localhost[[:space:]]*$' .env; then
    sed -i '' -E 's|^([[:space:]]*database\.default\.hostname[[:space:]]*=[[:space:]]*)localhost[[:space:]]*$|\1127.0.0.1|' .env
    ok "DB host diubah localhost -> 127.0.0.1 (fix koneksi XAMPP macOS)."
fi

# Baca konfigurasi DB dari .env
env_get() {
    local key escaped line
    key="$1"; escaped="$(printf '%s' "$key" | sed 's/\./\\./g')"
    line="$(grep -E "^[[:space:]]*${escaped}[[:space:]]*=" .env 2>/dev/null | grep -v '^[[:space:]]*#' | head -1)"
    [[ -z "$line" ]] && return 0
    printf '%s' "$line" | sed -E "s/^[^=]*=[[:space:]]*//; s/[[:space:]]+$//; s/^'(.*)'$/\1/; s/^\"(.*)\"$/\1/"
}
DB_HOST="$(env_get database.default.hostname)"; DB_HOST="${DB_HOST:-127.0.0.1}"
DB_NAME="$(env_get database.default.database)"; DB_NAME="${DB_NAME:-mahengold_demo}"
DB_USER="$(env_get database.default.username)"; DB_USER="${DB_USER:-root}"
DB_PASS="$(env_get database.default.password)"
DB_PORT="$(env_get database.default.port)";     DB_PORT="${DB_PORT:-3306}"
ok "Target DB : ${DB_USER}@${DB_HOST}:${DB_PORT}/${DB_NAME}"

MYSQL_ARGS=(--protocol=TCP -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER")
[[ -n "$DB_PASS" ]] && MYSQL_ARGS+=("-p${DB_PASS}")
mysql_exec()     { "$MYSQL_CMD" "${MYSQL_ARGS[@]}" "$@"; }
db_can_connect() { "$MYSQL_CMD" "${MYSQL_ARGS[@]}" -e "SELECT 1;" >/dev/null 2>&1; }
wait_mysql()     { local i; for i in $(seq 1 40); do db_can_connect && return 0; sleep 1; done; return 1; }
mysql_running()  { pgrep -x mysqld >/dev/null 2>&1 || pgrep -x mariadbd >/dev/null 2>&1 || pgrep -x mysqld_safe >/dev/null 2>&1; }

start_mysql() {
    if [[ -n "$XAMPP_ROOT" ]]; then
        if [[ -x "$XAMPP_ROOT/xampp" ]]; then info "Start MySQL via XAMPP (mungkin minta password Mac)..."; sudo "$XAMPP_ROOT/xampp" startmysql >/dev/null 2>&1 && return 0; fi
        if [[ -x "$XAMPP_ROOT/bin/mysql.server" ]]; then sudo "$XAMPP_ROOT/bin/mysql.server" start >/dev/null 2>&1 && return 0; fi
    fi
    return 1
}

# ================================================================
# 3. Pastikan MySQL hidup & bisa login (otomatis)
# ================================================================
info "Memeriksa koneksi MySQL..."
if db_can_connect; then
    ok "MySQL terhubung."
else
    mysql_running || { warn "MySQL belum berjalan. Mencoba menjalankan..."; start_mysql; }
    info "Menunggu MySQL siap..."
    if wait_mysql; then
        ok "MySQL siap & terhubung."
    elif mysql_running; then
        echo "  MySQL jalan tapi gagal login sebagai '${DB_USER}'. Jika root punya password,"
        echo "  isi di .env: database.default.password = <password>, lalu: bash setup-mac.sh"
        fail "Login MySQL gagal."
    else
        echo "  Tidak bisa start MySQL otomatis. Buka XAMPP > Manage Servers > Start MySQL,"
        echo "  lalu jalankan lagi: bash setup-mac.sh"
        fail "MySQL tidak berjalan."
    fi
fi

# ================================================================
# 4. Composer (unduh otomatis bila belum ada)
# ================================================================
echo ""
if [[ -f "vendor/autoload.php" ]]; then
    ok "Dependencies sudah ada (skip composer)."
else
    info "Menginstall dependencies (composer)..."
    COMPOSER_OK=false
    if command -v composer &>/dev/null; then
        composer install --no-dev --no-interaction --prefer-dist && COMPOSER_OK=true || true
    fi
    if ! $COMPOSER_OK && [[ ! -f "composer.phar" ]]; then
        info "Composer tidak ada. Mengunduh otomatis..."
        if command -v curl &>/dev/null; then
            curl -sS https://getcomposer.org/installer | "$PHP_CMD" -- --install-dir=. --filename=composer.phar
        else
            "$PHP_CMD" -r "copy('https://getcomposer.org/installer','composer-setup.php');" \
                && "$PHP_CMD" composer-setup.php --install-dir=. --filename=composer.phar
            rm -f composer-setup.php
        fi
    fi
    if ! $COMPOSER_OK && [[ -f "composer.phar" ]]; then
        "$PHP_CMD" composer.phar install --no-dev --no-interaction --prefer-dist && COMPOSER_OK=true || true
    fi
    $COMPOSER_OK && [[ -f "vendor/autoload.php" ]] || fail "Composer gagal. Pastikan ada internet, lalu: bash setup-mac.sh"
    ok "Dependencies terinstall."
fi

# ================================================================
# 5. Database (buat / reset)
# ================================================================
echo ""
if $FRESH; then
    warn "FRESH: menghapus database ${DB_NAME}..."
    mysql_exec -e "DROP DATABASE IF EXISTS \`${DB_NAME}\`;" 2>/dev/null || true
fi
info "Memastikan database ${DB_NAME} ada..."
mysql_exec -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
    || fail "Gagal membuat database ${DB_NAME}."
ok "Database OK."

# ================================================================
# 6. Migrasi + Seeder (idempotent)
# ================================================================
echo ""
info "Menjalankan migrasi..."
"$PHP_CMD" spark migrate -n || fail "Migrasi gagal. Cek error di atas."
ok "Migrasi selesai."

info "Memastikan data demo (seeder idempotent)..."
if "$PHP_CMD" spark db:seed DatabaseSeeder; then
    ok "Data demo siap. Login: admin@mahengold.test / admin123"
else
    warn "Seeder dilewati / gagal."
fi

chmod -R 755 writable/ 2>/dev/null || true

# ================================================================
# 7. Jalankan aplikasi
# ================================================================
echo ""
echo "============================================================"
echo "  Selesai! URL: http://localhost:8080"
echo "  Mode: php spark serve  (Ctrl+C untuk berhenti)"
echo "============================================================"
echo ""
sleep 1
open "http://localhost:8080" 2>/dev/null || true
"$PHP_CMD" spark serve
