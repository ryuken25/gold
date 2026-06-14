<?php

namespace App\Database\Seeds;

use App\Models\BuktiPembayaranModel;
use App\Models\JadwalAngsuranModel;
use App\Models\KreditModel;
use App\Models\NasabahModel;
use App\Models\PembayaranAngsuranModel;
use App\Models\PengajuanAktivitasModel;
use App\Models\PengajuanModel;
use App\Models\PengaturanSistemModel;
use App\Models\ProdukEmasModel;
use App\Models\UserModel;
use App\Services\CreditTransactionService;
use App\Services\CreditCalculatorService;
use CodeIgniter\Database\Seeder;
use DateInterval;
use DateTimeImmutable;

class MahenGoldSeeder extends Seeder
{
    protected UserModel $userModel;

    protected ProdukEmasModel $produkModel;

    protected NasabahModel $nasabahModel;

    protected KreditModel $kreditModel;

    protected JadwalAngsuranModel $jadwalModel;

    protected PembayaranAngsuranModel $pembayaranModel;

    protected PengaturanSistemModel $pengaturanModel;

    protected CreditCalculatorService $calculator;

    public function run()
    {
        helper('mahen');

        $this->userModel = new UserModel();
        $this->produkModel = new ProdukEmasModel();
        $this->nasabahModel = new NasabahModel();
        $this->kreditModel = new KreditModel();
        $this->jadwalModel = new JadwalAngsuranModel();
        $this->pembayaranModel = new PembayaranAngsuranModel();
        $this->pengaturanModel = new PengaturanSistemModel();
        $this->calculator = new CreditCalculatorService();

        // ---- Idempotent per-item: aman dijalankan berkali-kali, dan SELALU
        //      memastikan admin + produk demo ada (top-up bila hilang/parsial).

        // 1. Admin (buat bila belum ada).
        $admin = $this->userModel->where('email', 'admin@mahengold.test')->first();
        $adminId = $admin
            ? (int) $admin['id']
            : (int) $this->userModel->insert([
                'nama' => 'Administrator MahenGold',
                'email' => 'admin@mahengold.test',
                'username' => 'admin',
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'admin',
                'is_active' => 1,
            ], true);

        // 2. Pengaturan (sekali, bila tabel kosong).
        if ($this->pengaturanModel->countAllResults() === 0) {
            $this->pengaturanModel->insert([
                'nama_toko' => 'MahenGold',
                'nomor_whatsapp_toko' => '6282146575233',
                'margin_default' => 10.00,
                'logo_text' => 'MG',
                'alamat_toko' => 'Denpasar, Bali',
            ]);
        }

        // 3. Produk demo — AUTO-REFRESH tiap seed: 6 produk dipastikan ada DAN
        //    aktif (di-restore bila sempat dihapus/nonaktif), data disamakan.
        foreach ([
            ['kode_produk' => 'MGD-001', 'nama_produk' => 'Cincin Emas 1 Gram', 'jenis_emas' => 'Perhiasan', 'kadar' => '22K', 'berat_gram' => 1.00, 'harga_pokok' => 1500000, 'stok' => 5],
            ['kode_produk' => 'MGD-002', 'nama_produk' => 'Kalung Emas 2 Gram', 'jenis_emas' => 'Perhiasan', 'kadar' => '22K', 'berat_gram' => 2.00, 'harga_pokok' => 3200000, 'stok' => 3],
            ['kode_produk' => 'MGD-003', 'nama_produk' => 'Anting Emas 0.8 Gram', 'jenis_emas' => 'Perhiasan', 'kadar' => '22K', 'berat_gram' => 0.80, 'harga_pokok' => 1250000, 'stok' => 8],
            ['kode_produk' => 'MGD-004', 'nama_produk' => 'Gelang Emas 3 Gram', 'jenis_emas' => 'Perhiasan', 'kadar' => '22K', 'berat_gram' => 3.00, 'harga_pokok' => 4800000, 'stok' => 4],
            ['kode_produk' => 'MGD-005', 'nama_produk' => 'Logam Mulia 5 Gram', 'jenis_emas' => 'Logam Mulia', 'kadar' => '24K', 'berat_gram' => 5.00, 'harga_pokok' => 7500000, 'stok' => 6],
            ['kode_produk' => 'MGD-006', 'nama_produk' => 'Liontin Emas 1.5 Gram', 'jenis_emas' => 'Perhiasan', 'kadar' => '22K', 'berat_gram' => 1.50, 'harga_pokok' => 2300000, 'stok' => 7],
        ] as $produk) {
            $produk['status'] = 'aktif';
            $produk['deskripsi'] = 'Produk emas premium MahenGold untuk kebutuhan investasi dan perhiasan.';

            // withDeleted() supaya produk yang ter-soft-delete pun ketemu & dipulihkan.
            $existing = $this->produkModel->withDeleted()->where('kode_produk', $produk['kode_produk'])->first();
            if ($existing) {
                $this->produkModel->update($existing['id'], $produk);
                if (!empty($existing['deleted_at'])) {
                    $this->produkModel->builder()->where('id', $existing['id'])->update(['deleted_at' => null]);
                }
                continue;
            }
            $produk['gambar_url'] = null;
            $this->produkModel->insert($produk);
        }

        // 4. Data transaksi demo (nasabah + kredit sisi admin) — hanya bila
        //    belum ada kredit sama sekali. Opsional: jangan gagalkan seeder.
        if ($this->kreditModel->countAllResults() === 0) {
            $produkIds = [];
            foreach (['MGD-001', 'MGD-002', 'MGD-003'] as $kode) {
                $row = $this->produkModel->where('kode_produk', $kode)->first();
                if ($row) {
                    $produkIds[] = (int) $row['id'];
                }
            }

            if (count($produkIds) === 3) {
                $nasabahIds = [];
                foreach ([
                    ['nama' => 'Ayu Lestari', 'no_telepon' => '6281234567890', 'alamat' => 'Denpasar'],
                    ['nama' => 'Kadek Surya', 'no_telepon' => '6289876543210', 'alamat' => 'Badung'],
                    ['nama' => 'Ni Putu Sari', 'no_telepon' => '6281112223334', 'alamat' => 'Gianyar'],
                ] as $index => $nasabah) {
                    $kode = generate_kode('NSB', $index + 1);
                    $existing = $this->nasabahModel->where('kode_nasabah', $kode)->first();
                    if ($existing) {
                        $nasabahIds[] = (int) $existing['id'];
                        continue;
                    }
                    $nasabah['kode_nasabah'] = $kode;
                    $nasabah['catatan'] = 'Data nasabah dummy demo MahenGold.';
                    $nasabahIds[] = (int) $this->nasabahModel->insert($nasabah, true);
                }

                $today = new DateTimeImmutable('today');

                try {
                    $this->seedCredit($adminId, $nasabahIds[0], $produkIds[0], $today->sub(new DateInterval('P70D'))->format('Y-m-d'), $today->sub(new DateInterval('P40D'))->format('Y-m-d'), 12, 'bulanan', 2, 0, 'aktif');
                    $this->seedCredit($adminId, $nasabahIds[1], $produkIds[1], $today->sub(new DateInterval('P35D'))->format('Y-m-d'), $today->sub(new DateInterval('P21D'))->format('Y-m-d'), 10, 'mingguan', 5, 0, 'aktif');
                    $this->seedCredit($adminId, $nasabahIds[2], $produkIds[2], $today->sub(new DateInterval('P210D'))->format('Y-m-d'), $today->sub(new DateInterval('P180D'))->format('Y-m-d'), 6, 'bulanan', 6, 0, 'lunas');
                } catch (\Throwable $e) {
                    log_message('error', 'Seed transaksi demo gagal (produk & admin tetap ada): ' . $e->getMessage());
                }
            }
        }

        // 5. Demo PESANAN pelanggan + bukti pembayaran (cash & DP) supaya alur
        //    upload bukti + verifikasi admin langsung tampil di data demo.
        //    Idempotent: hanya dibuat sekali (lihat guard di dalam method).
        try {
            $this->seedPesananDemo($adminId);
        } catch (\Throwable $e) {
            log_message('error', 'Seed pesanan/DP demo gagal (data inti tetap ada): ' . $e->getMessage());
        }
    }

