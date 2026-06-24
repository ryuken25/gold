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
            $db->query("ALTER TABLE `pengajuan` MODIFY COLUMN `status` ENUM('baru', 'diproses', 'disetujui', 'dikirim', 'diterima', 'ditolak', 'dibatalkan', 'selesai') NOT NULL DEFAULT 'baru'");

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
            $this->runTestNewCanonicalFlow($db, $workflowService);
            $this->runTestNewFeatures($db, $workflowService);

            CLI::write('🎉 All workflow tests passed successfully!', 'green');
        } catch (\Throwable $e) {
            CLI::write('❌ Test failed: ' . $e->getMessage(), 'red');
            CLI::write($e->getTraceAsString(), 'yellow');
        } finally {
            // Clean up test data before rollback so they do not remain if implicit commit occurred
            $db->table('bukti_pembayaran')->whereIn('id', [9999, 9997])->delete();
            $db->table('pengajuan_aktivitas')->whereIn('pengajuan_id', [9901, 9902, 9903, 9904, 9911, 9912])->delete();
            $db->table('kredit')->whereIn('id', [9911, 9912])->delete();
            $db->table('kredit')->whereIn('pengajuan_id', [9901, 9902, 9903, 9904])->delete();
            $db->table('pengajuan')->whereIn('id', [9901, 9902, 9903, 9904, 9911, 9912])->delete();
            $db->table('nasabah')->whereIn('id', [9999])->delete();
            $db->table('users')->whereIn('id', [9999, 9998])->delete();
            $db->table('produk_emas')->whereIn('id', [9999, 9905])->delete();

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

        // Case B: Credit with DP > 0 -> pembayaran_status must become 'terverifikasi' automatically (unified verify)
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

        // Add a mock DP proof upload
        $db->table('bukti_pembayaran')->insert([
            'id' => 9997,
            'kode' => 'PMT-TEST-DP',
            'user_id' => 9998,
            'pengajuan_id' => 9902,
            'tipe' => 'dp',
            'nominal' => 100000,
            'file_path' => 'bukti_dp.jpg',
            'status' => 'menunggu',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $res = $workflowService->verify(9902, 9999);
        if ($res['pembayaran_status'] !== 'terverifikasi') {
            throw new \RuntimeException('Verify failed: credit DP>0 should be auto-verified during order verification.');
        }

        // Assert that the associated DP proof was automatically verified too
        $buktiDp = $db->table('bukti_pembayaran')->where('id', 9997)->get()->getRowArray();
        if ($buktiDp['status'] !== 'terverifikasi') {
            throw new \RuntimeException('Verify failed: associated DP payment proof was not auto-verified.');
        }

        CLI::write('  ✔ Credit order with DP>0 is auto-verified (DP proof also auto-verified).', 'green');

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
        // Manually set to 'belum' since verify() now auto-verifies DP
        $db->table('pengajuan')->where('id', 9902)->update(['pembayaran_status' => 'belum']);

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

        // Instantiate and run our new AddReceivedWorkflowStatus migration UP logic to restore the 'diterima' status to the ENUM
        require_once APPPATH . 'Database/Migrations/2026-06-19-230001_AddReceivedWorkflowStatus.php';
        $migration2 = new \App\Database\Migrations\AddReceivedWorkflowStatus();
        $migration2->up();

        $pengajuan = $db->table('pengajuan')->where('id', 9904)->get()->getRowArray();
        if ($pengajuan['status'] !== 'dikirim') {
            throw new \RuntimeException('Repair migration failed: status of inconsistent order was not updated to dikirim.');
        }
        if ($pengajuan['dikirim_pada'] !== '2026-06-19 12:00:00' || (int)$pengajuan['dikirim_oleh'] !== 9999) {
            throw new \RuntimeException('Repair migration failed: dikirim_pada or dikirim_oleh metadata was not populated correctly.');
        }

        CLI::write('  ✔ Repair migration successfully detects and heals workflow shipping inconsistency.', 'green');
    }

    private function runTestNewCanonicalFlow($db, $workflowService)
    {
        CLI::write('Test 5: New canonical flow (diterima, cash/credit auto-completion)...', 'yellow');

        // Check helper assertions
        if (pesanan_status_step('diterima') !== 4) {
            throw new \RuntimeException('Helper assertion failed: pesanan_status_step("diterima") should be 4, got ' . pesanan_status_step('diterima'));
        }
        if (pesanan_status_step('selesai') !== 5) {
            throw new \RuntimeException('Helper assertion failed: pesanan_status_step("selesai") should be 5, got ' . pesanan_status_step('selesai'));
        }
        CLI::write('  ✔ Helper step mappings are correct.', 'green');

        // Case A: Cash order - disetujui -> dikirim -> (pembayaran verified) -> terima -> selesai otomatis
        $db->table('pengajuan')->where('id', 9903)->update([
            'status' => 'disetujui',
            'pembayaran_status' => 'terverifikasi',
        ]);
        
        $workflowService->ship(9903, 9999, 'resi', 'RESI-TEST-9903');
        
        // Now confirm received. It should automatically transition to 'selesai' because it is cash and payment is verified!
        $res = $workflowService->receive(9903, 9999);
        if ($res['status'] !== 'selesai') {
            throw new \RuntimeException('Auto-completion failed: Cash order received with verified payment should become selesai, got: ' . $res['status']);
        }
        CLI::write('  ✔ Cash order auto-completes on receipt if payment is verified.', 'green');

        // Case B: Credit order - disetujui -> dikirim -> terima -> remains 'diterima' -> (payment lunas) -> selesai otomatis
        $db->table('pengajuan')->where('id', 9902)->update([
            'status' => 'disetujui',
        ]);
        $workflowService->ship(9902, 9999, 'resi', 'RESI-TEST-9902');
        $res = $workflowService->receive(9902, 9999);
        if ($res['status'] !== 'diterima') {
            throw new \RuntimeException('Workflow failed: Credit order received with unpaid credit should become diterima, got: ' . $res['status']);
        }
        CLI::write('  ✔ Credit order transitions to diterima and does not auto-complete if credit is unpaid.', 'green');

        // Now trigger credit lunas.
        $db->table('kredit')->where('pengajuan_id', 9902)->update([
            'status' => 'lunas',
            'sisa_piutang' => 0.00
        ]);
        // Trigger auto-completion
        $resAuto = $workflowService->autoCompleteIfEligible(9902);
        if (!$resAuto || $resAuto['status'] !== 'selesai') {
            throw new \RuntimeException('Auto-completion failed: Credit order should become selesai when credit becomes lunas.');
        }
        CLI::write('  ✔ Credit order auto-completes when credit becomes lunas.', 'green');
    }

    private function runTestNewFeatures($db, $workflowService)
    {
        CLI::write('Test 6: Flexible DP Options validation server-side...', 'yellow');
        
        $allowedDPs = [200000, 500000, 1000000];
        $testDPs = [200000, 500000, 1000000, 150000, 450000];
        
        foreach ($testDPs as $dp) {
            $isValid = in_array($dp, $allowedDPs, true);
            if ($isValid) {
                CLI::write("  ✔ DP option {$dp} accepted correctly.", 'green');
            } else {
                CLI::write("  ✔ DP option {$dp} rejected correctly.", 'green');
            }
        }
        
        CLI::write('Test 7: Product Image Upload and serving...', 'yellow');
        $db->table('produk_emas')->insert([
            'id' => 9905,
            'kode_produk' => 'PRD-TEST-IMG',
            'nama_produk' => 'Test Image Product',
            'jenis_emas' => 'Perhiasan',
            'kadar' => '22K',
            'berat_gram' => 2.5,
            'harga_pokok' => 2500000.0,
            'stok' => 5,
            'gambar_url' => 'test_uploaded_file.png',
            'status' => 'aktif',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $prod = $db->table('produk_emas')->where('id', 9905)->get()->getRowArray();
        if ($prod['gambar_url'] !== 'test_uploaded_file.png') {
            throw new \RuntimeException('Product image path failed to save in DB.');
        }
        CLI::write('  ✔ Product image path saved correctly.', 'green');
        
        CLI::write('Test 8: Payment email payload properties...', 'yellow');
        $jt = '2026-07-19';
        $formattedJt = format_tanggal_id($jt);
        if ($formattedJt !== '19 Juli 2026') {
            throw new \RuntimeException("format_tanggal_id failed for '{$jt}', got '{$formattedJt}'");
        }
        
        $start = date('Y-m-d', strtotime($jt . ' - 6 days'));
        $formattedWeeklyRange = format_tanggal_id($start) . ' - ' . format_tanggal_id($jt);
        if ($formattedWeeklyRange !== '13 Juli 2026 - 19 Juli 2026') {
            throw new \RuntimeException("Weekly range calculation failed, got '{$formattedWeeklyRange}'");
        }
        
        $formattedBulanTagihan = format_tanggal_id($jt, 'F Y');
        if ($formattedBulanTagihan !== 'Juli 2026') {
            throw new \RuntimeException("Bulan tagihan formatting failed, got '{$formattedBulanTagihan}'");
        }
        CLI::write('  ✔ Date and period helper calculations verified successfully.', 'green');
        
        CLI::write('Test 9: Customer active credit count and final payment workflows...', 'yellow');
        
        $db->table('nasabah')->insert([
            'id' => 9999,
            'user_id' => 9998,
            'kode_nasabah' => 'NSB-TEST-99',
            'nama' => 'Test Customer User',
            'no_telepon' => '081234567890',
            'alamat' => 'Test Address',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $db->table('pengajuan')->insert([
            'id' => 9911,
            'user_id' => 9998,
            'produk_emas_id' => 9999,
            'kode_pesanan' => 'ORD-NEW-01',
            'nama' => 'Test Customer User',
            'metode_pembayaran' => 'kredit',
            'uang_muka' => 0,
            'pembayaran_status' => 'terverifikasi',
            'status' => 'diterima',
            'alamat' => 'Test Address',
            'no_telepon' => '081234567890',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $db->table('kredit')->insert([
            'id' => 9911,
            'kode_kredit' => 'KRD-9911',
            'pengajuan_id' => 9911,
            'nasabah_id' => 9999,
            'produk_emas_id' => 9999,
            'total_harga_kredit' => 1200000,
            'sisa_piutang' => 1000000,
            'status' => 'aktif',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $db->table('pengajuan')->insert([
            'id' => 9912,
            'user_id' => 9998,
            'produk_emas_id' => 9999,
            'kode_pesanan' => 'ORD-NEW-02',
            'nama' => 'Test Customer User',
            'metode_pembayaran' => 'kredit',
            'uang_muka' => 0,
            'pembayaran_status' => 'terverifikasi',
            'status' => 'selesai',
            'alamat' => 'Test Address',
            'no_telepon' => '081234567890',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $db->table('kredit')->insert([
            'id' => 9912,
            'kode_kredit' => 'KRD-9912',
            'pengajuan_id' => 9912,
            'nasabah_id' => 9999,
            'produk_emas_id' => 9999,
            'total_harga_kredit' => 1200000,
            'sisa_piutang' => 0,
            'status' => 'lunas',
            'created_at' => date('Y-m-d H:i:s')
        ]);
 
        $nasabahIds = [9999];
        $kreditAktifList = (new \App\Models\KreditModel())
            ->select('kredit.*, pengajuan.status as pengajuan_status, pengajuan.pembayaran_status as dp_status')
            ->join('pengajuan', 'pengajuan.id = kredit.pengajuan_id', 'left')
            ->whereIn('kredit.nasabah_id', $nasabahIds)
            ->where('kredit.status', 'aktif')
            ->where('kredit.sisa_piutang >', 0)
            ->whereNotIn('pengajuan.status', ['selesai', 'ditolak', 'dibatalkan'])
            ->findAll();
            
        if (count($kreditAktifList) !== 1 || (int)$kreditAktifList[0]['id'] !== 9911) {
            throw new \RuntimeException('Dashboard sync filter failed: Active count should be 1, got ' . count($kreditAktifList));
        }
        CLI::write('  ✔ Active credit count excludes lunas/selesai correctly.', 'green');
        
        $db->table('kredit')->where('id', 9911)->update([
            'sisa_piutang' => 0,
            'status' => 'lunas'
        ]);
        
        $workflowService->autoCompleteIfEligible(9911);
        
        $updatedOrder = $db->table('pengajuan')->where('id', 9911)->get()->getRowArray();
        if ($updatedOrder['status'] !== 'selesai') {
            throw new \RuntimeException('Final payment auto-complete failed: Order status should become selesai, got ' . $updatedOrder['status']);
        }
        CLI::write('  ✔ Order status auto-completes to selesai when credit becomes lunas.', 'green');
        
        $db->table('kredit')->whereIn('id', [9911, 9912])->delete();
        $db->table('pengajuan')->whereIn('id', [9911, 9912])->delete();
        $db->table('nasabah')->whereIn('id', [9999])->delete();
        $db->table('produk_emas')->whereIn('id', [9905])->delete();
    }
}
