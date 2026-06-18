<?php

namespace App\Database\Seeds;

use App\Models\PengaturanSistemModel;
use App\Models\ProdukEmasModel;
use App\Models\UserModel;
use App\Services\CreditCalculatorService;
use CodeIgniter\Database\Seeder;
use Config\Database;
use DateTimeImmutable;

class MahenGoldWorkflowSeeder extends Seeder
{
    public function run()
    {
        helper('mahen');

        $db = Database::connect();

        // Truncate workflow tables to ensure clean, idempotent seeding
        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        $db->table('pengajuan')->truncate();
        $db->table('pengajuan_aktivitas')->truncate();
        $db->table('kredit')->truncate();
        $db->table('jadwal_angsuran')->truncate();
        $db->table('pembayaran_angsuran')->truncate();
        $db->table('pembayaran_alokasi')->truncate();
        $db->table('bukti_pembayaran')->truncate();
        $db->table('reminder_angsuran_logs')->truncate();
        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        $today = new DateTimeImmutable('today');

        $pengaturan = (new PengaturanSistemModel())->getPengaturan();
        $dpMinimal  = (int) ($pengaturan['dp_minimal'] ?? 200000);
        $margin     = (float) ($pengaturan['margin_default'] ?? 10.00);

        // ============================================================
        // 1. USERS — idempotent by email
        // ============================================================
        $adminId       = $this->upsertUser($db, 'admin@mahengold.test');
        $staffVerifId  = $this->upsertUser($db, 'staff.verifikasi@mahengold.test');
        $staffFinId    = $this->upsertUser($db, 'staff.finance@mahengold.test');

        $devUserId     = $this->upsertUser($db, 'winayaarya@gmail.com');
        $nsbDevUser    = $this->upsertNasabah($db, $devUserId, 'I Wayan Zebec 1', '6281200000001');

        $pelangganId   = $this->upsertUser($db, 'demo.pelanggan@mahengold.test');
        $dpPendingId   = $this->upsertUser($db, 'demo.dp.pending@mahengold.test');
        $dpReadyId     = $this->upsertUser($db, 'demo.dp.ready@mahengold.test');
        $cashPendId    = $this->upsertUser($db, 'demo.cash.pending@mahengold.test');
        $cashReadyId   = $this->upsertUser($db, 'demo.cash.ready@mahengold.test');
        $shippedId     = $this->upsertUser($db, 'demo.shipped@mahengold.test');
        $doneId        = $this->upsertUser($db, 'demo.done@mahengold.test');
        $rejectedId    = $this->upsertUser($db, 'demo.rejected@mahengold.test');
        $overdueId     = $this->upsertUser($db, 'demo.overdue@mahengold.test');
        $lunasId       = $this->upsertUser($db, 'demo.lunas@mahengold.test');
        $otherId       = $this->upsertUser($db, 'demo.other@mahengold.test');

        // ============================================================
        // 2. NASABAH — idempotent by user_id
        // ============================================================
        $nsbPelanggan = $this->upsertNasabah($db, $pelangganId, 'Putu Demo Pelanggan', '6281200000001');
        $nsbDpPend    = $this->upsertNasabah($db, $dpPendingId, 'Made DP Pending', '6281200000002');
        $nsbDpReady   = $this->upsertNasabah($db, $dpReadyId, 'Ketut DP Ready', '6281200000003');
        $nsbCashPend  = $this->upsertNasabah($db, $cashPendId, 'Wayan Cash Pending', '6281200000004');
        $nsbCashReady = $this->upsertNasabah($db, $cashReadyId, 'Nyoman Cash Ready', '6281200000005');
        $nsbShipped   = $this->upsertNasabah($db, $shippedId, 'Komang Shipped', '6281200000006');
        $nsbDone      = $this->upsertNasabah($db, $doneId, 'Putu Done', '6281200000007');
        $nsbRejected  = $this->upsertNasabah($db, $rejectedId, 'Made Rejected', '6281200000008');
        $nsbOverdue   = $this->upsertNasabah($db, $overdueId, 'Ketut Overdue', '6281200000009');
        $nsbLunas     = $this->upsertNasabah($db, $lunasId, 'Wayan Lunas', '6281200000010');
        $nsbOther     = $this->upsertNasabah($db, $otherId, 'I Wayan Other', '6281200000011');

        // ============================================================
        // 3. PRODUK — get active products
        // ============================================================
        $produk = [];
        foreach (['MGD-001','MGD-002','MGD-003','MGD-004','MGD-005',
                   'MGD-006','MGD-007','MGD-008','MGD-009','MGD-010'] as $kode) {
            $row = $db->table('produk_emas')->where('kode_produk', $kode)->where('status', 'aktif')->get()->getRowArray();
            if ($row) {
                $produk[$kode] = $row;
            }
        }
        if (empty($produk)) {
            throw new \RuntimeException('Tidak ada produk aktif. Jalankan MahenGoldSeeder dulu.');
        }
        // Helper to pick first available product
        $pick = fn(array $codes) => $this->pickProduk($produk, $codes);

        $ktpFile = 'demo_ktp.png';
        $this->writeDemoImage(WRITEPATH . 'uploads/ktp/' . $ktpFile, 'KTP DEMO', 'Demo Pelanggan');

        // ============================================================
        // 4. PENGAJUAN SCENARIOS (idempotent by kode_pesanan)
        // ============================================================

        // A. MG-DEMO-WAIT-VERIFY: kredit, status baru, DP menunggu
        $pidA = $this->upsertPengajuan($db, [
            'kode_pesanan'      => 'MG-DEMO-WAIT-VERIFY',
            'user_id'           => $pelangganId,
            'produk_emas_id'    => $pick(['MGD-001','MGD-003']),
            'metode_pembayaran' => 'kredit',
            'nama'              => 'Putu Demo Pelanggan',
            'no_telepon'        => '6281200000001',
            'alamat'            => 'Jl. Tunjung Sari No. 12, Denpasar',
            'tenor_bulan'       => 12,
            'periode_angsuran'  => 'bulanan',
            'uang_muka'         => $dpMinimal,
            'foto_ktp'          => $ktpFile,
            'status'            => 'baru',
            'pembayaran_status' => 'menunggu',
        ]);
        $this->logAktivitas($db, $pidA, 'dibuat', 'Pesanan dibuat oleh pelanggan', 'pelanggan');
        $this->createBuktiDP($db, $pidA, $pelangganId, $dpMinimal, 'menunggu', $ktpFile);

        // B. MG-DEMO-DP-PENDING: kredit, disetujui, DP belum terverifikasi
        $pidB = $this->upsertPengajuan($db, [
            'kode_pesanan'      => 'MG-DEMO-DP-PENDING',
            'user_id'           => $dpPendingId,
            'produk_emas_id'    => $pick(['MGD-002','MGD-004']),
            'metode_pembayaran' => 'kredit',
            'nama'              => 'Made DP Pending',
            'no_telepon'        => '6281200000002',
            'alamat'            => 'Jl. Sunset Road No. 45, Denpasar',
            'tenor_bulan'       => 12,
            'periode_angsuran'  => 'bulanan',
            'uang_muka'         => $dpMinimal,
            'foto_ktp'          => $ktpFile,
            'status'            => 'disetujui',
            'pembayaran_status' => 'menunggu',
            'diverifikasi_pada' => $today->modify('-2 days')->format('Y-m-d H:i:s'),
            'diverifikasi_oleh' => $adminId,
        ]);
        $this->logAktivitas($db, $pidB, 'dibuat', 'Pesanan dibuat', 'pelanggan');
        $this->logAktivitas($db, $pidB, 'diverifikasi', 'Pesanan disetujui admin', 'admin');
        $this->buatKreditDariPengajuan($db, $pidB, $margin, $adminId);
        $this->createBuktiDP($db, $pidB, $dpPendingId, $dpMinimal, 'menunggu', $ktpFile);

        // C. MG-DEMO-DP-READY: kredit, disetujui, DP terverifikasi
        $pidC = $this->upsertPengajuan($db, [
            'kode_pesanan'      => 'MG-DEMO-DP-READY',
            'user_id'           => $dpReadyId,
            'produk_emas_id'    => $pick(['MGD-003','MGD-005']),
            'metode_pembayaran' => 'kredit',
            'nama'              => 'Ketut DP Ready',
            'no_telepon'        => '6281200000003',
            'alamat'            => 'Jl. Raya Kerobokan No. 88, Badung',
            'tenor_bulan'       => 6,
            'periode_angsuran'  => 'bulanan',
            'uang_muka'         => $dpMinimal,
            'foto_ktp'          => $ktpFile,
            'status'            => 'disetujui',
            'pembayaran_status' => 'terverifikasi',
            'diverifikasi_pada' => $today->modify('-3 days')->format('Y-m-d H:i:s'),
            'diverifikasi_oleh' => $adminId,
        ]);
        $this->logAktivitas($db, $pidC, 'dibuat', 'Pesanan dibuat', 'pelanggan');
        $this->logAktivitas($db, $pidC, 'diverifikasi', 'Pesanan disetujui admin', 'admin');
        $this->buatKreditDariPengajuan($db, $pidC, $margin, $adminId);
        $this->createBuktiDP($db, $pidC, $dpReadyId, $dpMinimal, 'terverifikasi', $ktpFile, $adminId);

        // D. MG-DEMO-NO-DP-READY: kredit DP 0, disetujui, siap kirim
        $pidD = $this->upsertPengajuan($db, [
            'kode_pesanan'      => 'MG-DEMO-NO-DP-READY',
            'user_id'           => $pelangganId,
            'produk_emas_id'    => $pick(['MGD-008']),
            'metode_pembayaran' => 'kredit',
            'nama'              => 'Putu Demo Pelanggan',
            'no_telepon'        => '6281200000001',
            'alamat'            => 'Jl. Tunjung Sari No. 12, Denpasar',
            'tenor_bulan'       => 6,
            'periode_angsuran'  => 'bulanan',
            'uang_muka'         => 0,
            'foto_ktp'          => $ktpFile,
            'status'            => 'disetujui',
            'pembayaran_status' => 'terverifikasi',
            'diverifikasi_pada' => $today->modify('-1 day')->format('Y-m-d H:i:s'),
            'diverifikasi_oleh' => $adminId,
        ]);
        $this->logAktivitas($db, $pidD, 'dibuat', 'Pesanan dibuat', 'pelanggan');
        $this->logAktivitas($db, $pidD, 'diverifikasi', 'Pesanan disetujui admin', 'admin');
        $this->buatKreditDariPengajuan($db, $pidD, $margin, $adminId);

        // E. MG-DEMO-CASH-PENDING: cash, pembayaran belum terverifikasi
        $pidE = $this->upsertPengajuan($db, [
            'kode_pesanan'      => 'MG-DEMO-CASH-PENDING',
            'user_id'           => $cashPendId,
            'produk_emas_id'    => $pick(['MGD-006']),
            'metode_pembayaran' => 'cash',
            'nama'              => 'Wayan Cash Pending',
            'no_telepon'        => '6281200000004',
            'alamat'            => 'Jl. Seminyak No. 22, Badung',
            'status'            => 'disetujui',
            'pembayaran_status' => 'menunggu',
            'diverifikasi_pada' => $today->modify('-1 day')->format('Y-m-d H:i:s'),
            'diverifikasi_oleh' => $adminId,
        ]);
        $this->logAktivitas($db, $pidE, 'dibuat', 'Pesanan dibuat', 'pelanggan');
        $this->logAktivitas($db, $pidE, 'diverifikasi', 'Pesanan disetujui admin', 'admin');
        $this->createBuktiCash($db, $pidE, $cashPendId, $this->getProdukHarga($produk, 'MGD-006'), 'menunggu');

        // F. MG-DEMO-CASH-READY: cash, pembayaran terverifikasi
        $pidF = $this->upsertPengajuan($db, [
            'kode_pesanan'      => 'MG-DEMO-CASH-READY',
            'user_id'           => $cashReadyId,
            'produk_emas_id'    => $pick(['MGD-007']),
            'metode_pembayaran' => 'cash',
            'nama'              => 'Nyoman Cash Ready',
            'no_telepon'        => '6281200000005',
            'alamat'            => 'Jl. Padma Utara No. 9, Denpasar',
            'status'            => 'disetujui',
            'pembayaran_status' => 'terverifikasi',
            'diverifikasi_pada' => $today->modify('-2 days')->format('Y-m-d H:i:s'),
            'diverifikasi_oleh' => $adminId,
        ]);
        $this->logAktivitas($db, $pidF, 'dibuat', 'Pesanan dibuat', 'pelanggan');
        $this->logAktivitas($db, $pidF, 'diverifikasi', 'Pesanan disetujui admin', 'admin');
        $this->createBuktiCash($db, $pidF, $cashReadyId, $this->getProdukHarga($produk, 'MGD-007'), 'terverifikasi', $adminId);

        // G. MG-DEMO-SHIPPED-RESI: status dikirim, metode resi
        $pidG = $this->upsertPengajuan($db, [
            'kode_pesanan'         => 'MG-DEMO-SHIPPED-RESI',
            'user_id'              => $shippedId,
            'produk_emas_id'       => $pick(['MGD-001','MGD-003']),
            'metode_pembayaran'    => 'kredit',
            'nama'                 => 'Komang Shipped',
            'no_telepon'           => '6281200000006',
            'alamat'               => 'Jl. Gatot Subroto No. 15, Denpasar',
            'tenor_bulan'          => 12,
            'periode_angsuran'     => 'bulanan',
            'uang_muka'            => $dpMinimal,
            'foto_ktp'             => $ktpFile,
            'status'               => 'dikirim',
            'pembayaran_status'    => 'terverifikasi',
            'metode_pengiriman'    => 'resi',
            'referensi_pengiriman' => 'JNE-1234567890',
            'diverifikasi_pada'    => $today->modify('-5 days')->format('Y-m-d H:i:s'),
            'diverifikasi_oleh'    => $adminId,
            'dikirim_pada'         => $today->modify('-2 days')->format('Y-m-d H:i:s'),
            'dikirim_oleh'         => $adminId,
        ]);
        $this->logAktivitas($db, $pidG, 'dibuat', 'Pesanan dibuat', 'pelanggan');
        $this->logAktivitas($db, $pidG, 'diverifikasi', 'Pesanan disetujui', 'admin');
        $this->logAktivitas($db, $pidG, 'dikirim', 'Pesanan dikirim via resi: JNE-1234567890', 'admin');
        $this->buatKreditDariPengajuan($db, $pidG, $margin, $adminId);
        $this->createBuktiDP($db, $pidG, $shippedId, $dpMinimal, 'terverifikasi', $ktpFile, $adminId);

        // H. MG-DEMO-SHIPPED-PHONE: status dikirim, metode no_hp
        $pidH = $this->upsertPengajuan($db, [
            'kode_pesanan'         => 'MG-DEMO-SHIPPED-PHONE',
            'user_id'              => $doneId,
            'produk_emas_id'       => $pick(['MGD-004']),
            'metode_pembayaran'    => 'cash',
            'nama'                 => 'Putu Done',
            'no_telepon'           => '6281200000007',
            'alamat'               => 'Jl. Diponegoro No. 30, Denpasar',
            'status'               => 'dikirim',
            'pembayaran_status'    => 'terverifikasi',
            'metode_pengiriman'    => 'no_hp',
            'referensi_pengiriman' => '6281234567890',
            'diverifikasi_pada'    => $today->modify('-4 days')->format('Y-m-d H:i:s'),
            'diverifikasi_oleh'    => $adminId,
            'dikirim_pada'         => $today->modify('-1 day')->format('Y-m-d H:i:s'),
            'dikirim_oleh'         => $adminId,
        ]);
        $this->logAktivitas($db, $pidH, 'dibuat', 'Pesanan dibuat', 'pelanggan');
        $this->logAktivitas($db, $pidH, 'diverifikasi', 'Pesanan disetujui', 'admin');
        $this->logAktivitas($db, $pidH, 'dikirim', 'Pesanan dikirim via no_hp: 6281234567890', 'admin');
        $this->createBuktiCash($db, $pidH, $doneId, $this->getProdukHarga($produk, 'MGD-004'), 'terverifikasi', $adminId);

        // I. MG-DEMO-DONE: status selesai
        $pidI = $this->upsertPengajuan($db, [
            'kode_pesanan'         => 'MG-DEMO-DONE',
            'user_id'              => $doneId,
            'produk_emas_id'       => $pick(['MGD-005']),
            'metode_pembayaran'    => 'cash',
            'nama'                 => 'Putu Done',
            'no_telepon'           => '6281200000007',
            'alamat'               => 'Jl. Diponegoro No. 30, Denpasar',
            'status'               => 'selesai',
            'pembayaran_status'    => 'terverifikasi',
            'metode_pengiriman'    => 'resi',
            'referensi_pengiriman' => 'JNE-0987654321',
            'diverifikasi_pada'    => $today->modify('-10 days')->format('Y-m-d H:i:s'),
            'diverifikasi_oleh'    => $adminId,
            'dikirim_pada'         => $today->modify('-7 days')->format('Y-m-d H:i:s'),
            'dikirim_oleh'         => $adminId,
            'selesai_pada'         => $today->modify('-3 days')->format('Y-m-d H:i:s'),
            'selesai_oleh'         => $adminId,
        ]);
        $this->logAktivitas($db, $pidI, 'dibuat', 'Pesanan dibuat', 'pelanggan');
        $this->logAktivitas($db, $pidI, 'diverifikasi', 'Pesanan disetujui', 'admin');
        $this->logAktivitas($db, $pidI, 'dikirim', 'Pesanan dikirim via resi', 'admin');
        $this->logAktivitas($db, $pidI, 'selesai', 'Pesanan selesai', 'admin');
        $this->createBuktiCash($db, $pidI, $doneId, $this->getProdukHarga($produk, 'MGD-005'), 'terverifikasi', $adminId);

        // J. MG-DEMO-REJECTED: ditolak
        $pidJ = $this->upsertPengajuan($db, [
            'kode_pesanan'      => 'MG-DEMO-REJECTED',
            'user_id'           => $rejectedId,
            'produk_emas_id'    => $pick(['MGD-001']),
            'metode_pembayaran' => 'kredit',
            'nama'              => 'Made Rejected',
            'no_telepon'        => '6281200000008',
            'alamat'            => 'Jl. Teuku Umar No. 5, Denpasar',
            'tenor_bulan'       => 12,
            'periode_angsuran'  => 'bulanan',
            'uang_muka'         => $dpMinimal,
            'status'            => 'ditolak',
            'catatan'           => 'Data KTP tidak sesuai dengan identitas nasabah.',
            'ditolak_pada'      => $today->modify('-5 days')->format('Y-m-d H:i:s'),
            'ditolak_oleh'      => $adminId,
        ]);
        $this->logAktivitas($db, $pidJ, 'dibuat', 'Pesanan dibuat', 'pelanggan');
        $this->logAktivitas($db, $pidJ, 'ditolak', 'Data KTP tidak sesuai dengan identitas nasabah.', 'admin');

        // K. MG-DEMO-CANCELLED-LEGACY: status dibatalkan
        $pidK = $this->upsertPengajuan($db, [
            'kode_pesanan'      => 'MG-DEMO-CANCELLED-LEGACY',
            'user_id'           => $overdueId,
            'produk_emas_id'    => $pick(['MGD-002']),
            'metode_pembayaran' => 'cash',
            'nama'              => 'Ketut Overdue',
            'no_telepon'        => '6281200000009',
            'alamat'            => 'Jl. By Pass Ngurah Rai No. 10, Denpasar',
            'status'            => 'dibatalkan',
            'catatan'           => 'Pesanan dibatalkan oleh pelanggan.',
        ]);
        $this->logAktivitas($db, $pidK, 'dibuat', 'Pesanan dibuat', 'pelanggan');
        $this->logAktivitas($db, $pidK, 'dibatalkan', 'Pesanan dibatalkan oleh admin', 'admin');

        // L. MG-DEMO-VERIFY-TODAY: data khusus untuk klik Verifikasi
        $pidL = $this->upsertPengajuan($db, [
            'kode_pesanan'      => 'MG-DEMO-VERIFY-TODAY',
            'user_id'           => $pelangganId,
            'produk_emas_id'    => $pick(['MGD-009','MGD-010']),
            'metode_pembayaran' => 'kredit',
            'nama'              => 'Putu Demo Pelanggan',
            'no_telepon'        => '6281200000001',
            'alamat'            => 'Jl. Tunjung Sari No. 12, Denpasar',
            'tenor_bulan'       => 12,
            'periode_angsuran'  => 'bulanan',
            'uang_muka'         => $dpMinimal,
            'foto_ktp'          => $ktpFile,
            'status'            => 'baru',
            'pembayaran_status' => 'menunggu',
        ]);
        $this->logAktivitas($db, $pidL, 'dibuat', 'Pesanan dibuat oleh pelanggan', 'pelanggan');

        // ============================================================
        // 5. KREDIT DEMO SCENARIOS
        // ============================================================
        $calculator = new CreditCalculatorService();

        // 5a. Kredit aktif lancar — beberapa cicilan sudah dibayar
        $this->seedKreditLancar($db, $nsbPelanggan, $produk, $calculator, $margin, $dpMinimal, $today, $adminId);

        // 5b. Kredit H-3 jatuh tempo — cicilan pertama jatuh tempo 3 hari lagi
        $this->seedKreditH3($db, $nsbDpReady, $produk, $calculator, $margin, $dpMinimal, $today, $adminId);

        // 5c. Kredit jatuh tempo hari ini
        $this->seedKreditJatuhTempo($db, $nsbDpPend, $produk, $calculator, $margin, $dpMinimal, $today, $adminId);

        // 5d. Kredit terlambat 7 hari
        $this->seedKreditTerlambat($db, $nsbOverdue, $produk, $calculator, $margin, $dpMinimal, $today, $adminId, 7);

        // 5e. Kredit terlambat 30 hari
        $this->seedKreditTerlambat($db, $nsbOverdue, $produk, $calculator, $margin, $dpMinimal, $today, $adminId, 30);

        // 5f. Kredit pembayaran sebagian
        $this->seedKreditSebagian($db, $nsbCashPend, $produk, $calculator, $margin, $dpMinimal, $today, $adminId);

        // 5g. Kredit pembayaran lebih awal
        $this->seedKreditLebihAwal($db, $nsbCashReady, $produk, $calculator, $margin, $dpMinimal, $today, $adminId);

        // 5h. Kredit — satu pembayaran melunasi dua jadwal
        $this->seedKreditMultiJadwal($db, $nsbShipped, $produk, $calculator, $margin, $dpMinimal, $today, $adminId);

        // 5i. Kredit lunas
        $this->seedKreditLunas($db, $nsbLunas, $produk, $calculator, $margin, $dpMinimal, $today, $adminId);

        // 5j. Kredit dibatalkan legacy
        $this->seedKreditDibatalkan($db, $nsbRejected, $produk, $calculator, $margin, $dpMinimal, $today, $adminId);

        // Seed specific dev user scenario (zebec_request_1)
        $this->seedDevUserScenarios($db, $devUserId, $nsbDevUser, $produk, $calculator, $margin, $dpMinimal, $today, $adminId);

        log_message('info', 'MahenGoldWorkflowSeeder completed — ' . count($db->table('pengajuan')->get()->getResultArray()) . ' pengajuan, ' . $db->table('kredit')->countAllResults() . ' kredit');
    }