    /**
     * Buat akun pelanggan demo + beberapa pesanan yang mencakup semua state
     * alur bukti pembayaran: pengajuan baru, DP menunggu (pending), DP
     * terverifikasi, dan cash menunggu. Plus file gambar bukti/KTP demo.
     */
    protected function seedPesananDemo(int $adminId): void
    {
        $pengajuanModel = new PengajuanModel();
        $buktiModel     = new BuktiPembayaranModel();
        $aktivitasModel = new PengajuanAktivitasModel();

        // Pelanggan demo (kredensial dipakai juga oleh generator laporan BAB IV).
        $pelanggan = $this->userModel->where('email', 'demo.pelanggan@mahengold.test')->first();
        $userId = $pelanggan
            ? (int) $pelanggan['id']
            : (int) $this->userModel->insert([
                'nama'          => 'Putu Demo Pelanggan',
                'email'         => 'demo.pelanggan@mahengold.test',
                'username'      => null,
                'no_telepon'    => '6281200000001',
                'password_hash' => password_hash('demo1234', PASSWORD_DEFAULT),
                'role'          => 'pelanggan',
                'is_active'     => 1,
            ], true);

        // Guard idempotent: kalau pelanggan demo sudah punya pesanan, lewati.
        if ($pengajuanModel->where('user_id', $userId)->countAllResults() > 0) {
            return;
        }

        $prod = function (string $kode): ?array {
            return $this->produkModel->where('kode_produk', $kode)->first();
        };
        $alamat = 'Jl. Tunjung Sari No. 12, Denpasar, Bali';
        $telp   = '6281200000001';
        $dp     = (int) ($this->pengaturanModel->getPengaturan()['dp_minimal'] ?? 200000);

        // File gambar demo (KTP + bukti) supaya tombol "Lihat" tidak 404.
        $ktpFile = 'demo_ktp.png';
        $this->writeDemoImage(WRITEPATH . 'uploads/ktp/' . $ktpFile, 'KTP DEMO', 'Putu Demo Pelanggan');

        // --- A. Pesanan KREDIT status "baru" + DP bukti MENUNGGU ------------
        //     (bukti DP diunggah saat pengajuan; belum diverifikasi admin)
        if ($p = $prod('MGD-001')) {
            $id = (int) $pengajuanModel->insert([
                'kode_pesanan'      => 'MG-DEMO-001',
                'user_id'           => $userId,
                'produk_emas_id'    => (int) $p['id'],
                'metode_pembayaran' => 'kredit',
                'nama'              => 'Putu Demo Pelanggan',
                'no_telepon'        => $telp,
                'alamat'            => $alamat,
                'tenor_bulan'       => 12,
                'periode_angsuran'  => 'bulanan',
                'uang_muka'         => $dp,
                'foto_ktp'          => $ktpFile,
                'status'            => 'baru',
                'pembayaran_status' => 'menunggu',
            ], true);
            $aktivitasModel->log($id, 'dibuat', 'Pesanan dibuat oleh pelanggan', 'pelanggan');

            $file = 'demo_bukti_dp_baru.png';
            $this->writeDemoImage(WRITEPATH . 'uploads/bukti/' . $file, 'BUKTI DP - MENUNGGU (BARU)', 'MG-DEMO-001');
            $bid = (int) $buktiModel->insert([
                'tipe'          => 'dp',
                'pengajuan_id'  => $id,
                'user_id'       => $userId,
                'nominal'       => $dp,
                'nama_pengirim' => 'Putu Demo Pelanggan',
                'no_rekening'   => '1234567890',
                'bank_pengirim' => 'BCA',
                'file_path'     => $file,
                'status'        => 'menunggu',
            ], true);
            $buktiModel->update($bid, ['kode' => generate_kode('BKT', $bid)]);
        }

        // --- B. Pesanan KREDIT disetujui + DP bukti MENUNGGU (pending) ------
        if ($p = $prod('MGD-003')) {
            $id = (int) $pengajuanModel->insert([
                'kode_pesanan'      => 'MG-DEMO-002',
                'user_id'           => $userId,
                'produk_emas_id'    => (int) $p['id'],
                'metode_pembayaran' => 'kredit',
                'nama'              => 'Putu Demo Pelanggan',
                'no_telepon'        => $telp,
                'alamat'            => $alamat,
                'tenor_bulan'       => 6,
                'periode_angsuran'  => 'bulanan',
                'uang_muka'         => $dp,
                'foto_ktp'          => $ktpFile,
                'status'            => 'disetujui',
                'pembayaran_status' => 'menunggu',
            ], true);
            $aktivitasModel->log($id, 'dibuat', 'Pesanan dibuat oleh pelanggan', 'pelanggan');
            $aktivitasModel->log($id, 'diverifikasi', 'Pesanan disetujui admin.', 'Administrator MahenGold');
            $this->buatKreditDariPengajuan($pengajuanModel, $aktivitasModel, $id);

            $file = 'demo_bukti_dp_pending.png';
            $this->writeDemoImage(WRITEPATH . 'uploads/bukti/' . $file, 'BUKTI DP - MENUNGGU', 'MG-DEMO-002');
            $bid = (int) $buktiModel->insert([
                'tipe'          => 'dp',
                'pengajuan_id'  => $id,
                'user_id'       => $userId,
                'nominal'       => $dp,
                'nama_pengirim' => 'Putu Demo Pelanggan',
                'no_rekening'   => '1234567890',
                'bank_pengirim' => 'BCA',
                'file_path'     => $file,
                'status'        => 'menunggu',
            ], true);
            $buktiModel->update($bid, ['kode' => generate_kode('BKT', $bid)]);
        }

        // --- C. Pesanan KREDIT disetujui + DP bukti TERVERIFIKASI -----------
        if ($p = $prod('MGD-002')) {
            $id = (int) $pengajuanModel->insert([
                'kode_pesanan'      => 'MG-DEMO-003',
                'user_id'           => $userId,
                'produk_emas_id'    => (int) $p['id'],
                'metode_pembayaran' => 'kredit',
                'nama'              => 'Putu Demo Pelanggan',
                'no_telepon'        => $telp,
                'alamat'            => $alamat,
                'tenor_bulan'       => 10,
                'periode_angsuran'  => 'mingguan',
                'uang_muka'         => $dp,
                'foto_ktp'          => $ktpFile,
                'status'            => 'disetujui',
                'pembayaran_status' => 'terverifikasi',
            ], true);
            $aktivitasModel->log($id, 'dibuat', 'Pesanan dibuat oleh pelanggan', 'pelanggan');
            $aktivitasModel->log($id, 'diverifikasi', 'Pesanan disetujui admin.', 'Administrator MahenGold');
            $this->buatKreditDariPengajuan($pengajuanModel, $aktivitasModel, $id);

            $file = 'demo_bukti_dp_verified.png';
            $this->writeDemoImage(WRITEPATH . 'uploads/bukti/' . $file, 'BUKTI DP - TERVERIFIKASI', 'MG-DEMO-003');
            $bid = (int) $buktiModel->insert([
                'tipe'              => 'dp',
                'pengajuan_id'      => $id,
                'user_id'           => $userId,
                'nominal'           => $dp,
                'nama_pengirim'     => 'Putu Demo Pelanggan',
                'no_rekening'       => '1234567890',
                'bank_pengirim'     => 'BCA',
                'file_path'         => $file,
                'status'            => 'terverifikasi',
                'diverifikasi_oleh' => $adminId,
                'diverifikasi_pada' => date('Y-m-d H:i:s'),
            ], true);
            $buktiModel->update($bid, ['kode' => generate_kode('BKT', $bid)]);
        }

        // --- D. Pesanan CASH disetujui + bukti cash MENUNGGU ----------------
        if ($p = $prod('MGD-006')) {
            $id = (int) $pengajuanModel->insert([
                'kode_pesanan'      => 'MG-DEMO-004',
                'user_id'           => $userId,
                'produk_emas_id'    => (int) $p['id'],
                'metode_pembayaran' => 'cash',
                'nama'              => 'Putu Demo Pelanggan',
                'no_telepon'        => $telp,
                'alamat'            => $alamat,
                'status'            => 'disetujui',
                'pembayaran_status' => 'menunggu',
            ], true);
            $aktivitasModel->log($id, 'dibuat', 'Pesanan dibuat oleh pelanggan', 'pelanggan');
            $aktivitasModel->log($id, 'diverifikasi', 'Pesanan disetujui admin.', 'Administrator MahenGold');

            $file = 'demo_bukti_cash.png';
            $this->writeDemoImage(WRITEPATH . 'uploads/bukti/' . $file, 'BUKTI CASH - MENUNGGU', 'MG-DEMO-004');
            $bid = (int) $buktiModel->insert([
                'tipe'          => 'cash',
                'pengajuan_id'  => $id,
                'user_id'       => $userId,
                'nominal'       => (int) $p['harga_pokok'],
                'nama_pengirim' => 'Putu Demo Pelanggan',
                'no_rekening'   => '1234567890',
                'bank_pengirim' => 'BCA',
                'file_path'     => $file,
                'status'        => 'menunggu',
            ], true);
            $buktiModel->update($bid, ['kode' => generate_kode('BKT', $bid)]);
        }
    }

