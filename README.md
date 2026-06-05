# MahenGold

Sistem Informasi Penjualan dan Kredit Emas Berbasis Web pada Toko MahenGold.
Dibangun dengan **CodeIgniter 4**, **PHP 8**, **MariaDB/MySQL**, dan **Bootstrap 5**.

Repository: https://github.com/ryuken25/gold

---

## Cara Tercepat — 1x Klik (XAMPP)

Sudah pernah clone? Cukup jalankan satu file (otomatis: `git pull` + buat `.env`
+ start MySQL + buat DB + migrasi + seed + jalankan server):

- **Windows**: klik dua kali **`setup-windows.bat`** (atau jalankan di CMD).
- **Mac/Linux**: `bash setup-windows.sh`

Reset penuh (hapus DB + isi ulang data demo): tambahkan `fresh` →
`setup-windows.bat fresh` / `bash setup-windows.sh fresh`.

> **Soal `.env`:** `.env` **tidak di-track git** (biar `git pull` tidak pernah
> bentrok). File `.env` lokalmu aman — tidak akan diubah/replace. Saat pertama
> kali, script menyalin `.env.example` → `.env` otomatis bila belum ada.
> Jadi alurnya cukup: `git pull` lalu jalankan `setup-windows.*`.

---

## Cara Instalasi (Manual)

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

`.env` **tidak di-track git** (supaya `git pull` tidak pernah bentrok). Salin
dari `.env.example` (sudah berisi konfigurasi localhost siap pakai):

- Windows (CMD): `copy .env.example .env`
- PowerShell:    `Copy-Item .env.example .env`
- Linux/macOS:   `cp .env.example .env`

Edit bila perlu menyesuaikan database lokal Anda:

```env
database.default.hostname = localhost
database.default.database = mahengold_demo
database.default.username = root
database.default.password =
database.default.DBDriver  = MySQLi
```

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

## Login (admin & pelanggan disatukan)

Login memakai **satu halaman** `http://127.0.0.1:8080/login` untuk semua akun.
Sistem mengarahkan otomatis berdasarkan **role**:
- `role = admin` → panel admin (`/admin/dashboard`)
- `role = pelanggan` → akun pelanggan (`/akun`)

Pelanggan mendaftar sendiri lewat `/register` (otomatis `role = pelanggan`).

### Akun Demo Admin

| Field    | Nilai                  |
| -------- | ---------------------- |
| Email    | `admin@mahengold.test` |
| Password | `admin123`             |

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
- `/simulasi` — simulasi kredit (kalkulasi angsuran)
- `/pesanan` — kirim pesanan (in-system, butuh login pelanggan)
- `/akun/pesanan` — riwayat & status pesanan pelanggan

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

Mode `link` menggunakan `wa.me/...` (**tidak butuh token/gateway**).

WhatsApp dipakai **manual oleh admin**: klik tombol akan membuka chat ke nomor
pelanggan dengan template terisi, lalu admin kirim sendiri. Tersedia di:
- Detail pengajuan (`/admin/pengajuan/{id}`) — konfirmasi pesanan & info tenor.
- Queue pembayaran (`/admin/pembayaran`) — tombol **Kirim WA** pada pembayaran
  yang sudah terverifikasi (konfirmasi pembayaran ke pelanggan).

---

## Notifikasi Email (SMTP)

Sistem mengirim **email otomatis** (pengirim "Mahen Gold") pada 3 momen:
1. **Pesanan dibuat** — pelanggan menerima ringkasan + status menunggu verifikasi.
2. **Pesanan diverifikasi** — konfirmasi disetujui + arahan pembayaran di `/akun`.
3. **Pembayaran terverifikasi** — konfirmasi pembayaran (untuk kredit: sisa
   piutang & status lunas).

### Konfigurasi SMTP (Gmail)

Aktifkan 2FA di akun Gmail, buat **App Password** 16 karakter di
https://myaccount.google.com/apppasswords, lalu isi di `.env`:

```env
email.fromEmail = 'mahengold.notif@gmail.com'
email.fromName  = 'Mahen Gold'
email.protocol  = smtp
email.SMTPHost  = smtp.gmail.com
email.SMTPUser  = 'mahengold.notif@gmail.com'
email.SMTPPass  = 'xxxxxxxxxxxxxxxx'
email.SMTPPort  = 587
email.SMTPCrypto = tls
email.mailType  = html
email.SMTPTimeout = 30
```

Selama `email.SMTPHost` kosong, email tidak terkirim (tercatat `gagal` di tabel
`email_logs`) **tetapi alur pesanan tetap berjalan normal**.

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