    // ================================================================
    // UPSERT HELPERS
    // ================================================================

    protected function upsertUser($db, string $email): int
    {
        $existing = $db->table('users')->where('email', $email)->get()->getRowArray();
        if ($existing) {
            return (int) $existing['id'];
        }
        // Insert minimal user row — MahenGoldSeeder handles full fields
        $db->table('users')->insert([
            'email'         => $email,
            'nama'          => $email,
            'password_hash' => password_hash('demo1234', PASSWORD_DEFAULT),
            'role'          => 'pelanggan',
            'is_active'     => 1,
        ]);
        return (int) $db->insertID();
    }

    protected function upsertNasabah($db, int $userId, string $nama, string $noTelepon): int
    {
        $existing = $db->table('nasabah')->where('user_id', $userId)->get()->getRowArray();
        if ($existing) {
            return (int) $existing['id'];
        }
        $db->table('nasabah')->insert([
            'kode_nasabah' => 'PENDING',
            'user_id'      => $userId,
            'nama'         => $nama,
            'no_telepon'   => $noTelepon,
            'alamat'       => 'Alamat demo',
        ]);
        $id = (int) $db->insertID();
        $db->table('nasabah')->where('id', $id)->update(['kode_nasabah' => generate_kode('NSB', $id)]);
        return $id;
    }

