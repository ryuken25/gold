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

        // 1. Truncate workflow tables to ensure clean, idempotent seeding
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

        // 2. Fetch admin & staff accounts
        $adminRow = $db->table('users')->where('email', 'admin@mahengold.test')->get()->getRowArray();
        $adminId  = $adminRow ? (int) $adminRow['id'] : 1;

        // 3. Fetch Balinese users seeded by MahenGoldSeeder
        $userEmails = [
            'winayaarya@gmail.com', // I Wayan Zebec 1 (dev user)
            'made.winayagatar@mahengold.test', // I Made Winayagatar Arya Bhanu
            'kirana.maheswari@mahengold.test', // Ni Putu Kirana Maheswari
            'arya.pranata@mahengold.test', // I Kadek Arya Pranata
            'sekar.lestari@mahengold.test', // Ni Made Sekar Lestari
            'aditya.mahendra@mahengold.test', // I Komang Aditya Mahendra
            'diah.paramitha@mahengold.test', // Ni Kadek Diah Paramitha
            'surya.pradnyana@mahengold.test', // I Wayan Surya Pradnyana
            'ayu.saraswati@mahengold.test', // Ni Luh Ayu Saraswati
            'bagus.pramana@mahengold.test', // I Nyoman Bagus Pramana
            'citra.dewayani@mahengold.test', // Ni Komang Citra Dewayani
            'dharma.wijaya@mahengold.test', // I Ketut Dharma Wijaya
            'anjani.larasati@mahengold.test', // Ni Putu Anjani Larasati
        ];

        $users = [];
        foreach ($userEmails as $email) {
            $user = $db->table('users')->where('email', $email)->get()->getRowArray();
            if ($user) {
                $users[$email] = $user;
                // Ensure nasabah profile exists for the user
                $this->upsertNasabah($db, (int)$user['id'], $user['nama'], $user['no_telepon'] ?: '6281200000001');
            }
        }

        // 4. Get active products
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

        $pick = fn(array $codes) => (int) ($produk[$codes[0]]['id'] ?? reset($produk)['id']);
        $calculator = new CreditCalculatorService();

        // Write demo image for KTP
        $this->writeDemoImage(WRITEPATH . 'uploads/ktp/demo_ktp.png', 'KTP DEMO', 'KTP');

        // ============================================================
        // SEED SCENARIOS
        // ============================================================

        // 1. Pengajuan kredit baru belum diverifikasi (Zebec)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['winayaarya@gmail.com']['id'],
            'produk_emas_id'    => $pick(['MGD-001']),
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 12,
            'uang_muka'         => $dpMinimal,
            'status'            => 'baru',
            'pembayaran_status' => 'belum',
            'days_ago'          => 5,
            'admin_id'          => $adminId,
            'margin'            => $margin,
        ]);

        // 2. Pengajuan kredit disetujui dengan DP pending (Zebec)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['winayaarya@gmail.com']['id'],
            'produk_emas_id'    => $pick(['MGD-002']),
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 6,
            'uang_muka'         => $dpMinimal,
            'status'            => 'disetujui',
            'pembayaran_status' => 'menunggu', // DP pending
            'days_ago'          => 6,
            'admin_id'          => $adminId,
            'margin'            => $margin,
        ]);

        // 3. Pengajuan kredit disetujui dengan DP verified (Zebec)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['winayaarya@gmail.com']['id'],
            'produk_emas_id'    => $pick(['MGD-003']),
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 12,
            'uang_muka'         => $dpMinimal,
            'status'            => 'disetujui',
            'pembayaran_status' => 'terverifikasi', // DP verified
            'days_ago'          => 7,
            'admin_id'          => $adminId,
            'margin'            => $margin,
        ]);

        // 4. Pengajuan kredit DP 0 yang siap dikirim (Made Winayagatar)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['made.winayagatar@mahengold.test']['id'],
            'produk_emas_id'    => $pick(['MGD-004']),
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 6,
            'uang_muka'         => 0,
            'status'            => 'disetujui',
            'pembayaran_status' => 'terverifikasi',
            'days_ago'          => 8,
            'admin_id'          => $adminId,
            'margin'            => $margin,
        ]);

        // 5. Cash pending verifikasi (Made Winayagatar)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['made.winayagatar@mahengold.test']['id'],
            'produk_emas_id'    => $pick(['MGD-005']),
            'metode_pembayaran' => 'cash',
            'status'            => 'disetujui',
            'pembayaran_status' => 'menunggu', // Cash pending
            'days_ago'          => 9,
            'admin_id'          => $adminId,
            'margin'            => $margin,
            'nominal_cash'      => (int) $produk['MGD-005']['harga_pokok'],
        ]);

        // 6. Cash verified siap dikirim (Ni Putu Kirana)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['kirana.maheswari@mahengold.test']['id'],
            'produk_emas_id'    => $pick(['MGD-006']),
            'metode_pembayaran' => 'cash',
            'status'            => 'disetujui',
            'pembayaran_status' => 'terverifikasi', // Cash verified
            'days_ago'          => 10,
            'admin_id'          => $adminId,
            'margin'            => $margin,
            'nominal_cash'      => (int) $produk['MGD-006']['harga_pokok'],
        ]);

        // 7. Pesanan dikirim via resi (Ni Putu Kirana)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['kirana.maheswari@mahengold.test']['id'],
            'produk_emas_id'    => $pick(['MGD-007']),
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 12,
            'uang_muka'         => $dpMinimal,
            'status'            => 'dikirim',
            'pembayaran_status' => 'terverifikasi',
            'days_ago'          => 12,
            'admin_id'          => $adminId,
            'margin'            => $margin,
            'shipping_method'   => 'resi',
            'shipping_ref'      => 'REG-RESI-777',
        ]);

        // 8. Pesanan dikirim via nomor HP (I Kadek Arya)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['arya.pranata@mahengold.test']['id'],
            'produk_emas_id'    => $pick(['MGD-008']),
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 12,
            'uang_muka'         => $dpMinimal,
            'status'            => 'dikirim',
            'pembayaran_status' => 'terverifikasi',
            'days_ago'          => 13,
            'admin_id'          => $adminId,
            'margin'            => $margin,
            'shipping_method'   => 'no_hp',
            'shipping_ref'      => '6281200000004',
        ]);

        // 9. Pesanan selesai (I Kadek Arya)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['arya.pranata@mahengold.test']['id'],
            'produk_emas_id'    => $pick(['MGD-009']),
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 12,
            'uang_muka'         => $dpMinimal,
            'status'            => 'selesai',
            'pembayaran_status' => 'terverifikasi',
            'days_ago'          => 15,
            'admin_id'          => $adminId,
            'margin'            => $margin,
            'shipping_method'   => 'resi',
            'shipping_ref'      => 'REG-RESI-999',
        ]);

        // 10. Pesanan ditolak (Ni Made Sekar)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['sekar.lestari@mahengold.test']['id'],
            'produk_emas_id'    => $pick(['MGD-010']),
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 12,
            'uang_muka'         => $dpMinimal,
            'status'            => 'ditolak',
            'pembayaran_status' => 'belum',
            'days_ago'          => 4,
            'admin_id'          => $adminId,
            'margin'            => $margin,
            'reject_reason'     => 'Identitas KTP tidak terbaca dengan jelas.',
        ]);

        // 11. Kredit aktif lancar (Zebec)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['winayaarya@gmail.com']['id'],
            'produk_emas_id'    => $pick(['MGD-005']),
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 12,
            'uang_muka'         => $dpMinimal,
            'status'            => 'selesai',
            'pembayaran_status' => 'terverifikasi',
            'days_ago'          => 45,
            'admin_id'          => $adminId,
            'margin'            => $margin,
            'shipping_method'   => 'resi',
            'shipping_ref'      => 'REG-ZEBEC-111',
            'payment_scenario'  => 'lancar',
        ]);

        // 12. Kredit H-3 jatuh tempo (I Komang Aditya)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['aditya.mahendra@mahengold.test']['id'],
            'produk_emas_id'    => $pick(['MGD-001']),
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 12,
            'uang_muka'         => $dpMinimal,
            'status'            => 'selesai',
            'pembayaran_status' => 'terverifikasi',
            'days_ago'          => 27,
            'admin_id'          => $adminId,
            'margin'            => $margin,
            'shipping_method'   => 'resi',
            'shipping_ref'      => 'REG-ADITYA-122',
            'payment_scenario'  => 'H-3',
        ]);

        // 13. Kredit jatuh tempo hari ini (Ni Kadek Diah)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['diah.paramitha@mahengold.test']['id'],
            'produk_emas_id'    => $pick(['MGD-002']),
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 12,
            'uang_muka'         => $dpMinimal,
            'status'            => 'selesai',
            'pembayaran_status' => 'terverifikasi',
            'days_ago'          => 30,
            'admin_id'          => $adminId,
            'margin'            => $margin,
            'shipping_method'   => 'resi',
            'shipping_ref'      => 'REG-DIAH-133',
            'payment_scenario'  => 'today',
        ]);

        // 14. Kredit jatuh tempo bulan depan (I Wayan Surya)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['surya.pradnyana@mahengold.test']['id'],
            'produk_emas_id'    => $pick(['MGD-003']),
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 12,
            'uang_muka'         => $dpMinimal,
            'status'            => 'selesai',
            'pembayaran_status' => 'terverifikasi',
            'days_ago'          => 1,
            'admin_id'          => $adminId,
            'margin'            => $margin,
            'shipping_method'   => 'resi',
            'shipping_ref'      => 'REG-SURYA-144',
            'payment_scenario'  => 'bulan-depan',
        ]);

        // 15. Kredit telat 7 hari (Zebec)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['winayaarya@gmail.com']['id'],
            'produk_emas_id'    => $pick(['MGD-004']),
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 12,
            'uang_muka'         => $dpMinimal,
            'status'            => 'selesai',
            'pembayaran_status' => 'terverifikasi',
            'days_ago'          => 37,
            'admin_id'          => $adminId,
            'margin'            => $margin,
            'shipping_method'   => 'resi',
            'shipping_ref'      => 'REG-ZEBEC-155',
            'payment_scenario'  => 'telat-7',
        ]);

        // 16. Kredit telat 30 hari (Ni Luh Ayu)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['ayu.saraswati@mahengold.test']['id'],
            'produk_emas_id'    => $pick(['MGD-005']),
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 12,
            'uang_muka'         => $dpMinimal,
            'status'            => 'selesai',
            'pembayaran_status' => 'terverifikasi',
            'days_ago'          => 60,
            'admin_id'          => $adminId,
            'margin'            => $margin,
            'shipping_method'   => 'resi',
            'shipping_ref'      => 'REG-AYU-166',
            'payment_scenario'  => 'telat-30',
        ]);

        // 17. Kredit pembayaran sebagian (I Nyoman Bagus)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['bagus.pramana@mahengold.test']['id'],
            'produk_emas_id'    => $pick(['MGD-006']),
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 12,
            'uang_muka'         => $dpMinimal,
            'status'            => 'selesai',
            'pembayaran_status' => 'terverifikasi',
            'days_ago'          => 35,
            'admin_id'          => $adminId,
            'margin'            => $margin,
            'shipping_method'   => 'resi',
            'shipping_ref'      => 'REG-BAGUS-177',
            'payment_scenario'  => 'sebagian',
        ]);

        // 18. Kredit pembayaran multi-jadwal (Ni Komang Citra)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['citra.dewayani@mahengold.test']['id'],
            'produk_emas_id'    => $pick(['MGD-007']),
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 12,
            'uang_muka'         => $dpMinimal,
            'status'            => 'selesai',
            'pembayaran_status' => 'terverifikasi',
            'days_ago'          => 65,
            'admin_id'          => $adminId,
            'margin'            => $margin,
            'shipping_method'   => 'resi',
            'shipping_ref'      => 'REG-CITRA-188',
            'payment_scenario'  => 'multi-jadwal',
        ]);

        // 19. Kredit lunas (I Ketut Dharma)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['dharma.wijaya@mahengold.test']['id'],
            'produk_emas_id'    => $pick(['MGD-008']),
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 12,
            'uang_muka'         => $dpMinimal,
            'status'            => 'selesai',
            'pembayaran_status' => 'terverifikasi',
            'days_ago'          => 370,
            'admin_id'          => $adminId,
            'margin'            => $margin,
            'shipping_method'   => 'resi',
            'shipping_ref'      => 'REG-DHARMA-199',
            'payment_scenario'  => 'lunas',
        ]);

        // 20. Bukti cicilan pending verifikasi (Ni Putu Anjani)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['anjani.larasati@mahengold.test']['id'],
            'produk_emas_id'    => $pick(['MGD-009']),
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 12,
            'uang_muka'         => $dpMinimal,
            'status'            => 'selesai',
            'pembayaran_status' => 'terverifikasi',
            'days_ago'          => 35,
            'admin_id'          => $adminId,
            'margin'            => $margin,
            'shipping_method'   => 'resi',
            'shipping_ref'      => 'REG-ANJANI-200',
            'payment_scenario'  => 'cicilan-pending',
        ]);

        // 21. Bukti cicilan ditolak dan bisa upload ulang (Ni Putu Anjani)
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['anjani.larasati@mahengold.test']['id'],
            'produk_emas_id'    => $pick(['MGD-010']),
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 12,
            'uang_muka'         => $dpMinimal,
            'status'            => 'selesai',
            'pembayaran_status' => 'terverifikasi',
            'days_ago'          => 35,
            'admin_id'          => $adminId,
            'margin'            => $margin,
            'shipping_method'   => 'resi',
            'shipping_ref'      => 'REG-ANJANI-211',
            'payment_scenario'  => 'cicilan-rejected',
        ]);

        log_message('info', 'MahenGoldWorkflowSeeder completed successfully.');
    }

    // ================================================================
    // SIMULATION WORKFLOW ENGINE
    // ================================================================

    protected function simulateWorkflow($db, $calculator, array $params): int
    {
        $userId = (int) $params['user_id'];
        $produkEmasId = (int) ($params['produkEmasId'] ?? $params['produk_emas_id'] ?? 0);
        $metodePembayaran = $params['metode_pembayaran'];
        $tenorBulan = $params['tenor_bulan'] ?? 12;
        $uangMuka = $params['uang_muka'] ?? 200000;
        $targetStatus = $params['status']; // baru, disetujui, dikirim, selesai, ditolak
        $pembayaranStatus = $params['pembayaran_status'] ?? 'belum'; // belum, menunggu, terverifikasi
        $daysAgo = $params['days_ago'] ?? 30;
        $adminId = $params['admin_id'];

        $today = new DateTimeImmutable('today');
        $createdAt = $today->modify("-{$daysAgo} days");

        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
        $nasabah = $db->table('nasabah')->where('user_id', $userId)->get()->getRowArray();

        // 1. Create pengajuan (DIBUAT)
        $pengajuanId = $this->upsertPengajuan($db, [
            'user_id'           => $userId,
            'produk_emas_id'    => $produkEmasId,
            'metode_pembayaran' => $metodePembayaran,
            'nama'              => $user['nama'],
            'no_telepon'        => $user['no_telepon'] ?: '6281200000001',
            'alamat'            => $nasabah['alamat'] ?? 'Denpasar, Bali',
            'tenor_bulan'       => $tenorBulan,
            'periode_angsuran'  => 'bulanan',
            'uang_muka'         => $uangMuka,
            'foto_ktp'          => 'demo_ktp.png',
            'status'            => 'baru',
            'pembayaran_status' => 'belum',
            'created_at'        => $createdAt->format('Y-m-d H:i:s'),
        ]);

        $this->logAktivitas($db, $pengajuanId, 'dibuat', 'Pesanan dibuat oleh pelanggan', 'pelanggan');

        // 2. Order Verification (DIVERIFIKASI / DISETUJUI)
        if (in_array($targetStatus, ['disetujui', 'dikirim', 'selesai', 'ditolak'], true)) {
            $verifDaysAgo = max(0, $daysAgo - 1);
            $verifDate = $today->modify("-{$verifDaysAgo} days");

            $updateData = [
                'status'            => $targetStatus === 'ditolak' ? 'ditolak' : 'disetujui',
                'diverifikasi_pada' => $verifDate->format('Y-m-d H:i:s'),
                'diverifikasi_oleh' => $adminId,
            ];

            if ($pembayaranStatus === 'terverifikasi') {
                $updateData['pembayaran_status'] = 'terverifikasi';
            } elseif ($pembayaranStatus === 'menunggu') {
                $updateData['pembayaran_status'] = 'menunggu';
            }

            if ($targetStatus === 'ditolak') {
                $updateData['catatan'] = $params['reject_reason'] ?? 'Pesanan ditolak karena KTP kurang jelas.';
                $updateData['ditolak_pada'] = $verifDate->format('Y-m-d H:i:s');
                $updateData['ditolak_oleh'] = $adminId;
            }

            $db->table('pengajuan')->where('id', $pengajuanId)->update($updateData);

            if ($targetStatus === 'ditolak') {
                $this->logAktivitas($db, $pengajuanId, 'ditolak', $updateData['catatan'], 'admin');
                return $pengajuanId;
            }

            $this->logAktivitas($db, $pengajuanId, 'diverifikasi', 'Pesanan disetujui admin', 'admin');

            // Auto-create Kredit row if kredit
            if ($metodePembayaran === 'kredit') {
                $kreditId = $this->buatKreditDariPengajuan($db, $pengajuanId, $params['margin'], $adminId, $verifDate);
            }
        }

        // 3. Payment Upload (DP/Cash Proof)
        if ($pembayaranStatus !== 'belum' && in_array($targetStatus, ['disetujui', 'dikirim', 'selesai'], true)) {
            $payDaysAgo = max(0, $daysAgo - 2);
            $payDate = $today->modify("-{$payDaysAgo} days");

            if ($metodePembayaran === 'cash') {
                $nominal = $params['nominal_cash'] ?? 1500000;
                $this->createBuktiCash($db, $pengajuanId, $userId, $nominal, $pembayaranStatus, $adminId, $payDate);
            } else {
                if ($uangMuka > 0) {
                    $this->createBuktiDP($db, $pengajuanId, $userId, $uangMuka, $pembayaranStatus, 'demo_ktp.png', $adminId, $payDate);
                }
            }

            if ($pembayaranStatus === 'terverifikasi') {
                $db->table('pengajuan')->where('id', $pengajuanId)->update(['pembayaran_status' => 'terverifikasi']);
                $this->logAktivitas($db, $pengajuanId, 'pembayaran_diverifikasi', 'Pembayaran terverifikasi', 'admin');
            }
        }

        // 4. Shipping (DIKIRIM)
        if (in_array($targetStatus, ['dikirim', 'selesai'], true)) {
            $shipDaysAgo = max(0, $daysAgo - 3);
            $shipDate = $today->modify("-{$shipDaysAgo} days");

            $db->table('pengajuan')->where('id', $pengajuanId)->update([
                'status'                 => 'dikirim',
                'metode_pengiriman'      => $params['shipping_method'] ?? 'resi',
                'referensi_pengiriman'   => $params['shipping_ref'] ?? 'REG-RESI-100',
                'dikirim_pada'           => $shipDate->format('Y-m-d H:i:s'),
                'dikirim_oleh'           => $adminId,
            ]);

            $this->logAktivitas($db, $pengajuanId, 'dikirim', 'Pesanan dikirim via ' . ($params['shipping_method'] ?? 'resi'), 'admin');
        }

        // 5. Completion (SELESAI)
        if ($targetStatus === 'selesai') {
            $completeDaysAgo = max(0, $daysAgo - 4);
            $completeDate = $today->modify("-{$completeDaysAgo} days");

            $db->table('pengajuan')->where('id', $pengajuanId)->update([
                'status'       => 'selesai',
                'selesai_pada' => $completeDate->format('Y-m-d H:i:s'),
                'selesai_oleh' => $adminId,
            ]);

            $this->logAktivitas($db, $pengajuanId, 'selesai', 'Pesanan selesai', 'admin');
        }

        // 6. Installment payment scenarios (for credit)
        if (isset($kreditId) && !empty($params['payment_scenario'])) {
            $this->applyPaymentScenario($db, $kreditId, $params['payment_scenario'], $today, $adminId, $userId, $user['email']);
        }

        return $pengajuanId;
    }

    // ================================================================
    // WORKFLOW STAGE SUB-HELPERS
    // ================================================================

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
            'alamat'       => 'Jl. By Pass Ngurah Rai No. 100, Sanur',
        ]);
        $id = (int) $db->insertID();
        $db->table('nasabah')->where('id', $id)->update(['kode_nasabah' => generate_kode('NSB', $id)]);
        return $id;
    }

    protected function upsertPengajuan($db, array $data): int
    {
        $db->table('pengajuan')->insert($data);
        $id = (int) $db->insertID();
        $db->table('pengajuan')->where('id', $id)->update(['kode_pesanan' => generate_kode('MG', $id)]);
        return $id;
    }

    protected function logAktivitas($db, int $pengajuanId, string $aksi, string $keterangan, string $aktor): void
    {
        $db->table('pengajuan_aktivitas')->insert([
            'pengajuan_id' => $pengajuanId,
            'aksi'         => $aksi,
            'keterangan'   => $keterangan,
            'aktor'        => $aktor,
        ]);
    }

    protected function createBuktiDP($db, int $pengajuanId, int $userId, int $nominal, string $status, string $ktpFile, ?int $adminId = null, ?DateTimeImmutable $date = null): int
    {
        $date ??= new DateTimeImmutable('now');
        $file = 'demo_bukti_dp_' . $pengajuanId . '.png';
        $this->writeDemoImage(WRITEPATH . 'uploads/bukti/' . $file, 'BUKTI DP #' . $pengajuanId, 'Ref: ' . $pengajuanId);

        $data = [
            'tipe'          => 'dp',
            'pengajuan_id'  => $pengajuanId,
            'user_id'       => $userId,
            'nominal'       => $nominal,
            'nama_pengirim' => 'Nama Pengirim',
            'no_rekening'   => '1234567890',
            'bank_pengirim' => 'BCA',
            'file_path'     => $file,
            'status'        => $status,
            'created_at'    => $date->format('Y-m-d H:i:s'),
        ];
        if ($status === 'terverifikasi' && $adminId) {
            $data['diverifikasi_oleh'] = $adminId;
            $data['diverifikasi_pada'] = $date->format('Y-m-d H:i:s');
        }
        $db->table('bukti_pembayaran')->insert($data);
        $id = (int) $db->insertID();
        $db->table('bukti_pembayaran')->where('id', $id)->update(['kode' => generate_kode('BKT', $id)]);
        return $id;
    }

    protected function createBuktiCash($db, int $pengajuanId, int $userId, int $nominal, string $status, ?int $adminId = null, ?DateTimeImmutable $date = null): int
    {
        $date ??= new DateTimeImmutable('now');
        $file = 'demo_bukti_cash_' . $pengajuanId . '.png';
        $this->writeDemoImage(WRITEPATH . 'uploads/bukti/' . $file, 'BUKTI CASH #' . $pengajuanId, 'Ref: ' . $pengajuanId);

        $data = [
            'tipe'          => 'cash',
            'pengajuan_id'  => $pengajuanId,
            'user_id'       => $userId,
            'nominal'       => $nominal,
            'nama_pengirim' => 'Nama Pengirim',
            'no_rekening'   => '1234567890',
            'bank_pengirim' => 'BCA',
            'file_path'     => $file,
            'status'        => $status,
            'created_at'    => $date->format('Y-m-d H:i:s'),
        ];
        if ($status === 'terverifikasi' && $adminId) {
            $data['diverifikasi_oleh'] = $adminId;
            $data['diverifikasi_pada'] = $date->format('Y-m-d H:i:s');
        }
        $db->table('bukti_pembayaran')->insert($data);
        $id = (int) $db->insertID();
        $db->table('bukti_pembayaran')->where('id', $id)->update(['kode' => generate_kode('BKT', $id)]);
        return $id;
    }

    protected function buatKreditDariPengajuan($db, int $pengajuanId, float $margin, int $adminId, DateTimeImmutable $verifDate): int
    {
        $pengajuan = $db->table('pengajuan')->where('id', $pengajuanId)->get()->getRowArray();
        $produk = $db->table('produk_emas')->where('id', $pengajuan['produk_emas_id'])->get()->getRowArray();

        $calculator = new CreditCalculatorService();
        $tenor = (int) ($pengajuan['tenor_bulan'] ?: 12);
        $periode = (string) ($pengajuan['periode_angsuran'] ?: 'bulanan');
        $uangMuka = (int) ($pengajuan['uang_muka'] ?? 0);
        $kalkulasi = $calculator->calculate((float) $produk['harga_pokok'], $margin, $tenor, $periode, $uangMuka);

        $jatuhTempoPertama = $periode === 'mingguan'
            ? $verifDate->modify('+7 days')->format('Y-m-d')
            : $verifDate->modify('+1 month')->format('Y-m-d');

        $sisaPokok = (int) $kalkulasi['sisa_pokok'];

        $nasabah = $db->table('nasabah')->where('user_id', $pengajuan['user_id'])->get()->getRowArray();
        $nasabahId = (int) $nasabah['id'];

        $db->table('kredit')->insert([
            'kode_kredit'          => 'PENDING',
            'pengajuan_id'         => $pengajuanId,
            'nasabah_id'           => $nasabahId,
            'produk_emas_id'       => $produk['id'],
            'tanggal_kredit'       => $verifDate->format('Y-m-d'),
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

        return $kreditId;
    }

    // ================================================================
    // PAYMENT SCENARIO APPLIER
    // ================================================================

    protected function applyPaymentScenario($db, int $kreditId, string $scenario, DateTimeImmutable $today, int $adminId, int $userId, string $email): void
    {
        $schedules = $db->table('jadwal_angsuran')->where('kredit_id', $kreditId)->orderBy('angsuran_ke', 'ASC')->get()->getResultArray();
        $kredit = $db->table('kredit')->where('id', $kreditId)->get()->getRowArray();
        $sisaPokok = (int) $kredit['sisa_pokok_kredit'];

        switch ($scenario) {
            case 'lancar':
                // Set first schedule in past, pay it.
                if (isset($schedules[0])) {
                    $dueDate = $today->modify('-15 days');
                    $nominal = (int) round((float) $schedules[0]['nominal_tagihan']);

                    $db->table('jadwal_angsuran')->where('id', $schedules[0]['id'])->update([
                        'tanggal_jatuh_tempo' => $dueDate->format('Y-m-d'),
                        'nominal_dibayar'     => $nominal,
                        'status'              => 'dibayar',
                        'tanggal_dibayar'     => $dueDate->format('Y-m-d'),
                    ]);

                    $this->recordPayment($db, $kreditId, $schedules[0]['id'], $nominal, $dueDate, $adminId, 'Pembayaran Angsuran 1');

                    $db->table('kredit')->where('id', $kreditId)->update([
                        'total_terbayar' => $nominal,
                        'sisa_piutang'   => max(0, $sisaPokok - $nominal),
                    ]);
                }
                break;

            case 'H-3':
                if (isset($schedules[0])) {
                    $dueDate = $today->modify('+3 days');
                    $db->table('jadwal_angsuran')->where('id', $schedules[0]['id'])->update([
                        'tanggal_jatuh_tempo' => $dueDate->format('Y-m-d'),
                    ]);
                }
                break;

            case 'today':
                if (isset($schedules[0])) {
                    $dueDate = $today;
                    $db->table('jadwal_angsuran')->where('id', $schedules[0]['id'])->update([
                        'tanggal_jatuh_tempo' => $dueDate->format('Y-m-d'),
                    ]);
                }
                break;

            case 'bulan-depan':
                if (isset($schedules[0])) {
                    $dueDate = $today->modify('+30 days');
                    $db->table('jadwal_angsuran')->where('id', $schedules[0]['id'])->update([
                        'tanggal_jatuh_tempo' => $dueDate->format('Y-m-d'),
                    ]);
                }
                break;

            case 'telat-7':
                if (isset($schedules[0])) {
                    $dueDate = $today->modify('-7 days');
                    $db->table('jadwal_angsuran')->where('id', $schedules[0]['id'])->update([
                        'tanggal_jatuh_tempo' => $dueDate->format('Y-m-d'),
                        'status'              => 'terlambat',
                    ]);

                    // Seed reminder log
                    $db->table('reminder_angsuran_logs')->insert([
                        'kredit_id'          => $kreditId,
                        'jadwal_angsuran_id' => $schedules[0]['id'],
                        'user_id'            => $userId,
                        'nasabah_id'         => $kredit['nasabah_id'],
                        'jenis'              => 'terlambat',
                        'channel'            => 'email',
                        'tujuan'             => $email,
                        'subjek'             => 'Pemberitahuan Keterlambatan Pembayaran Angsuran',
                        'pesan'              => 'Angsuran Anda sudah telat 7 hari...',
                        'status'             => 'sukses',
                        'tanggal_referensi'  => $dueDate->format('Y-m-d'),
                        'created_at'         => date('Y-m-d H:i:s'),
                    ]);
                }
                break;

            case 'telat-30':
                if (isset($schedules[0])) {
                    $dueDate = $today->modify('-30 days');
                    $db->table('jadwal_angsuran')->where('id', $schedules[0]['id'])->update([
                        'tanggal_jatuh_tempo' => $dueDate->format('Y-m-d'),
                        'status'              => 'terlambat',
                    ]);

                    // Seed reminder log
                    $db->table('reminder_angsuran_logs')->insert([
                        'kredit_id'          => $kreditId,
                        'jadwal_angsuran_id' => $schedules[0]['id'],
                        'user_id'            => $userId,
                        'nasabah_id'         => $kredit['nasabah_id'],
                        'jenis'              => 'terlambat',
                        'channel'            => 'email',
                        'tujuan'             => $email,
                        'subjek'             => 'Pemberitahuan Keterlambatan Pembayaran Angsuran',
                        'pesan'              => 'Angsuran Anda sudah telat 30 hari...',
                        'status'             => 'sukses',
                        'tanggal_referensi'  => $dueDate->format('Y-m-d'),
                        'created_at'         => date('Y-m-d H:i:s'),
                    ]);
                }
                break;

            case 'sebagian':
                if (isset($schedules[0])) {
                    $dueDate = $today->modify('-5 days');
                    $nominal = (int) round((float) $schedules[0]['nominal_tagihan']);
                    $bayar = (int) ($nominal * 0.5); // 50%

                    $db->table('jadwal_angsuran')->where('id', $schedules[0]['id'])->update([
                        'tanggal_jatuh_tempo' => $dueDate->format('Y-m-d'),
                        'nominal_dibayar'     => $bayar,
                        'status'              => 'sebagian',
                        'tanggal_dibayar'     => $dueDate->format('Y-m-d'),
                    ]);

                    $this->recordPayment($db, $kreditId, $schedules[0]['id'], $bayar, $dueDate, $adminId, 'Pembayaran Sebagian');

                    $db->table('kredit')->where('id', $kreditId)->update([
                        'total_terbayar' => $bayar,
                        'sisa_piutang'   => max(0, $sisaPokok - $bayar),
                    ]);
                }
                break;

            case 'multi-jadwal':
                if (count($schedules) >= 2) {
                    $dueDate1 = $today->modify('-35 days');
                    $dueDate2 = $today->modify('-5 days');

                    $nom1 = (int) round((float) $schedules[0]['nominal_tagihan']);
                    $nom2 = (int) round((float) $schedules[1]['nominal_tagihan']);
                    $total = $nom1 + $nom2;

                    // Update schedules
                    $db->table('jadwal_angsuran')->where('id', $schedules[0]['id'])->update([
                        'tanggal_jatuh_tempo' => $dueDate1->format('Y-m-d'),
                        'nominal_dibayar'     => $nom1,
                        'status'              => 'dibayar',
                        'tanggal_dibayar'     => $dueDate2->format('Y-m-d'),
                    ]);

                    $db->table('jadwal_angsuran')->where('id', $schedules[1]['id'])->update([
                        'tanggal_jatuh_tempo' => $dueDate2->format('Y-m-d'),
                        'nominal_dibayar'     => $nom2,
                        'status'              => 'dibayar',
                        'tanggal_dibayar'     => $dueDate2->format('Y-m-d'),
                    ]);

                    // Single payment covering both
                    $db->table('pembayaran_angsuran')->insert([
                        'kode_pembayaran'   => 'PENDING',
                        'kredit_id'         => $kreditId,
                        'nominal_bayar'     => $total,
                        'tanggal_bayar'     => $dueDate2->format('Y-m-d'),
                        'metode_pembayaran' => 'transfer',
                        'keterangan'        => 'Pembayaran multi-jadwal (Angsuran 1 & 2)',
                        'dicatat_oleh'      => $adminId,
                        'created_at'        => $dueDate2->format('Y-m-d H:i:s'),
                    ]);
                    $payId = $db->insertID();
                    $db->table('pembayaran_angsuran')->where('id', $payId)->update(['kode_pembayaran' => generate_kode('BYR', $payId)]);

                    // Allocations
                    $db->table('pembayaran_alokasi')->insertBatch([
                        [
                            'pembayaran_angsuran_id' => $payId,
                            'jadwal_angsuran_id'     => $schedules[0]['id'],
                            'nominal_alokasi'        => $nom1,
                            'created_at'             => date('Y-m-d H:i:s'),
                        ],
                        [
                            'pembayaran_angsuran_id' => $payId,
                            'jadwal_angsuran_id'     => $schedules[1]['id'],
                            'nominal_alokasi'        => $nom2,
                            'created_at'             => date('Y-m-d H:i:s'),
                        ]
                    ]);

                    $db->table('kredit')->where('id', $kreditId)->update([
                        'total_terbayar' => $total,
                        'sisa_piutang'   => max(0, $sisaPokok - $total),
                    ]);
                }
                break;

            case 'lunas':
                $totalPaid = 0;
                $pBatch = [];
                
                foreach ($schedules as $index => $s) {
                    $nominal = (int) round((float) $s['nominal_tagihan']);
                    $dueDate = $today->modify("-" . (12 - $index) . " months");
                    
                    $db->table('jadwal_angsuran')->where('id', $s['id'])->update([
                        'tanggal_jatuh_tempo' => $dueDate->format('Y-m-d'),
                        'nominal_dibayar'     => $nominal,
                        'status'              => 'dibayar',
                        'tanggal_dibayar'     => $dueDate->format('Y-m-d'),
                    ]);

                    $this->recordPayment($db, $kreditId, $s['id'], $nominal, $dueDate, $adminId, 'Pembayaran Angsuran ' . ($index + 1));
                    $totalPaid += $nominal;
                }

                $db->table('kredit')->where('id', $kreditId)->update([
                    'total_terbayar' => $totalPaid,
                    'sisa_piutang'   => 0,
                    'status'         => 'lunas',
                ]);
                break;

            case 'cicilan-pending':
                if (isset($schedules[0])) {
                    $dueDate = $today->modify('-5 days');
                    $nominal = (int) round((float) $schedules[0]['nominal_tagihan']);

                    $db->table('jadwal_angsuran')->where('id', $schedules[0]['id'])->update([
                        'tanggal_jatuh_tempo' => $dueDate->format('Y-m-d'),
                        'status'              => 'terlambat',
                    ]);

                    // Pending bukti pembayaran
                    $file = 'demo_bukti_cicilan_' . $schedules[0]['id'] . '.png';
                    $this->writeDemoImage(WRITEPATH . 'uploads/bukti/' . $file, 'BUKTI ANGSURAN', 'Ref');

                    $db->table('bukti_pembayaran')->insert([
                        'tipe'               => 'cicilan',
                        'kredit_id'          => $kreditId,
                        'jadwal_angsuran_id' => $schedules[0]['id'],
                        'user_id'            => $userId,
                        'nominal'            => $nominal,
                        'file_path'          => $file,
                        'status'             => 'menunggu',
                        'created_at'         => $dueDate->format('Y-m-d H:i:s'),
                    ]);
                    $bid = $db->insertID();
                    $db->table('bukti_pembayaran')->where('id', $bid)->update(['kode' => generate_kode('BKT', $bid)]);
                }
                break;

            case 'cicilan-rejected':
                if (isset($schedules[0])) {
                    $dueDate = $today->modify('-5 days');
                    $nominal = (int) round((float) $schedules[0]['nominal_tagihan']);

                    $db->table('jadwal_angsuran')->where('id', $schedules[0]['id'])->update([
                        'tanggal_jatuh_tempo' => $dueDate->format('Y-m-d'),
                        'status'              => 'terlambat',
                    ]);

                    // Ditolak bukti pembayaran
                    $file = 'demo_bukti_cicilan_reject_' . $schedules[0]['id'] . '.png';
                    $this->writeDemoImage(WRITEPATH . 'uploads/bukti/' . $file, 'BUKTI REJECTED', 'Ref');

                    $db->table('bukti_pembayaran')->insert([
                        'tipe'               => 'cicilan',
                        'kredit_id'          => $kreditId,
                        'jadwal_angsuran_id' => $schedules[0]['id'],
                        'user_id'            => $userId,
                        'nominal'            => $nominal,
                        'file_path'          => $file,
                        'status'             => 'ditolak',
                        'catatan_admin'      => 'Bukti transfer buram/tidak terbaca.',
                        'diverifikasi_oleh'  => $adminId,
                        'diverifikasi_pada'  => $dueDate->format('Y-m-d H:i:s'),
                        'created_at'         => $dueDate->format('Y-m-d H:i:s'),
                    ]);
                    $bid = $db->insertID();
                    $db->table('bukti_pembayaran')->where('id', $bid)->update(['kode' => generate_kode('BKT', $bid)]);
                }
                break;
        }
    }

    protected function recordPayment($db, int $kreditId, int $jadwalId, int $nominal, DateTimeImmutable $date, int $adminId, string $keterangan): void
    {
        $db->table('pembayaran_angsuran')->insert([
            'kode_pembayaran'   => 'PENDING',
            'kredit_id'         => $kreditId,
            'jadwal_angsuran_id'=> $jadwalId,
            'nominal_bayar'     => $nominal,
            'tanggal_bayar'     => $date->format('Y-m-d'),
            'metode_pembayaran' => 'transfer',
            'keterangan'        => $keterangan,
            'dicatat_oleh'      => $adminId,
            'created_at'        => $date->format('Y-m-d H:i:s'),
        ]);
        $payId = $db->insertID();
        $db->table('pembayaran_angsuran')->where('id', $payId)->update(['kode_pembayaran' => generate_kode('BYR', $payId)]);

        $db->table('pembayaran_alokasi')->insert([
            'pembayaran_angsuran_id' => $payId,
            'jadwal_angsuran_id'     => $jadwalId,
            'nominal_alokasi'        => $nominal,
            'created_at'             => $date->format('Y-m-d H:i:s'),
        ]);
    }

    // ================================================================
    // IMAGE CREATOR HELPER
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
}