    /**
     * Bentuk kredit + jadwal angsuran otomatis dari sebuah pengajuan (meniru
     * aksi admin "verifikasi"). Aman bila pengajuan tidak ditemukan.
     */
    protected function buatKreditDariPengajuan(PengajuanModel $pengajuanModel, PengajuanAktivitasModel $aktivitasModel, int $pengajuanId): void
    {
        $pengajuan = $pengajuanModel->find($pengajuanId);
        if (!$pengajuan) {
            return;
        }
        $hasil = (new CreditTransactionService())->createFromPengajuan($pengajuan, 10.00);
        if (!empty($hasil['kredit'])) {
            $aktivitasModel->log($pengajuanId, 'kredit_dibuat', 'Kredit otomatis dibuat: ' . $hasil['kredit']['kode_kredit'], 'Administrator MahenGold');
        }
    }

    /**
     * Tulis gambar bukti/KTP demo sederhana (GD) ke writable/uploads. Bila GD
     * tidak tersedia, fallback ke PNG minimal 1x1. Idempotent (skip bila ada).
     */
    protected function writeDemoImage(string $path, string $judul, string $sub): void
    {
        if (is_file($path)) {
            return;
        }
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (!is_file($dir . '/index.html')) {
            @file_put_contents($dir . '/index.html', '');
        }

        if (function_exists('imagecreatetruecolor')) {
            $img  = imagecreatetruecolor(640, 400);
            $bg   = imagecolorallocate($img, 28, 26, 23);
            $gold = imagecolorallocate($img, 201, 162, 75);
            $soft = imagecolorallocate($img, 156, 147, 133);
            imagefilledrectangle($img, 0, 0, 640, 400, $bg);
            imagefilledrectangle($img, 0, 0, 640, 64, imagecolorallocate($img, 18, 17, 15));
            imagestring($img, 5, 24, 24, 'MahenGold', $gold);
            imagestring($img, 4, 24, 150, $judul, $gold);
            imagestring($img, 3, 24, 185, 'Ref: ' . $sub, $soft);
            imagestring($img, 2, 24, 360, 'Gambar contoh data demo (bukan transaksi nyata).', $soft);
            imagepng($img, $path);
            imagedestroy($img);
            return;
        }

        // Fallback: PNG 1x1 valid.
        @file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
    }