    protected function upsertPengajuan($db, array $data): int
    {
        // Unset code if set, since it will be auto-generated sequentially using generate_kode
        unset($data['kode_pesanan']);
        $db->table('pengajuan')->insert($data);
        $id = (int) $db->insertID();
        $db->table('pengajuan')->where('id', $id)->update(['kode_pesanan' => generate_kode('MG', $id)]);
        return $id;
    }

    protected function pickProduk(array $produk, array $codes): int
    {
        foreach ($codes as $code) {
            if (isset($produk[$code])) {
                return (int) $produk[$code]['id'];
            }
        }
        return (int) reset($produk)['id'];
    }

    protected function getProdukHarga(array $produk, string $kode): int
    {
        return isset($produk[$kode]) ? (int) $produk[$kode]['harga_pokok'] : 1500000;
    }

    // ================================================================
    // AKTIVITAS & BUKTI HELPERS
    // ================================================================

    protected function logAktivitas($db, int $pengajuanId, string $aksi, string $keterangan, string $aktor): void
    {
        $db->table('pengajuan_aktivitas')->insert([
            'pengajuan_id' => $pengajuanId,
            'aksi'         => $aksi,
            'keterangan'   => $keterangan,
            'aktor'        => $aktor,
        ]);
    }

    protected function createBuktiDP($db, int $pengajuanId, int $userId, int $nominal, string $status, string $ktpFile, ?int $adminId = null): void
    {
        $file = 'demo_bukti_dp_' . $pengajuanId . '.png';
        $this->writeDemoImage(WRITEPATH . 'uploads/bukti/' . $file, 'BUKTI DP #' . $pengajuanId, 'Ref: ' . $pengajuanId);

        $data = [
            'tipe'          => 'dp',
            'pengajuan_id'  => $pengajuanId,
            'user_id'       => $userId,
            'nominal'       => $nominal,
            'nama_pengirim' => 'Demo Pelanggan',
            'no_rekening'   => '1234567890',
            'bank_pengirim' => 'BCA',
            'file_path'     => $file,
            'status'        => $status,
        ];
        if ($status === 'terverifikasi' && $adminId) {
            $data['diverifikasi_oleh'] = $adminId;
            $data['diverifikasi_pada'] = date('Y-m-d H:i:s');
        }
        $bid = $db->table('bukti_pembayaran')->insert($data);
        if ($bid) {
            $id = (int) $db->insertID();
            $db->table('bukti_pembayaran')->where('id', $id)->update(['kode' => generate_kode('BKT', $id)]);
        }
    }

