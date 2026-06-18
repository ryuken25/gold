# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

Two distinct subsystems share one repo:

1. **MahenGold** — a CodeIgniter 4 / PHP 8.2 web app for gold sales & installment credit (the actual product). MVC + a Services layer, MariaDB/MySQL, Bootstrap 5 views. This is what `app/`, `public/`, `database/`, and the setup scripts are for.
2. **BAB IV report generator** — `ultimate_laporan.py` + `skills/bab4-diagrams/`, a standalone Python tool that generates the author's thesis chapter (DFD/ERD diagrams + a `.docx`) by auditing the PHP app and screenshotting it live. Unrelated to runtime; see "Report generator" below.

When a task says "the app", "fitur", "pesanan", "kredit", "admin" → it's the PHP app. When it mentions BAB IV, DFD, ERD, diagram, laporan, or `.docx` → it's the Python tool.

## Commands

### Run / setup the PHP app
- **One-click full setup** (git pull + `.env` + start MySQL + migrate + seed + serve): `setup-windows.bat` (Windows), `bash setup-mac.sh` (macOS, auto-rewrites DB host to `127.0.0.1`), `bash setup-windows.sh` (Linux). Append `fresh` to wipe + reseed the DB.
- Manual serve: `php spark serve` → http://127.0.0.1:8080
- Migrate + seed from scratch: `php spark migrate:fresh --seed`
- Download/refresh product images: `php spark assets:download-mahengold [--force]` (falls back to local SVGs offline)

### Tests
- All: `composer test` or `vendor/bin/phpunit`
- Single file: `vendor/bin/phpunit tests/unit/SomeTest.php`
- Single method: `vendor/bin/phpunit --filter testMethodName`

### Report generator (Python)
- `python ultimate_laporan.py` (app must be running on :8080 for the §4.5 screenshots). Flags: `--skip-capture`, `--no-render`, `--no-docx`, `--base-url URL`. Output lands in `BabIvAssets/`. Deps: `pip install -r skills/bab4-diagrams/requirements.txt` (uses Playwright).

## Architecture (PHP app)

### Two auth realms, one login page
`/login` is shared. `Customer\AuthController::attempt` routes by `users.role`: `admin` → `/admin/*` (guarded by the `adminauth` filter), `pelanggan` → `/akun/*` (guarded by `customerauth`). Session keys differ: admin uses `current_admin()`, customer uses `current_pelanggan()` (both in `app/Helpers/mahen_helper.php`, auto-loaded). There is **no separate admin login** despite `/admin/login` existing — it redirects to `/login`.

### The order → credit → payment pipeline (the core domain flow)
This is the spine of the app and spans several controllers/services/tables:

1. **Pesanan (customer)** — `PublicController::ajukanPesanan` writes a row to `pengajuan` (status `baru`). Method is `cash` or `kredit`. For `kredit`, a KTP photo upload is **mandatory** (saved to `writable/uploads/ktp/`) and `uang_muka` (DP) is computed server-side — see DP rule below.
2. **Verifikasi (admin)** — `Admin\PengajuanController::verifikasi` sets status `disetujui`. For `kredit` it **auto-creates** the `nasabah` (if missing) + `kredit` + full `jadwal_angsuran` schedule via `CreditTransactionService::createFromPengajuan` (idempotent — won't double-create if a kredit already links to that pengajuan).
3. **Bukti pembayaran (customer)** — `Customer\AkunController` uploads proof to `writable/uploads/bukti/` into the single `bukti_pembayaran` table, discriminated by `tipe`:
   - `cash` — full payment for a cash order (`uploadBuktiCash`)
   - `dp` — the down payment for a credit order (`uploadBuktiDP`)
   - `cicilan` — one installment against a `jadwal_angsuran` row (`uploadBuktiAngsuran`)
4. **Verifikasi pembayaran (admin)** — `Admin\PembayaranController::verifikasi` branches on `tipe`: `cicilan` → records payment via `PaymentService::record` (updates kredit `total_terbayar`/`sisa_piutang`, marks schedule paid, flips to `lunas` when done); `dp` → marks `pengajuan.pembayaran_status = terverifikasi` (installments continue separately); `cash` → marks the order `selesai`. Rejection (`tolak`) sets `pembayaran_status = belum`.

**Status fields are two independent axes on `pengajuan`:** `status` (baru/diproses/disetujui/ditolak/dibatalkan/selesai) tracks the order; `pembayaran_status` (belum/menunggu/terverifikasi) tracks whether a DP/cash proof has been verified. A bukti row also has its own `status` (menunggu/terverifikasi/ditolak) — "pending" = `menunggu`.

### DP (uang muka) rule — fixed nominal, not percent
DP is a **fixed rupiah amount** read from `pengaturan_sistem` key `dp_minimal` (currently Rp 200.000), NOT a percentage. The customer form's `uang_muka` input is ignored on the server — `PublicController::ajukanPesanan` always recomputes DP from settings and rejects the order if the DP ≥ total credit price. The whole credit calc (DP, `sisa_pokok`, installment amount, schedule) lives in `CreditCalculatorService` (Flat Rate margin); both `PublicController` and the admin/credit services call it so simulation and the real transaction stay consistent.

### Services layer (`app/Services/`)
Business logic lives here, not in controllers: `CreditCalculatorService` (flat-rate math + schedule generation), `CreditTransactionService` (create kredit + schedule, in a DB transaction), `PaymentService` (record installment payments), `WhatsAppTemplateService` (builds `wa.me` URLs + logs), `EmailNotificationService` (SMTP, fire-and-forget). **Email/WhatsApp must never break the main flow** — every call is wrapped in try/catch and only logged on failure.

### WhatsApp & Email are manual / best-effort
WhatsApp is link-mode only (`wa.me`, no gateway/token): the app builds a pre-filled URL and the admin sends it themselves; sends are logged in `whatsapp_logs`. Email (SMTP) auto-sends at 3 points (order created / verified / payment verified) but silently logs failure to `email_logs` if `email.SMTPHost` is unset.

### Routes & migrations as source of truth
`app/Config/Routes.php` has `setAutoRoute(false)` — every endpoint is explicit; read it to know what exists. Schema is the sum of `app/Database/Migrations/` (timestamped, additive — later `Alter*`/`Add*` migrations modify earlier tables, e.g. the `dp` enum value was added to `bukti_pembayaran.tipe` in a later migration). `database/mahengold_demo.sql` is a snapshot the setup scripts import as a fallback if seeding yields zero products.

### Conventions
- Helper functions (`format_rupiah`, `generate_kode`, `wa_number_normalize`, `current_admin`/`current_pelanggan`, etc.) are in `app/Helpers/mahen_helper.php` and globally available — use them rather than re-implementing.
- `generate_kode('PREFIX', $id)` produces codes like `KRD0001`; rows are typically inserted with a `PENDING` code then updated to the real one using the new id.
- Uploaded files are served through controller actions scoped to the owner (e.g. `AkunController::ktp`/`bukti` check `user_id`), never linked directly from `writable/`.
- Seeders are idempotent / auto-refreshing (re-running setup re-activates demo products); don't assume a fresh DB.
- `.env` is **git-ignored** — never commit it; copy from `.env.example`. On macOS, DB host must be `127.0.0.1` (TCP), not `localhost` (socket).

### Demo admin login
`admin@mahengold.test` / `admin123`.

## Gotcha: README "Batasan Sistem" is stale
The README's "Batasan Sistem" section (no customer login, no payment-proof upload, no payment verification) describes the *original proposal scope* — the implemented app has **since added all three** (customer accounts, `bukti_pembayaran` upload, admin verification). Trust the code, not that list.
