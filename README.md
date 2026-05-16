# MahenGold

Sistem Informasi Penjualan dan Kredit Emas Berbasis Web pada Toko MahenGold.
Dibangun dengan **CodeIgniter 4**, **PHP 8**, **MariaDB/MySQL**, dan **Bootstrap 5**.

Repository: https://github.com/ryuken25/gold

---

## Cara Instalasi

### 1. Prasyarat

Pastikan komputer Anda sudah terpasang:

- **PHP 8.1+** dengan ekstensi: `intl`, `mbstring`, `mysqlnd`, `curl`, `gd`, `xml`
- **Composer** (https://getcomposer.org/)
- **MySQL / MariaDB** (XAMPP, Laragon, WAMP, atau standalone)
- **Git** (https://git-scm.com/)

Cek versi PHP dan Composer:

```bash
php -v
composer -V
```

### 2. Clone Repository

```bash
git clone https://github.com/ryuken25/gold.git
cd gold
```

### 3. Install Dependency

```bash
composer install
```

### 4. Konfigurasi `.env`

File `.env` sudah disertakan di repo (karena project full localhost, tidak ada credential production).
Edit bila perlu menyesuaikan database lokal Anda:

```env
database.default.hostname = localhost
database.default.database = mahengold_demo
database.default.username = root
database.default.password =
database.default.DBDriver  = MySQLi
```

Jika belum ada `.env`, salin dari template:

- Windows (CMD): `copy env .env`
- PowerShell:    `Copy-Item env .env`
- Linux/macOS:   `cp env .env`

### 5. Buat Database

Buat database baru (misalnya `mahengold_demo`) lewat phpMyAdmin / HeidiSQL / CLI:

```sql
CREATE DATABASE mahengold_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

### 6. Migrasi & Seeder

```bash
php spark migrate:fresh --seed
```

### 7. Download Asset Gambar MahenGold

```bash
php spark assets:download-mahengold
```

Gunakan flag `--force` untuk overwrite asset yang sudah ada.

### 8. Jalankan Server

```bash
php spark serve
```

Buka di browser: **http://127.0.0.1:8080**

---

## Akun Demo Admin

| Field    | Nilai                  |
| -------- | ---------------------- |
| Username | `admin`                |
| Email    | `admin@mahengold.test` |
| Password | `admin123`             |

Login admin di: http://127.0.0.1:8080/admin/login

---

## Fitur Utama

### Public / Guest
- Landing page premium MahenGold
- Katalog produk emas
- Detail produk
- Simulasi kredit Flat Rate
- Form pengajuan via WhatsApp (tanpa login pelanggan)
- Template `wa.me` dengan preview pesan otomatis

### Admin
- Login admin
- Dashboard ringkasan kredit, pembayaran, piutang, jatuh tempo, dan log WhatsApp
- CRUD produk emas
- CRUD nasabah
- Transaksi kredit + generate jadwal angsuran otomatis
- Pencatatan pembayaran angsuran (manual oleh admin)
- Monitoring piutang
- Laporan kredit, pembayaran, dan piutang
- Pengaturan sistem
- WhatsApp logs & template generator

---

## Route Utama

### Public
- `/` — landing
- `/katalog` — daftar produk
- `/produk/{kode_produk}` — detail produk
- `/simulasi` — simulasi kredit
- `/wa/pengajuan` — pengajuan via WhatsApp

### Admin
- `/admin/login`, `/admin/logout`
- `/admin/dashboard`
- `/admin/produk`
- `/admin/nasabah`
- `/admin/kredit`
- `/admin/pembayaran`
- `/admin/piutang`
- `/admin/laporan/kredit`
- `/admin/laporan/pembayaran`
- `/admin/laporan/piutang`
- `/admin/whatsapp-logs`
- `/admin/pengaturan`

### API Internal
- `/api/kredit/preview`
- `/api/produk/{id}/simulasi`
- `/api/referensi-harga-emas`

---

## Konfigurasi WhatsApp

Pengaturan default di `.env`:

```env
WA_MODE=link
WA_TARGET_NUMBER=6282146575233
```

Mode `link` menggunakan `wa.me/...` (tidak butuh token).
Cloud API hanya placeholder; fallback otomatis ke template `wa.me` bila token belum diisi.

---

## Struktur Database

Migration tersedia di [`app/Database/Migrations/`](app/Database/Migrations/):
- `CreateUsersTable`
- `CreateProdukEmasTable`
- `CreateNasabahTable`
- `CreateKreditTable`
- `CreateJadwalAngsuranTable`
- `CreatePembayaranAngsuranTable`
- `CreateWhatsappLogsTable`
- `CreatePengaturanSistemTable`

Seeder utama: `DatabaseSeeder`, `MahenGoldSeeder`.

---

## Service Penting

- `CreditCalculatorService` — simulasi kredit Flat Rate
- `CreditTransactionService` — pembuatan transaksi kredit
- `PaymentService` — pencatatan pembayaran angsuran
- `WhatsAppTemplateService` — template WA dan log pesan

---

## Troubleshooting

**Error `intl extension is required`**
Aktifkan `extension=intl` di `php.ini`, lalu restart server.

**`spark` tidak bisa dijalankan**
Pastikan dijalankan dari root project, bukan dari subfolder.

**Database connection refused**
Cek service MySQL/MariaDB aktif, dan kredensial di `.env` sesuai.

**Asset gambar tidak muncul**
Jalankan ulang `php spark assets:download-mahengold --force`. Bila tidak ada internet, command otomatis membuat fallback SVG lokal.

---

## Batasan Sistem (sesuai proposal)

- Tidak ada login pelanggan
- Tidak ada upload bukti pembayaran
- Tidak ada verifikasi pembayaran otomatis
- Tidak ada payment gateway
- WhatsApp default via template `wa.me`
- Laporan hanya ringkasan/list
- Dokumen legal/perjanjian kredit berada di luar sistem

---

## Lisensi

MIT — lihat [LICENSE](LICENSE).