    protected function createBuktiCash($db, int $pengajuanId, int $userId, int $nominal, string $status, ?int $adminId = null): void
    {
        $file = 'demo_bukti_cash_' . $pengajuanId . '.png';
        $this->writeDemoImage(WRITEPATH . 'uploads/bukti/' . $file, 'BUKTI CASH #' . $pengajuanId, 'Ref: ' . $pengajuanId);

        $data = [
            'tipe'          => 'cash',
            'pengajuan_id'  => $pengajuanId,
            'user_id'       => $userId,
            'nominal'       => $nominal,
            'nama_pengirim' => 'Demo Pelanggan',
            'no_rekening'   => '1234567890',
            'bank_pengirim' => 'BCA',
            'file_path'     => $file,
            'status'        => $status,
        ];
        if ($status === 'terverifikasi' && $adminId) {
            $data['diverifikasi_oleh'] = $adminId;
            $data['diverifikasi_pada'] = date('Y-m-d H:i:s');
        }
        $bid = $db->table('bukti_pembayaran')->insert($data);
        if ($bid) {
            $id = (int) $db->insertID();
            $db->table('bukti_pembayaran')->where('id', $id)->update(['kode' => generate_kode('BKT', $id)]);
        }
    }

    // ================================================================
    // KREDIT CREATION HELPERS
    // ================================================================

