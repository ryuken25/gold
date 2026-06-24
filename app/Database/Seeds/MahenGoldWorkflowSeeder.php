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

        // 3. Fetch single customer
        $userEmails = [
            'kadeknadi98@gmail.com',
        ];

        $users = [];
        foreach ($userEmails as $email) {
            $user = $db->table('users')->where('email', $email)->get()->getRowArray();
            if ($user) {
                $users[$email] = $user;
                $this->upsertNasabah($db, (int)$user['id'], $user['nama'], $user['no_telepon'] ?: '6281234567890');
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

        // Generate demo KTP
        $this->writeDemoImage(WRITEPATH . 'uploads/ktp/demo_ktp.png', 'KTP DEMO', 'kadeknadi98@gmail.com');

        // ============================================================
        // SEED SINGLE ACTIVE CREDIT OVERDUE SCENARIO
        // ============================================================
        $this->simulateWorkflow($db, $calculator, [
            'user_id'           => $users['kadeknadi98@gmail.com']['id'],
            'produk_emas_id'    => $pick(['MGD-003']),
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 12,
            'uang_muka'         => 500000,
            'status'            => 'diterima', // order received, credit active, NOT completed
            'pembayaran_status' => 'terverifikasi', // DP verified
            'days_ago'          => 37,
            'admin_id'          => $adminId,
            'margin'            => $margin,
            'shipping_method'   => 'resi',
            'shipping_ref'      => 'REG-NADI-001',
            'payment_scenario'  => 'telat-7', // first schedule is terlambat (7 days overdue)
        ]);

        log_message('info', 'MahenGoldWorkflowSeeder completed successfully.');
    }

    protected function simulateWorkflow($db, $calculator, array $params): int
    {
        $userId = (int) $params['user_id'];
        $produkEmasId = (int) ($params['produkEmasId'] ?? $params['produk_emas_id'] ?? 0);
        $metodePembayaran = $params['metode_pembayaran'];
        $tenorBulan = $params['tenor_bulan'] ?? 12;
        $uangMuka = $params['uang_muka'] ?? 200000;
        $targetStatus = $params['status'];
        $pembayaranStatus = $params['pembayaran_status'] ?? 'belum';
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
            'no_telepon'        => $user['no_telepon'] ?: '6281234567890',
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

        // 2. Order Verification (DISETUJUI)
        if (in_array($targetStatus, ['disetujui', 'dikirim', 'diterima', 'selesai', 'ditolak'], true)) {
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

            $db->table('pengajuan')->where('id', $pengajuanId)->update($updateData);

            // Auto-create Kredit row if credit
            if ($metodePembayaran === 'kredit') {
                $kreditId = $this->buatKreditDariPengajuan($db, $pengajuanId, $params['margin'], $adminId, $verifDate);
            }
        }

        // 3. Payment Upload (DP/Cash Proof)
        if ($pembayaranStatus !== 'belum' && in_array($targetStatus, ['disetujui', 'dikirim', 'diterima', 'selesai'], true)) {
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
        if (in_array($targetStatus, ['dikirim', 'diterima', 'selesai'], true)) {
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

        // 4b. Received (DITERIMA)
        if (in_array($targetStatus, ['diterima', 'selesai'], true)) {
            $recvDaysAgo = max(0, $daysAgo - 3);
            $recvDate = $today->modify("-{$recvDaysAgo} days");

            $db->table('pengajuan')->where('id', $pengajuanId)->update([
                'status'        => 'diterima',
                'diterima_pada' => $recvDate->format('Y-m-d H:i:s'),
                'diterima_oleh' => $adminId,
            ]);

            $this->logAktivitas($db, $pengajuanId, 'diterima', 'Pesanan dikonfirmasi sudah diterima pelanggan.', 'admin');
        }

        // 5. Completion (SELESAI) - only if requested status is selesai
        if ($targetStatus === 'selesai') {
            $completeDaysAgo = max(0, $daysAgo - 4);
            $completeDate = $today->modify("-{$completeDaysAgo} days");

            $db->table('pengajuan')->where('id', $pengajuanId)->update([
                'status'       => 'selesai',
                'selesai_pada' => $completeDate->format('Y-m-d H:i:s'),
                'selesai_oleh' => null,
            ]);

            $this->logAktivitas($db, $pengajuanId, 'selesai', 'Pesanan selesai otomatis karena pembayaran/kredit sudah lunas.', 'system');
        }

        // 6. Installment payment scenarios (for credit)
        if (isset($kreditId) && !empty($params['payment_scenario'])) {
            $this->applyPaymentScenario($db, $kreditId, $params['payment_scenario'], $today, $adminId, $userId, $user['email']);
        }

        return $pengajuanId;
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

    protected function applyPaymentScenario($db, int $kreditId, string $scenario, DateTimeImmutable $today, int $adminId, int $userId, string $email): void
    {
        $schedules = $db->table('jadwal_angsuran')->where('kredit_id', $kreditId)->orderBy('angsuran_ke', 'ASC')->get()->getResultArray();
        $kredit = $db->table('kredit')->where('id', $kreditId)->get()->getRowArray();
        $sisaPokok = (int) $kredit['sisa_pokok_kredit'];

        switch ($scenario) {
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
        }
    }

    protected function writeDemoImage(string $path, string $judul, string $sub): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (!is_file($dir . '/index.html')) {
            @file_put_contents($dir . '/index.html', '');
        }

        if (function_exists('imagecreatetruecolor')) {
            $img  = imagecreatetruecolor(640, 400);
            
            if (strpos($path, 'ktp') !== false) {
                $bg   = imagecolorallocate($img, 45, 62, 80);
                $gold = imagecolorallocate($img, 201, 162, 75);
                $white = imagecolorallocate($img, 255, 255, 255);
                $red = imagecolorallocate($img, 220, 53, 69);
                
                imagefilledrectangle($img, 0, 0, 640, 400, $bg);
                imagefilledrectangle($img, 0, 0, 640, 64, imagecolorallocate($img, 28, 38, 49));
                imagestring($img, 5, 24, 24, 'DUMMY / BUKAN DOKUMEN RESMI', $red);
                
                imagestring($img, 5, 24, 90, 'KARTU IDENTITAS DUMMY', $gold);
                imagestring($img, 4, 24, 140, 'NIK  : DEMO-000001', $white);
                imagestring($img, 4, 24, 180, 'Nama : I Kadek Nadi Artana', $white);
                imagestring($img, 4, 24, 220, 'Asal : Denpasar, Bali', $white);
                
                imagestring($img, 5, 100, 300, 'DUMMY - BUKAN DOKUMEN RESMI', $red);
                imagestring($img, 2, 24, 370, 'Hanya digunakan untuk keperluan pengujian sistem.', $white);
            } else {
                $bg   = imagecolorallocate($img, 244, 246, 249);
                $dark = imagecolorallocate($img, 33, 37, 41);
                $gold = imagecolorallocate($img, 185, 130, 19);
                $green = imagecolorallocate($img, 40, 167, 69);
                $red = imagecolorallocate($img, 220, 53, 69);
                
                imagefilledrectangle($img, 0, 0, 640, 400, $bg);
                imagerectangle($img, 10, 10, 630, 390, $gold);
                imagefilledrectangle($img, 11, 11, 629, 64, imagecolorallocate($img, 233, 236, 239));
                imagestring($img, 5, 24, 24, 'BUKTI PEMBAYARAN DUMMY', $green);
                imagestring($img, 4, 450, 24, 'STATUS: SUCCESS', $green);
                
                imagestring($img, 5, 150, 150, 'DUMMY - TIDAK SAH', $red);
                imagestring($img, 4, 50, 200, 'Referensi : ' . $sub, $dark);
                imagestring($img, 4, 50, 240, 'Nominal   : ' . $judul, $dark);
                imagestring($img, 4, 50, 280, 'Tanggal   : ' . date('Y-m-d'), $dark);
                
                imagestring($img, 2, 50, 350, 'Dokumen ini dibuat otomatis sebagai data simulasi seeder.', $dark);
            }
            
            imagepng($img, $path);
            imagedestroy($img);
            return;
        }

        @file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
    }
}
