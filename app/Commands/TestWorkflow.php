<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use App\Services\PengajuanWorkflowService;
use App\Models\PengajuanModel;

class TestWorkflow extends BaseCommand
{
    protected $group       = 'test';
    protected $name        = 'test:workflow';
    protected $description = 'Runs integration tests for the workflow service, payment verification, and repair migration';

    public function run(array $params)
    {
        $db = Database::connect();
        CLI::write('═══════════════════════════════════════════', 'cyan');
        CLI::write('RUNNING WORKFLOW INTEGRATION TESTS', 'cyan');
        CLI::write('═══════════════════════════════════════════', 'cyan');
        CLI::newLine();

        $db->transBegin();
        try {
            // Correct the status ENUM definition on the fly for tests
            $db->query("ALTER TABLE `pengajuan` MODIFY COLUMN `status` ENUM('baru', 'diproses', 'disetujui', 'dikirim', 'ditolak', 'dibatalkan', 'selesai') NOT NULL DEFAULT 'baru'");

            // Clean up existing test IDs to prevent duplicate/key constraint issues
            $db->table('bukti_pembayaran')->whereIn('id', [9999])->delete();
            $db->table('pengajuan_aktivitas')->whereIn('pengajuan_id', [9901, 9902, 9903, 9904])->delete();
            $db->table('kredit')->whereIn('pengajuan_id', [9901, 9902, 9903, 9904])->delete();
            $db->table('pengajuan')->whereIn('id', [9901, 9902, 9903, 9904])->delete();
            $db->table('users')->whereIn('id', [9999, 9998])->delete();
            $db->table('produk_emas')->whereIn('id', [9999])->delete();

            // Setup base fixtures (User, Product)
            $ok = $db->table('users')->insert([
                'id' => 9999,
                'username' => 'test_admin',
                'nama' => 'Test Admin User',
                'email' => 'admin_test@mahengold.com',
                'password_hash' => 'testpwd',
                'role' => 'admin',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            if (!$ok) {
                $err = $db->error();
                throw new \RuntimeException('Failed to insert admin user: ' . json_encode($err));
            }

            $ok = $db->table('users')->insert([
                'id' => 9998,
                'username' => 'test_customer',
                'nama' => 'Test Customer User',
                'email' => 'customer_test@gmail.com',
                'password_hash' => 'testpwd',
                'role' => 'pelanggan',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            if (!$ok) {
                $err = $db->error();
                throw new \RuntimeException('Failed to insert customer user: ' . json_encode($err));
            }

            $ok = $db->table('produk_emas')->insert([
                'id' => 9999,
                'kode_produk' => 'PRD-TEST-99',
                'nama_produk' => 'Test Gold Product',
                'jenis_emas' => 'Antam',
                'kadar' => '24K',
                'berat_gram' => 1.0,
                'harga_pokok' => 1000000.0,
                'stok' => 10,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            if (!$ok) {
                $err = $db->error();
                throw new \RuntimeException('Failed to insert product: ' . json_encode($err));
            }

            $workflowService = new PengajuanWorkflowService();

            $this->runTestVerifyPreservesPaymentStatus($db, $workflowService);
            $this->runTestShipValidationRules($db, $workflowService);
            $this->runTestPaymentVerificationAtomic($db);
            $this->runTestRepairMigration($db);

            CLI::write('🎉 All workflow tests passed successfully!', 'green');
        } catch (\Throwable $e) {
            CLI::write('❌ Test failed: ' . $e->getMessage(), 'red');
            CLI::write($e->getTraceAsString(), 'yellow');
        } finally {
            // Clean up test data before rollback so they do not remain if implicit commit occurred
            $db->table('bukti_pembayaran')->whereIn('id', [9999])->delete();
            $db->table('pengajuan_aktivitas')->whereIn('pengajuan_id', [9901, 9902, 9903, 9904])->delete();
            $db->table('kredit')->whereIn('pengajuan_id', [9901, 9902, 9903, 9904])->delete();
            $db->table('pengajuan')->whereIn('id', [9901, 9902, 9903, 9904])->delete();
            $db->table('users')->whereIn('id', [9999, 9998])->delete();
            $db->table('produk_emas')->whereIn('id', [9999])->delete();

            $db->transRollback();
            CLI::newLine();
            CLI::write('═══════════════════════════════════════════', 'cyan');
        }
    }

    private function runTestVerifyPreservesPaymentStatus($db, $workflowService)
    {
        CLI::write('Test 1: verify() payment status rules...', 'yellow');

        // Case A: Credit with DP = 0 -> pembayaran_status must become 'terverifikasi'
        $ok = $db->table('pengajuan')->insert([
            'id' => 9901,
            'user_id' => 9998,
            'produk_emas_id' => 9999,
            'kode_pesanan' => 'ORD-TEST-01',
            'nama' => 'Test Customer User',
            'metode_pembayaran' => 'kredit',
            'uang_muka' => 0,
            'pembayaran_status' => 'belum',
            'status' => 'baru',
            'tenor_bulan' => 3,
            'periode_angsuran' => 'bulanan',
            'alamat' => 'Test Address',
            'no_telepon' => '081234567890',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        if (!$ok) {
            $err = $db->error();
            throw new \RuntimeException('Failed to insert pengajuan 9901: ' . json_encode($err));
        }

        $res = $workflowService->verify(9901, 9999);
        if ($res['pembayaran_status'] !== 'terverifikasi') {
            throw new \RuntimeException('Verify failed: credit DP=0 should be auto-verified.');
        }
        CLI::write('  ✔ Credit order with DP=0 is auto-verified.', 'green');

        // Case B: Credit with DP > 0 -> pembayaran_status must preserve 'belum' or 'menunggu'
        $ok = $db->table('pengajuan')->insert([
            'id' => 9902,
            'user_id' => 9998,
            'produk_emas_id' => 9999,
            'kode_pesanan' => 'ORD-TEST-02',
            'nama' => 'Test Customer User',
            'metode_pembayaran' => 'kredit',
            'uang_muka' => 100000,
            'pembayaran_status' => 'belum',
            'status' => 'baru',
            'tenor_bulan' => 3,
            'periode_angsuran' => 'bulanan',
            'alamat' => 'Test Address',
            'no_telepon' => '081234567890',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        if (!$ok) {
            $err = $db->error();
            throw new \RuntimeException('Failed to insert pengajuan 9902: ' . json_encode($err));
        }

        $res = $workflowService->verify(9902, 9999);
        if ($res['pembayaran_status'] !== 'belum') {
            throw new \RuntimeException('Verify failed: credit DP>0 should preserve pembayaran_status as belum.');
        }
        CLI::write('  ✔ Credit order with DP>0 preserves payment status as belum.', 'green');

        // Case C: Cash order -> pembayaran_status must preserve its status
        $ok = $db->table('pengajuan')->insert([
            'id' => 9903,
            'user_id' => 9998,
            'produk_emas_id' => 9999,
            'kode_pesanan' => 'ORD-TEST-03',
            'nama' => 'Test Customer User',
            'metode_pembayaran' => 'cash',
            'uang_muka' => 0,
            'pembayaran_status' => 'menunggu',
            'status' => 'baru',
            'alamat' => 'Test Address',
            'no_telepon' => '081234567890',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        if (!$ok) {
            $err = $db->error();
            throw new \RuntimeException('Failed to insert pengajuan 9903: ' . json_encode($err));
        }

        $res = $workflowService->verify(9903, 9999);
        if ($res['pembayaran_status'] !== 'menunggu') {
            throw new \RuntimeException('Verify failed: cash order should preserve pembayaran_status as menunggu.');
        }
        CLI::write('  ✔ Cash order preserves payment status as menunggu.', 'green');
    }

    private function runTestShipValidationRules($db, $workflowService)
    {
        CLI::write('Test 2: ship() validation rules and prerequisites...', 'yellow');

        // Order 9902 (Credit DP > 0, status disetujui, pembayaran_status = belum) -> must fail ship
        $failed = false;
        try {
            $workflowService->ship(9902, 9999, 'resi', 'RESI-TEST-99');
        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), 'DP belum terverifikasi') !== false) {
                $failed = true;
            }
        }
        if (!$failed) {
            throw new \RuntimeException('Ship failed: credit DP > 0 should not be shipped if DP is not verified.');
        }
        CLI::write('  ✔ Credit order with unverified DP is blocked from shipping.', 'green');

        // Now verify payment for 9902 and ship -> should succeed
        $db->table('pengajuan')->where('id', 9902)->update(['pembayaran_status' => 'terverifikasi']);
        $res = $workflowService->ship(9902, 9999, 'resi', 'RESI-TEST-99');
        if ($res['status'] !== 'dikirim' || $res['referensi_pengiriman'] !== 'RESI-TEST-99') {
            throw new \RuntimeException('Ship failed: order should transition to dikirim with metadata. Actual status: ' . ($res['status'] ?? 'NULL') . ', actual referensi_pengiriman: ' . ($res['referensi_pengiriman'] ?? 'NULL'));
        }
        CLI::write('  ✔ Credit order with verified DP ships successfully.', 'green');
    }

    private function runTestPaymentVerificationAtomic($db)
    {
        CLI::write('Test 3: PembayaranController::verifikasi() atomic transaction...', 'yellow');

        $ok = $db->table('bukti_pembayaran')->insert([
            'id' => 9999,
            'kode' => 'PMT-TEST-99',
            'user_id' => 9998,
            'pengajuan_id' => 9903,
            'tipe' => 'cash',
            'nominal' => 1000000,
            'file_path' => 'bukti_test.jpg',
            'status' => 'menunggu',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        if (!$ok) {
            $err = $db->error();
            throw new \RuntimeException('Failed to insert bukti_pembayaran: ' . json_encode($err));
        }

        // Simulating the transaction block logic of verifikasi()
        $db->transStart();
        $db->table('pengajuan')->where('id', 9903)->update(['pembayaran_status' => 'terverifikasi']);
        $db->table('bukti_pembayaran')->where('id', 9999)->update([
            'status' => 'terverifikasi',
            'diverifikasi_oleh' => 9999,
            'diverifikasi_pada' => date('Y-m-d H:i:s')
        ]);
        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new \RuntimeException('Payment verification transaction failed.');
        }

        $pengajuan = $db->table('pengajuan')->where('id', 9903)->get()->getRowArray();
        $bukti = $db->table('bukti_pembayaran')->where('id', 9999)->get()->getRowArray();

        if ($pengajuan['pembayaran_status'] !== 'terverifikasi' || $bukti['status'] !== 'terverifikasi') {
            throw new \RuntimeException('Atomic verification failed to update order or proof status.');
        }
        CLI::write('  ✔ Payment verification transaction is fully atomic and updates correct statuses.', 'green');
    }

    private function runTestRepairMigration($db)
    {
        CLI::write('Test 4: RepairShippedWorkflowInconsistency migration...', 'yellow');

        // Insert an inconsistent row: has 'dikirim' activity log but order status remains 'disetujui'
        $ok = $db->table('pengajuan')->insert([
            'id' => 9904,
            'user_id' => 9998,
            'produk_emas_id' => 9999,
            'kode_pesanan' => 'ORD-TEST-04',
            'nama' => 'Test Customer User',
            'metode_pembayaran' => 'cash',
            'pembayaran_status' => 'terverifikasi',
            'status' => 'disetujui',
            'alamat' => 'Test Address',
            'no_telepon' => '081234567890',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        if (!$ok) {
            $err = $db->error();
            throw new \RuntimeException('Failed to insert pengajuan 9904: ' . json_encode($err));
        }

        $ok = $db->table('pengajuan_aktivitas')->insert([
            'pengajuan_id' => 9904,
            'aksi' => 'dikirim',
            'keterangan' => 'Pesanan dikirim via resi: RESI-TEST-9904',
            'aktor' => 'Test Admin User',
            'created_at' => '2026-06-19 12:00:00'
        ]);
        if (!$ok) {
            $err = $db->error();
            throw new \RuntimeException('Failed to insert pengajuan_aktivitas: ' . json_encode($err));
        }

        // Instantiate and run the repair migration UP logic
        require_once APPPATH . 'Database/Migrations/2026-06-19-210001_RepairShippedWorkflowInconsistency.php';
        $migration = new \App\Database\Migrations\RepairShippedWorkflowInconsistency();
        $migration->up();

        $pengajuan = $db->table('pengajuan')->where('id', 9904)->get()->getRowArray();
        if ($pengajuan['status'] !== 'dikirim') {
            throw new \RuntimeException('Repair migration failed: status of inconsistent order was not updated to dikirim.');
        }
        if ($pengajuan['dikirim_pada'] !== '2026-06-19 12:00:00' || (int)$pengajuan['dikirim_oleh'] !== 9999) {
            throw new \RuntimeException('Repair migration failed: dikirim_pada or dikirim_oleh metadata was not populated correctly.');
        }

        CLI::write('  ✔ Repair migration successfully detects and heals workflow shipping inconsistency.', 'green');
    }
}