    /**
     * Create a kredit + jadwal angsuran, idempotent by kode_kredit.
     */
    protected function createKredit(
        $db,
        int $nasabahId,
        int $produkId,
        $calculator,
        float $margin,
        int $uangMuka,
        \DateTimeImmutable $today,
        int $adminId,
        string $kode,
        string $jadwalPertama,
        array $overrides = []
    ): ?int {
        $produk = $db->table('produk_emas')->where('id', $produkId)->get()->getRowArray();
        if (!$produk) {
            return null;
        }

        $kalkulasi = $calculator->calculate((float) $produk['harga_pokok'], $margin, 12, 'bulanan', $uangMuka);

        $totalTerbayar = $overrides['total_terbayar'] ?? 0;
        $sisaPokok = (int) $kalkulasi['sisa_pokok'];
        $sisaPiutang = max(0, $sisaPokok - $totalTerbayar);
        $status = $overrides['status'] ?? 'aktif';

        $db->table('kredit')->insert([
            'kode_kredit'          => 'PENDING',
            'pengajuan_id'         => $overrides['pengajuan_id'] ?? null,
            'nasabah_id'           => $nasabahId,
            'produk_emas_id'       => $produkId,
            'tanggal_kredit'       => $today->format('Y-m-d'),
            'harga_pokok_snapshot' => $kalkulasi['harga_pokok'],
            'margin_persen'        => $kalkulasi['margin_persen'],
            'margin_nominal'       => $kalkulasi['margin_nominal'],
            'total_harga_kredit'   => $kalkulasi['total_harga_kredit'],
            'uang_muka'            => $uangMuka,
            'sisa_pokok_kredit'    => $sisaPokok,
            'tenor_bulan'          => 12,
            'periode_angsuran'     => 'bulanan',
            'jumlah_periode'       => $kalkulasi['jumlah_periode'],
            'nominal_angsuran'     => $kalkulasi['nominal_angsuran'],
            'total_terbayar'       => $totalTerbayar,
            'sisa_piutang'         => $sisaPiutang,
            'status'               => $status,
        ]);
        $kreditId = (int) $db->insertID();
        $db->table('kredit')->where('id', $kreditId)->update(['kode_kredit' => generate_kode('KRD', $kreditId)]);

        // Generate jadwal
        $jadwal = $calculator->generateSchedule($jadwalPertama, $kalkulasi);
        foreach ($jadwal as &$j) {
            $j['kredit_id'] = $kreditId;
        }
        unset($j);
        $db->table('jadwal_angsuran')->insertBatch($jadwal);

        return $kreditId;
    }

    /**
     * Buat kredit dari pengajuan — mimics CreditTransactionService::createFromPengajuan
     */
    protected function buatKreditDariPengajuan($db, int $pengajuanId, float $margin, int $adminId): void
    {
        $pengajuan = $db->table('pengajuan')->where('id', $pengajuanId)->get()->getRowArray();
        if (!$pengajuan || ($pengajuan['metode_pembayaran'] ?? '') !== 'kredit') {
            return;
        }

        // Skip if kredit already exists for this pengajuan
        $existing = $db->table('kredit')->where('pengajuan_id', $pengajuanId)->get()->getRowArray();
        if ($existing) {
            return;
        }

        $produk = $db->table('produk_emas')->where('id', $pengajuan['produk_emas_id'])->get()->getRowArray();
        if (!$produk) {
            return;
        }

        $calculator = new CreditCalculatorService();
        $tenor    = (int) ($pengajuan['tenor_bulan'] ?: 12);
        $periode  = (string) ($pengajuan['periode_angsuran'] ?: 'bulanan');
        $uangMuka = (int) ($pengajuan['uang_muka'] ?? 0);
        $kalkulasi = $calculator->calculate((float) $produk['harga_pokok'], $margin, $tenor, $periode, $uangMuka);

        $jatuhTempoPertama = $periode === 'mingguan'
            ? date('Y-m-d', strtotime('+7 day'))
            : date('Y-m-d', strtotime('+1 month'));

        $sisaPokok = (int) $kalkulasi['sisa_pokok'];

        // Find nasabah for this user — required by FK
        $nasabah = $db->table('nasabah')->where('user_id', $pengajuan['user_id'])->get()->getRowArray();
        if (!$nasabah) {
            // Auto-create nasabah
            $nsbId = $db->table('nasabah')->insert([
                'kode_nasabah' => 'NSB-' . str_pad((string) ($db->table('nasabah')->countAllResults() + 1), 4, '0', STR_PAD_LEFT),
                'user_id'      => $pengajuan['user_id'],
                'nama'         => $pengajuan['nama'] ?? 'Auto Nasabah',
                'no_telepon'   => $pengajuan['no_telepon'] ?? '',
                'alamat'       => $pengajuan['alamat'] ?? '-',
            ]);
            $nasabahId = (int) $db->insertID();
            $db->table('nasabah')->where('id', $nasabahId)->update(['kode_nasabah' => generate_kode('NSB', $nasabahId)]);
        } else {
            $nasabahId = (int) $nasabah['id'];
        }

        $db->table('kredit')->insert([
            'kode_kredit'          => 'PENDING',
            'pengajuan_id'         => $pengajuanId,
            'nasabah_id'           => $nasabahId,
            'produk_emas_id'       => $produk['id'],
            'tanggal_kredit'       => date('Y-m-d'),
            'harga_pokok_snapshot' => $kalkulasi['harga_pokok'],
            'margin_persen'        => $kalkulasi['margin_persen'],
            'margin_nominal'       => $kalkulasi['margin_nominal'],
            'total_harga_kredit'   => $kalkulasi['total_harga_kredit'],
            'uang_muka'            => $uangMuka,
            'sisa_pokok_kredit'    => $sisaPokok,
            'tenor_bulan'          => $tenor,
            'periode_angsuran'     => $periode,
            'jumlah_periode'       => $kalkulasi['jumlah_periode'],
            'nominal_angsuran'     => $kalkulasi['nominal_angsuran'],
            'total_terbayar'       => 0,
            'sisa_piutang'         => $sisaPokok,
            'status'               => 'aktif',
            'catatan'              => 'Auto dari pesanan ' . ($pengajuan['kode_pesanan'] ?? ''),
        ]);
        $kreditId = (int) $db->insertID();
        $db->table('kredit')->where('id', $kreditId)->update(['kode_kredit' => generate_kode('KRD', $kreditId)]);

        $jadwal = $calculator->generateSchedule($jatuhTempoPertama, $kalkulasi);
        foreach ($jadwal as &$j) {
            $j['kredit_id'] = $kreditId;
        }
        unset($j);
        $db->table('jadwal_angsuran')->insertBatch($jadwal);

        // Decrement stock
        $stok = (int) $produk['stok'];
        if ($stok > 0) {
            $db->table('produk_emas')->where('id', $produk['id'])->update(['stok' => $stok - 1]);
        }
    }

    // ================================================================
    // KREDIT SCENARIO HELPERS
    // ================================================================

    protected function seedKreditLancar($db, int $nasabahId, array $produk, $calculator, float $margin, int $dp, \DateTimeImmutable $today, int $adminId): void
    {
        $p = reset($produk);
        $jadwalPertama = $today->modify('-30 days')->format('Y-m-d');
        $kreditId = $this->createKredit($db, $nasabahId, (int) $p['id'], $calculator, $margin, $dp, $today, $adminId, 'MG-KR-ACTIVE', $jadwalPertama, [
            'status' => 'aktif',
        ]);
        if (!$kreditId) return;

        // Pay first 2 installments
        $schedules = $db->table('jadwal_angsuran')->where('kredit_id', $kreditId)->orderBy('angsuran_ke', 'ASC')->get()->getResultArray();
        $totalPaid = 0;
        for ($i = 0; $i < min(2, count($schedules)); $i++) {
            $nominal = (int) round((float) $schedules[$i]['nominal_tagihan']);
            $db->table('jadwal_angsuran')->where('id', $schedules[$i]['id'])->update([
                'nominal_dibayar' => $nominal,
                'status'          => 'dibayar',
                'tanggal_dibayar' => $schedules[$i]['tanggal_jatuh_tempo'],
            ]);
            $this->recordSeededPayment($db, $kreditId, (int)$schedules[$i]['id'], $nominal, $schedules[$i]['tanggal_jatuh_tempo'], $adminId, 'Pembayaran Angsuran ' . ($i + 1));
            $totalPaid += $nominal;
        }
        // Read sisa_pokok_kredit from the kredit row (set correctly by createKredit)
        $kreditRow = $db->table('kredit')->where('id', $kreditId)->get()->getRowArray();
        $sisaPokokKredit = (int) round((float) ($kreditRow['sisa_pokok_kredit'] ?? 0));
        $db->table('kredit')->where('id', $kreditId)->update([
            'total_terbayar' => $totalPaid,
            'sisa_piutang'   => max(0, $sisaPokokKredit - $totalPaid),
        ]);
    }