    protected function seedCredit(
        int $adminId,
        int $nasabahId,
        int $produkId,
        string $tanggalKredit,
        string $tanggalJatuhTempoPertama,
        int $tenorBulan,
        string $periode,
        int $angsuranTerbayar,
        int $nominalTambahan,
        string $forcedStatus = 'aktif'
    ): void {
        $produk = $this->produkModel->find($produkId);
        $kalkulasi = $this->calculator->calculate($produk['harga_pokok'], 10, $tenorBulan, $periode);

        $kreditId = $this->kreditModel->insert([
            'kode_kredit' => generate_kode('KRD', $this->kreditModel->countAllResults() + 1),
            'nasabah_id' => $nasabahId,
            'produk_emas_id' => $produkId,
            'tanggal_kredit' => $tanggalKredit,
            'harga_pokok_snapshot' => $kalkulasi['harga_pokok'],
            'margin_persen' => $kalkulasi['margin_persen'],
            'margin_nominal' => $kalkulasi['margin_nominal'],
            'total_harga_kredit' => $kalkulasi['total_harga_kredit'],
            'tenor_bulan' => $tenorBulan,
            'periode_angsuran' => $periode,
            'jumlah_periode' => $kalkulasi['jumlah_periode'],
            'nominal_angsuran' => $kalkulasi['nominal_angsuran'],
            'total_terbayar' => 0,
            'sisa_piutang' => $kalkulasi['total_harga_kredit'],
            'status' => 'aktif',
            'catatan' => 'Data transaksi kredit dummy untuk demo.',
        ], true);

        $jadwal = $this->calculator->generateSchedule($tanggalJatuhTempoPertama, $kalkulasi);
        foreach ($jadwal as &$item) {
            $item['kredit_id'] = $kreditId;
        }
        unset($item);
        $this->jadwalModel->insertBatch($jadwal);

        $schedules = $this->jadwalModel->where('kredit_id', $kreditId)->orderBy('angsuran_ke', 'ASC')->findAll();

        $totalTerbayar = 0;
        foreach ($schedules as $index => $schedule) {
            if ($index + 1 > $angsuranTerbayar) {
                break;
            }

            $nominalBayar = (int) round((float) $schedule['nominal_tagihan']);
            $tanggalBayar = new DateTimeImmutable($schedule['tanggal_jatuh_tempo']);
            $this->pembayaranModel->insert([
                'kode_pembayaran' => generate_kode('BYR', $this->pembayaranModel->countAllResults() + 1),
                'kredit_id' => $kreditId,
                'jadwal_angsuran_id' => $schedule['id'],
                'tanggal_bayar' => $tanggalBayar->format('Y-m-d'),
                'nominal_bayar' => $nominalBayar,
                'metode_pembayaran' => 'transfer',
                'keterangan' => 'Pembayaran dummy demo.',
                'dicatat_oleh' => $adminId,
            ]);

            $this->jadwalModel->update($schedule['id'], [
                'nominal_dibayar' => $nominalBayar,
                'status' => 'dibayar',
                'tanggal_dibayar' => $tanggalBayar->format('Y-m-d'),
            ]);
            $totalTerbayar += $nominalBayar;
        }

        if ($nominalTambahan > 0 && isset($schedules[$angsuranTerbayar])) {
            $schedule = $schedules[$angsuranTerbayar];
            $this->pembayaranModel->insert([
                'kode_pembayaran' => generate_kode('BYR', $this->pembayaranModel->countAllResults() + 1),
                'kredit_id' => $kreditId,
                'jadwal_angsuran_id' => $schedule['id'],
                'tanggal_bayar' => date('Y-m-d'),
                'nominal_bayar' => $nominalTambahan,
                'metode_pembayaran' => 'cash',
                'keterangan' => 'Pembayaran parsial dummy.',
                'dicatat_oleh' => $adminId,
            ]);

            $status = $nominalTambahan >= $schedule['nominal_tagihan'] ? 'dibayar' : 'sebagian';
            $this->jadwalModel->update($schedule['id'], [
                'nominal_dibayar' => $nominalTambahan,
                'status' => $status,
                'tanggal_dibayar' => date('Y-m-d'),
            ]);
            $totalTerbayar += $nominalTambahan;
        }

        $status = $forcedStatus;
        $sisaPiutang = max(0, $kalkulasi['total_harga_kredit'] - $totalTerbayar);
        if ($forcedStatus !== 'lunas' && $sisaPiutang <= 0) {
            $status = 'lunas';
        }

        if ($forcedStatus === 'lunas') {
            $status = 'lunas';
            $totalTerbayar = $kalkulasi['total_harga_kredit'];
            $sisaPiutang = 0;
            foreach ($schedules as $schedule) {
                $this->jadwalModel->update($schedule['id'], [
                    'nominal_dibayar' => $schedule['nominal_tagihan'],
                    'status' => 'dibayar',
                    'tanggal_dibayar' => $schedule['tanggal_jatuh_tempo'],
                ]);
            }
        }

        $this->kreditModel->update($kreditId, [
            'total_terbayar' => $totalTerbayar,
            'sisa_piutang' => $sisaPiutang,
            'status' => $status,
        ]);

        $this->produkModel->update($produkId, ['stok' => max(0, ((int) $produk['stok']) - 1)]);
    }
}