    protected function seedKreditH3($db, int $nasabahId, array $produk, $calculator, float $margin, int $dp, \DateTimeImmutable $today, int $adminId): void
    {
        $p = reset($produk);
        $jadwalPertama = $today->modify('+3 days')->format('Y-m-d');
        $this->createKredit($db, $nasabahId, (int) $p['id'], $calculator, $margin, $dp, $today, $adminId, 'MG-KR-H3', $jadwalPertama, [
            'status' => 'aktif',
        ]);
    }

    protected function seedKreditJatuhTempo($db, int $nasabahId, array $produk, $calculator, float $margin, int $dp, \DateTimeImmutable $today, int $adminId): void
    {
        $p = reset($produk);
        $jadwalPertama = $today->format('Y-m-d');
        $this->createKredit($db, $nasabahId, (int) $p['id'], $calculator, $margin, $dp, $today, $adminId, 'MG-KR-JT-TODAY', $jadwalPertama, [
            'status' => 'aktif',
        ]);
    }

    protected function seedKreditTerlambat($db, int $nasabahId, array $produk, $calculator, float $margin, int $dp, \DateTimeImmutable $today, int $adminId, int $daysOverdue): void
    {
        $p = reset($produk);
        $kode = 'MG-KR-OVERDUE-' . $daysOverdue;
        $jadwalPertama = $today->modify("-{$daysOverdue} days -1 month")->format('Y-m-d');
        $kreditId = $this->createKredit($db, $nasabahId, (int) $p['id'], $calculator, $margin, $dp, $today, $adminId, $kode, $jadwalPertama, [
            'status' => 'aktif',
        ]);
        if (!$kreditId) return;

        // Mark first installment as overdue
        $schedules = $db->table('jadwal_angsuran')->where('kredit_id', $kreditId)->orderBy('angsuran_ke', 'ASC')->get()->getResultArray();
        if (!empty($schedules[0])) {
            $db->table('jadwal_angsuran')->where('id', $schedules[0]['id'])->update([
                'status' => 'terlambat',
            ]);
        }
    }

    protected function seedKreditSebagian($db, int $nasabahId, array $produk, $calculator, float $margin, int $dp, \DateTimeImmutable $today, int $adminId): void
    {
        $p = reset($produk);
        $jadwalPertama = $today->modify('-45 days')->format('Y-m-d');
        $kreditId = $this->createKredit($db, $nasabahId, (int) $p['id'], $calculator, $margin, $dp, $today, $adminId, 'MG-KR-SEBAGIAN', $jadwalPertama, [
            'status' => 'aktif',
        ]);
        if (!$kreditId) return;

        $schedules = $db->table('jadwal_angsuran')->where('kredit_id', $kreditId)->orderBy('angsuran_ke', 'ASC')->get()->getResultArray();
        if (!empty($schedules[0])) {
            $tagihan = (int) round((float) $schedules[0]['nominal_tagihan']);
            $bayar = (int) ($tagihan * 0.5); // Pay 50%
            $db->table('jadwal_angsuran')->where('id', $schedules[0]['id'])->update([
                'nominal_dibayar' => $bayar,
                'status'          => 'sebagian',
                'tanggal_dibayar' => date('Y-m-d'),
            ]);
            $this->recordSeededPayment($db, $kreditId, (int)$schedules[0]['id'], $bayar, date('Y-m-d'), $adminId, 'Pembayaran sebagian Angsuran 1');
            $kreditRow = $db->table('kredit')->where('id', $kreditId)->get()->getRowArray();
            $sisaPokokKredit = (int) round((float) ($kreditRow['sisa_pokok_kredit'] ?? 0));
            $db->table('kredit')->where('id', $kreditId)->update([
                'total_terbayar' => $bayar,
                'sisa_piutang'   => max(0, $sisaPokokKredit - $bayar),
            ]);
        }
    }

    protected function seedKreditLebihAwal($db, int $nasabahId, array $produk, $calculator, float $margin, int $dp, \DateTimeImmutable $today, int $adminId): void
    {
        $p = reset($produk);
        $jadwalPertama = $today->modify('+20 days')->format('Y-m-d');
        $kreditId = $this->createKredit($db, $nasabahId, (int) $p['id'], $calculator, $margin, $dp, $today, $adminId, 'MG-KR-EARLY', $jadwalPertama, [
            'status' => 'aktif',
        ]);
        if (!$kreditId) return;

        // Pay first installment early (before due date)
        $schedules = $db->table('jadwal_angsuran')->where('kredit_id', $kreditId)->orderBy('angsuran_ke', 'ASC')->get()->getResultArray();
        if (!empty($schedules[0])) {
            $nominal = (int) round((float) $schedules[0]['nominal_tagihan']);
            $db->table('jadwal_angsuran')->where('id', $schedules[0]['id'])->update([
                'nominal_dibayar' => $nominal,
                'status'          => 'dibayar',
                'tanggal_dibayar' => date('Y-m-d'),
            ]);
            $this->recordSeededPayment($db, $kreditId, (int)$schedules[0]['id'], $nominal, date('Y-m-d'), $adminId, 'Pembayaran lebih awal Angsuran 1');
            $kreditRow = $db->table('kredit')->where('id', $kreditId)->get()->getRowArray();
            $sisaPokokKredit = (int) round((float) ($kreditRow['sisa_pokok_kredit'] ?? 0));
            $db->table('kredit')->where('id', $kreditId)->update([
                'total_terbayar' => $nominal,
                'sisa_piutang'   => max(0, $sisaPokokKredit - $nominal),
            ]);
        }
    }

    protected function seedKreditMultiJadwal($db, int $nasabahId, array $produk, $calculator, float $margin, int $dp, \DateTimeImmutable $today, int $adminId): void
    {
        $p = reset($produk);
        $jadwalPertama = $today->modify('-60 days')->format('Y-m-d');
        $kreditId = $this->createKredit($db, $nasabahId, (int) $p['id'], $calculator, $margin, $dp, $today, $adminId, 'MG-KR-MULTI', $jadwalPertama, [
            'status' => 'aktif',
        ]);
        if (!$kreditId) return;

        $schedules = $db->table('jadwal_angsuran')->where('kredit_id', $kreditId)->orderBy('angsuran_ke', 'ASC')->get()->getResultArray();
        if (count($schedules) >= 2) {
            // One payment covers 2 schedules
            $nominal1 = (int) round((float) $schedules[0]['nominal_tagihan']);
            $nominal2 = (int) round((float) $schedules[1]['nominal_tagihan']);
            $totalBayar = $nominal1 + $nominal2;

            $db->table('pembayaran_angsuran')->insert([
                'kode_pembayaran'   => 'PENDING',
                'kredit_id'         => $kreditId,
                'nominal_bayar'     => $totalBayar,
                'tanggal_bayar'     => date('Y-m-d'),
                'metode_pembayaran' => 'transfer',
                'keterangan'        => 'Pembayaran multi-jadwal',
                'dicatat_oleh'      => $adminId,
                'created_at'        => date('Y-m-d H:i:s'),
            ]);
            $payId = $db->insertID();
            $db->table('pembayaran_angsuran')->where('id', $payId)->update(['kode_pembayaran' => generate_kode('BYR', $payId)]);

            foreach ([$schedules[0], $schedules[1]] as $s) {
                $n = (int) round((float) $s['nominal_tagihan']);
                $db->table('jadwal_angsuran')->where('id', $s['id'])->update([
                    'nominal_dibayar' => $n,
                    'status'          => 'dibayar',
                    'tanggal_dibayar' => date('Y-m-d'),
                ]);
                $db->table('pembayaran_alokasi')->insert([
                    'pembayaran_angsuran_id' => $payId,
                    'jadwal_angsuran_id'     => $s['id'],
                    'nominal_alokasi'        => $n,
                    'created_at'             => date('Y-m-d H:i:s'),
                ]);
            }
            $kreditRow = $db->table('kredit')->where('id', $kreditId)->get()->getRowArray();
            $sisaPokokKredit = (int) round((float) ($kreditRow['sisa_pokok_kredit'] ?? 0));
            $db->table('kredit')->where('id', $kreditId)->update([
                'total_terbayar' => $totalBayar,
                'sisa_piutang'   => max(0, $sisaPokokKredit - $totalBayar),
            ]);
        }
    }

    protected function seedKreditLunas($db, int $nasabahId, array $produk, $calculator, float $margin, int $dp, \DateTimeImmutable $today, int $adminId): void
    {
        $p = reset($produk);
        $jadwalPertama = $today->modify('-365 days')->format('Y-m-d');
        $kreditId = $this->createKredit($db, $nasabahId, (int) $p['id'], $calculator, $margin, $dp, $today, $adminId, 'MG-KR-LUNAS', $jadwalPertama, [
            'status' => 'aktif',
        ]);
        if (!$kreditId) return;

        // Mark all schedules as paid
        $schedules = $db->table('jadwal_angsuran')->where('kredit_id', $kreditId)->get()->getResultArray();
        $totalPaid = 0;
        foreach ($schedules as $s) {
            $nominal = (int) round((float) $s['nominal_tagihan']);
            $db->table('jadwal_angsuran')->where('id', $s['id'])->update([
                'nominal_dibayar' => $nominal,
                'status'          => 'dibayar',
                'tanggal_dibayar' => $s['tanggal_jatuh_tempo'],
            ]);
            $this->recordSeededPayment($db, $kreditId, (int)$s['id'], $nominal, $s['tanggal_jatuh_tempo'], $adminId, 'Pelunasan Angsuran ' . $s['angsuran_ke']);
            $totalPaid += $nominal;
        }
        $db->table('kredit')->where('id', $kreditId)->update([
            'total_terbayar' => $totalPaid,
            'sisa_piutang'   => 0,
            'status'         => 'lunas',
        ]);
    }

    protected function recordSeededPayment($db, int $kreditId, int $jadwalId, int $nominal, string $tanggal, int $adminId, string $keterangan): void
    {
        $kredit = $db->table('kredit')->where('id', $kreditId)->get()->getRowArray();
        $nasabahId = (int) ($kredit['nasabah_id'] ?? 0);
        $nasabah = $db->table('nasabah')->where('id', $nasabahId)->get()->getRowArray();
        $userId = (int) ($nasabah['user_id'] ?? $adminId);

        $db->table('pembayaran_angsuran')->insert([
            'kode_pembayaran'   => 'PENDING',
            'kredit_id'         => $kreditId,
            'jadwal_angsuran_id'=> $jadwalId,
            'nominal_bayar'     => $nominal,
            'tanggal_bayar'     => $tanggal,
            'metode_pembayaran' => 'transfer',
            'keterangan'        => $keterangan,
            'dicatat_oleh'      => $adminId,
            'created_at'        => $tanggal . ' 12:00:00',
        ]);
        $payId = $db->insertID();
        $db->table('pembayaran_angsuran')->where('id', $payId)->update(['kode_pembayaran' => generate_kode('BYR', $payId)]);

        $db->table('pembayaran_alokasi')->insert([
            'pembayaran_angsuran_id' => $payId,
            'jadwal_angsuran_id'     => $jadwalId,
            'nominal_alokasi'        => $nominal,
            'created_at'             => $tanggal . ' 12:00:00',
        ]);
    }

    protected function seedKreditDibatalkan($db, int $nasabahId, array $produk, $calculator, float $margin, int $dp, \DateTimeImmutable $today, int $adminId): void
    {
        $p = reset($produk);
        $jadwalPertama = $today->modify('-30 days')->format('Y-m-d');
        $this->createKredit($db, $nasabahId, (int) $p['id'], $calculator, $margin, $dp, $today, $adminId, 'MG-KR-CANCELLED', $jadwalPertama, [
            'status' => 'dibatalkan',
        ]);
    }

    // ================================================================
    // IMAGE HELPER
    // ================================================================

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
            imagestring($img, 2, 24, 360, 'Gambar contoh data demo.', $soft);
            imagepng($img, $path);
            imagedestroy($img);
            return;
        }

        @file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
    }

    protected function seedDevUserScenarios($db, int $devUserId, int $nsbDevUser, array $produk, $calculator, float $margin, int $dpMinimal, \DateTimeImmutable $today, int $adminId): void
    {
        $pick = fn(array $codes) => $this->pickProduk($produk, $codes);
        $ktpFile = 'demo_ktp.png';
        $devUserEmail = 'winayaarya@gmail.com';

        // 1. Zebec Pengajuan Baru
        $this->upsertPengajuan($db, [
            'user_id'           => $devUserId,
            'produk_emas_id'    => $pick(['MGD-001']),
            'metode_pembayaran' => 'kredit',
            'nama'              => 'I Wayan Zebec 1',
            'no_telepon'        => '6281200000001',
            'alamat'            => 'Jl. By Pass Ngurah Rai No. 100, Sanur',
            'tenor_bulan'       => 12,
            'periode_angsuran'  => 'bulanan',
            'uang_muka'         => $dpMinimal,
            'foto_ktp'          => $ktpFile,
            'status'            => 'baru',
            'pembayaran_status' => 'menunggu',
        ]);

        // 2. Zebec DP Pending
        $pidDpPending = $this->upsertPengajuan($db, [
            'user_id'           => $devUserId,
            'produk_emas_id'    => $pick(['MGD-002']),
            'metode_pembayaran' => 'kredit',
            'nama'              => 'I Wayan Zebec 1',
            'no_telepon'        => '6281200000001',
            'alamat'            => 'Jl. By Pass Ngurah Rai No. 100, Sanur',
            'tenor_bulan'       => 6,
            'periode_angsuran'  => 'bulanan',
            'uang_muka'         => $dpMinimal,
            'foto_ktp'          => $ktpFile,
            'status'            => 'disetujui',
            'pembayaran_status' => 'menunggu',
            'diverifikasi_pada' => $today->modify('-1 day')->format('Y-m-d H:i:s'),
            'diverifikasi_oleh' => $adminId,
        ]);
        $this->buatKreditDariPengajuan($db, $pidDpPending, $margin, $adminId);
        $this->createBuktiDP($db, $pidDpPending, $devUserId, $dpMinimal, 'menunggu', $ktpFile);

        // 3. Zebec DP Verified (Ready to Ship)
        $pidDpVerified = $this->upsertPengajuan($db, [
            'user_id'           => $devUserId,
            'produk_emas_id'    => $pick(['MGD-003']),
            'metode_pembayaran' => 'kredit',
            'nama'              => 'I Wayan Zebec 1',
            'no_telepon'        => '6281200000001',
            'alamat'            => 'Jl. By Pass Ngurah Rai No. 100, Sanur',
            'tenor_bulan'       => 12,
            'periode_angsuran'  => 'bulanan',
            'uang_muka'         => $dpMinimal,
            'foto_ktp'          => $ktpFile,
            'status'            => 'disetujui',
            'pembayaran_status' => 'terverifikasi',
            'diverifikasi_pada' => $today->modify('-2 days')->format('Y-m-d H:i:s'),
            'diverifikasi_oleh' => $adminId,
        ]);
        $this->buatKreditDariPengajuan($db, $pidDpVerified, $margin, $adminId);
        $this->createBuktiDP($db, $pidDpVerified, $devUserId, $dpMinimal, 'terverifikasi', $ktpFile, $adminId);

        // 4. Zebec Shipped
        $pidShipped = $this->upsertPengajuan($db, [
            'user_id'           => $devUserId,
            'produk_emas_id'    => $pick(['MGD-004']),
            'metode_pembayaran' => 'kredit',
            'nama'              => 'I Wayan Zebec 1',
            'no_telepon'        => '6281200000001',
            'alamat'            => 'Jl. By Pass Ngurah Rai No. 100, Sanur',
            'tenor_bulan'       => 12,
            'periode_angsuran'  => 'bulanan',
            'uang_muka'         => $dpMinimal,
            'foto_ktp'          => $ktpFile,
            'status'            => 'dikirim',
            'pembayaran_status' => 'terverifikasi',
            'diverifikasi_pada' => $today->modify('-3 days')->format('Y-m-d H:i:s'),
            'diverifikasi_oleh' => $adminId,
            'metode_pengiriman' => 'resi',
            'referensi_pengiriman' => 'REG-ZEBEC-001',
            'dikirim_pada'      => $today->modify('-1 day')->format('Y-m-d H:i:s'),
            'dikirim_oleh'      => $adminId,
        ]);
        $this->buatKreditDariPengajuan($db, $pidShipped, $margin, $adminId);
        $this->createBuktiDP($db, $pidShipped, $devUserId, $dpMinimal, 'terverifikasi', $ktpFile, $adminId);

        // 5. Zebec Kredit Aktif (with installments: 1 paid, 1 overdue, 1 unpaid, and manual reminders)
        $pidKredit = $this->upsertPengajuan($db, [
            'user_id'           => $devUserId,
            'produk_emas_id'    => $pick(['MGD-005']),
            'metode_pembayaran' => 'kredit',
            'nama'              => 'I Wayan Zebec 1',
            'no_telepon'        => '6281200000001',
            'alamat'            => 'Jl. By Pass Ngurah Rai No. 100, Sanur',
            'tenor_bulan'       => 12,
            'periode_angsuran'  => 'bulanan',
            'uang_muka'         => $dpMinimal,
            'foto_ktp'          => $ktpFile,
            'status'            => 'selesai',
            'pembayaran_status' => 'terverifikasi',
            'diverifikasi_pada' => $today->modify('-45 days')->format('Y-m-d H:i:s'),
            'diverifikasi_oleh' => $adminId,
            'metode_pengiriman' => 'resi',
            'referensi_pengiriman' => 'REG-ZEBEC-002',
            'dikirim_pada'      => $today->modify('-40 days')->format('Y-m-d H:i:s'),
            'dikirim_oleh'      => $adminId,
            'selesai_pada'      => $today->modify('-39 days')->format('Y-m-d H:i:s'),
            'selesai_oleh'      => $adminId,
        ]);
        $this->createBuktiDP($db, $pidKredit, $devUserId, $dpMinimal, 'terverifikasi', $ktpFile, $adminId);

        // Create the Kredit row and Jadwal
        $jadwalPertama = $today->modify('-39 days')->format('Y-m-d');
        $kreditId = $this->createKredit($db, $nsbDevUser, $pick(['MGD-005']), $calculator, $margin, $dpMinimal, $today, $adminId, 'KRD-ZEBEC-ACTIVE', $jadwalPertama, [
            'pengajuan_id' => $pidKredit,
            'status'       => 'aktif',
        ]);

        if ($kreditId) {
            $schedules = $db->table('jadwal_angsuran')->where('kredit_id', $kreditId)->orderBy('angsuran_ke', 'ASC')->get()->getResultArray();
            
            // Pay first schedule
            if (isset($schedules[0])) {
                $nominal = (int) round((float) $schedules[0]['nominal_tagihan']);
                $db->table('jadwal_angsuran')->where('id', $schedules[0]['id'])->update([
                    'nominal_dibayar' => $nominal,
                    'status'          => 'dibayar',
                    'tanggal_dibayar' => $schedules[0]['tanggal_jatuh_tempo'],
                ]);

                // Create a payment record and verify it
                $db->table('pembayaran_angsuran')->insert([
                    'kode_pembayaran' => 'PENDING',
                    'kredit_id'       => $kreditId,
                    'jadwal_angsuran_id'=> $schedules[0]['id'],
                    'nominal_bayar'   => $nominal,
                    'tanggal_bayar'   => $schedules[0]['tanggal_jatuh_tempo'],
                    'metode_pembayaran'=> 'transfer',
                    'keterangan'      => 'Pembayaran Angsuran 1',
                    'dicatat_oleh'    => $adminId,
                    'created_at'      => $schedules[0]['tanggal_jatuh_tempo'],
                ]);
                $paymentId = $db->insertID();
                $db->table('pembayaran_angsuran')->where('id', $paymentId)->update(['kode_pembayaran' => generate_kode('BYR', $paymentId)]);

                // Allocate payment
                $db->table('pembayaran_alokasi')->insert([
                    'pembayaran_angsuran_id' => $paymentId,
                    'jadwal_angsuran_id'     => $schedules[0]['id'],
                    'nominal_alokasi'        => $nominal,
                    'created_at'             => $schedules[0]['tanggal_jatuh_tempo'],
                ]);

                // Update Kredit totals
                $kreditRow = $db->table('kredit')->where('id', $kreditId)->get()->getRowArray();
                $sisaPokok = (int) ($kreditRow['sisa_pokok_kredit'] ?? 5080000);
                $db->table('kredit')->where('id', $kreditId)->update([
                    'total_terbayar' => $nominal,
                    'sisa_piutang'   => max(0, $sisaPokok - $nominal),
                ]);
            }

            // Set second schedule to overdue (jatuh tempo 9 days ago)
            if (isset($schedules[1])) {
                $db->table('jadwal_angsuran')->where('id', $schedules[1]['id'])->update([
                    'status' => 'terlambat',
                ]);

                // Log a reminder sent to user
                $db->table('reminder_angsuran_logs')->insert([
                    'kredit_id'          => $kreditId,
                    'jadwal_angsuran_id' => $schedules[1]['id'],
                    'user_id'            => $devUserId,
                    'nasabah_id'         => $nsbDevUser,
                    'jenis'              => 'terlambat',
                    'channel'            => 'email',
                    'tujuan'             => $devUserEmail,
                    'subjek'             => 'Pemberitahuan Keterlambatan Pembayaran Angsuran',
                    'pesan'              => 'Yth. I Wayan Zebec 1, angsuran ke-2 Anda telah melewati jatuh tempo...',
                    'status'             => 'sukses',
                    'tanggal_referensi'  => $schedules[1]['tanggal_jatuh_tempo'],
                    'created_at'         => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
