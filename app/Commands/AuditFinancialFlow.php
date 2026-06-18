<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class AuditFinancialFlow extends BaseCommand
{
    protected $group       = 'audit';
    protected $name        = 'audit:financial-flow';
    protected $description = 'Audit konsistensi data keuangan (read-only default)';

    public function run(array $params)
    {
        $fix = in_array('--fix', $params, true);
        $db = Database::connect();
        $issues = [];

        CLI::write('═══════════════════════════════════════════', 'cyan');
        CLI::write('FINANCIAL FLOW AUDIT' . ($fix ? ' (--fix mode)' : ''), 'cyan');
        CLI::write('═══════════════════════════════════════════', 'cyan');
        CLI::newLine();

        // 1. Duplicate kredit per pengajuan
        $dups = $db->query("SELECT pengajuan_id, COUNT(*) as cnt FROM kredit WHERE pengajuan_id IS NOT NULL GROUP BY pengajuan_id HAVING cnt > 1")->getResultArray();
        if ($dups) {
            foreach ($dups as $d) {
                $issues[] = "Duplicate kredit untuk pengajuan {$d['pengajuan_id']} ({$d['cnt']} kredit)";
            }
        }

        // 2. Kredit tanpa jadwal
        $noSchedule = $db->query("SELECT k.id, k.kode_kredit FROM kredit k LEFT JOIN jadwal_angsuran j ON j.kredit_id = k.id WHERE j.id IS NULL AND k.status = 'aktif'")->getResultArray();
        foreach ($noSchedule as $r) {
            $issues[] = "Kredit {$r['kode_kredit']} (ID:{$r['id']}) aktif tanpa jadwal angsuran";
        }

        // 3. Pembayaran tanpa alokasi
        $noAlloc = $db->query("SELECT pa.id, pa.kode_pembayaran FROM pembayaran_angsuran pa LEFT JOIN pembayaran_alokasi pa2 ON pa2.pembayaran_angsuran_id = pa.id WHERE pa2.id IS NULL")->getResultArray();
        foreach ($noAlloc as $r) {
            $issues[] = "Pembayaran {$r['kode_pembayaran']} (ID:{$r['id']}) tanpa alokasi";
        }

        // 4. Alokasi sum != payment amount
        $mismatch = $db->query("
            SELECT pa.id, pa.kode_pembayaran, pa.nominal_bayar, COALESCE(SUM(pa2.nominal_alokasi), 0) as total_alokasi
            FROM pembayaran_angsuran pa
            LEFT JOIN pembayaran_alokasi pa2 ON pa2.pembayaran_angsuran_id = pa.id
            GROUP BY pa.id
            HAVING total_alokasi != pa.nominal_bayar
        ")->getResultArray();
        foreach ($mismatch as $r) {
            $issues[] = "Pembayaran {$r['kode_pembayaran']} alokasi {$r['total_alokasi']} != nominal {$r['nominal_bayar']}";
        }

        // 5. Saldo kredit tidak konsisten
        $unbalanced = $db->query("
            SELECT k.id, k.kode_kredit, k.total_terbayar, k.sisa_piutang, k.sisa_pokok_kredit,
                   (k.total_terbayar + k.sisa_piutang) as calculated
            FROM kredit k
            WHERE k.status != 'dibatalkan'
            AND ABS((k.total_terbayar + k.sisa_piutang) - COALESCE(k.sisa_pokok_kredit, k.total_harga_kredit)) > 1
        ")->getResultArray();
        foreach ($unbalanced as $r) {
            $issues[] = "Saldo tidak konsisten: {$r['kode_kredit']} — terbayar+piutang={$r['calculated']} vs sisa_pokok={$r['sisa_pokok_kredit']}";
        }

        // 6. Sisa negatif
        $negative = $db->query("SELECT id, kode_kredit, sisa_piutang FROM kredit WHERE sisa_piutang < 0")->getResultArray();
        foreach ($negative as $r) {
            $issues[] = "Sisa piutang negatif: {$r['kode_kredit']} = {$r['sisa_piutang']}";
        }

        // 7. Lunas tapi sisa > 0
        $lunasButOwed = $db->query("SELECT id, kode_kredit, sisa_piutang FROM kredit WHERE status = 'lunas' AND sisa_piutang > 0")->getResultArray();
        foreach ($lunasButOwed as $r) {
            $issues[] = "Status lunas tapi sisa piutang > 0: {$r['kode_kredit']} = {$r['sisa_piutang']}";
        }

        // 8. Aktif tapi sisa = 0
        $activeButZero = $db->query("SELECT id, kode_kredit, sisa_piutang FROM kredit WHERE status = 'aktif' AND sisa_piutang = 0")->getResultArray();
        foreach ($activeButZero as $r) {
            $issues[] = "Status aktif tapi sisa piutang = 0: {$r['kode_kredit']}";
            if ($fix) {
                $db->query("UPDATE kredit SET status = 'lunas' WHERE id = {$r['id']}");
                CLI::write("  → Fixed: {$r['kode_kredit']} → lunas", 'green');
            }
        }

        // 9. Dikirim tanpa referensi / detail pengiriman lengkap
        $shippedNoDetails = $db->query("SELECT id, kode_pesanan FROM pengajuan WHERE status = 'dikirim' AND (metode_pengiriman IS NULL OR metode_pengiriman = '' OR referensi_pengiriman IS NULL OR referensi_pengiriman = '')")->getResultArray();
        foreach ($shippedNoDetails as $r) {
            $issues[] = "Pesanan dikirim tanpa detail pengiriman lengkap (metode atau referensi kosong): {$r['kode_pesanan']}";
        }

        // 10. Selesai tanpa metadata pengiriman
        $doneNoShip = $db->query("SELECT id, kode_pesanan FROM pengajuan WHERE status = 'selesai' AND (dikirim_pada IS NULL OR dikirim_oleh IS NULL OR metode_pengiriman IS NULL OR referensi_pengiriman IS NULL)")->getResultArray();
        foreach ($doneNoShip as $r) {
            $issues[] = "Pesanan selesai tapi belum pernah melalui proses kirim (detail/metadata pengiriman kosong): {$r['kode_pesanan']}";
        }

        // 10b. Dikirim activity matches dikirim/selesai status
        $shippedActivityMismatch = $db->query("
            SELECT DISTINCT p.id, p.kode_pesanan, p.status
            FROM pengajuan p
            JOIN pengajuan_aktivitas pa ON pa.pengajuan_id = p.id
            WHERE pa.aksi = 'dikirim' AND p.status NOT IN ('dikirim', 'selesai')
        ")->getResultArray();
        foreach ($shippedActivityMismatch as $r) {
            $issues[] = "Inkonsistensi: Pesanan {$r['kode_pesanan']} memiliki aktivitas 'dikirim' tetapi status saat ini adalah '{$r['status']}'";
        }

        // 11. Kredit tanpa pengajuan
        $creditNoOrder = $db->query("SELECT k.id, k.kode_kredit FROM kredit k LEFT JOIN pengajuan p ON p.id = k.pengajuan_id WHERE k.pengajuan_id IS NOT NULL AND p.id IS NULL")->getResultArray();
        foreach ($creditNoOrder as $r) {
            $issues[] = "Kredit {$r['kode_kredit']} merujuk pengajuan tidak ada";
        }

        // 12. Reminder duplicate
        $dupReminder = $db->query("
            SELECT jadwal_angsuran_id, jenis, tanggal_referensi, channel, COUNT(*) as cnt
            FROM reminder_angsuran_logs
            GROUP BY jadwal_angsuran_id, jenis, tanggal_referensi, channel
            HAVING cnt > 1
        ")->getResultArray();
        foreach ($dupReminder as $r) {
            $issues[] = "Reminder duplikat: jadwal={$r['jadwal_angsuran_id']} jenis={$r['jenis']} tgl={$r['tanggal_referensi']}";
        }

        // 13. Pengajuan kredit disetujui tanpa kredit
        $noCredit = $db->query("
            SELECT p.id, p.kode_pesanan FROM pengajuan p
            LEFT JOIN kredit k ON k.pengajuan_id = p.id
            WHERE p.metode_pembayaran = 'kredit' AND p.status = 'disetujui' AND k.id IS NULL
        ")->getResultArray();
        foreach ($noCredit as $r) {
            $issues[] = "Pengajuan kredit disetujui tanpa kredit: {$r['kode_pesanan']}";
        }

        // 14. Pesanan dikirim/selesai tapi DP belum terverifikasi (kredit)
        $dpPendingShipped = $db->query("
            SELECT id, kode_pesanan, status, pembayaran_status 
            FROM pengajuan 
            WHERE metode_pembayaran = 'kredit' 
            AND uang_muka > 0 
            AND status IN ('dikirim', 'selesai') 
            AND pembayaran_status != 'terverifikasi'
        ")->getResultArray();
        foreach ($dpPendingShipped as $r) {
            $issues[] = "Pesanan {$r['kode_pesanan']} berstatus {$r['status']} tapi DP belum terverifikasi";
        }

        // 15. Pesanan cash dikirim/selesai tapi pembayaran belum terverifikasi
        $cashUnpaid = $db->query("
            SELECT id, kode_pesanan, status, pembayaran_status 
            FROM pengajuan 
            WHERE metode_pembayaran = 'cash' 
            AND status IN ('dikirim', 'selesai') 
            AND pembayaran_status != 'terverifikasi'
        ")->getResultArray();
        foreach ($cashUnpaid as $r) {
            $issues[] = "Pesanan cash {$r['kode_pesanan']} berstatus {$r['status']} tapi pembayaran belum terverifikasi";
        }

        // 16. Total terbayar di kredit tidak cocok dengan jumlah pembayaran terverifikasi
        $mismatchedKreditPayments = $db->query("
            SELECT k.id, k.kode_kredit, k.total_terbayar, COALESCE(SUM(pa.nominal_bayar), 0) as real_terbayar
            FROM kredit k
            LEFT JOIN pembayaran_angsuran pa ON pa.kredit_id = k.id
            GROUP BY k.id
            HAVING k.total_terbayar != real_terbayar
        ")->getResultArray();
        foreach ($mismatchedKreditPayments as $r) {
            $issues[] = "Total terbayar kredit {$r['kode_kredit']} ({$r['total_terbayar']}) tidak cocok dengan jumlah pembayaran ({$r['real_terbayar']})";
        }
        
        // 17. Audit nama & kode skenario/demo yang dilarang di UI
        $forbiddenNames = ['Demo Pelanggan', 'DP Pending', 'DP Ready', 'Cash Pending', 'Cash Ready', 'Overdue', 'Lunas', 'Rejected', 'Shipped'];
        
        foreach ($forbiddenNames as $name) {
            $badUsers = $db->table('users')->like('nama', $name)->get()->getResultArray();
            foreach ($badUsers as $u) {
                $issues[] = "Nama user mengandung label skenario: {$u['nama']} (ID: {$u['id']})";
            }
        }
        
        foreach ($forbiddenNames as $name) {
            $badNasabahs = $db->table('nasabah')->like('nama', $name)->get()->getResultArray();
            foreach ($badNasabahs as $n) {
                $issues[] = "Nama nasabah mengandung label skenario: {$n['nama']} (ID: {$n['id']})";
            }
        }

        foreach ($forbiddenNames as $name) {
            $badPengajuans = $db->table('pengajuan')->like('nama', $name)->get()->getResultArray();
            foreach ($badPengajuans as $p) {
                $issues[] = "Nama pengajuan mengandung label skenario: {$p['nama']} (ID: {$p['id']})";
            }
        }

        $badOrderCodes = $db->query("SELECT id, kode_pesanan FROM pengajuan WHERE kode_pesanan LIKE '%MG-DEMO%' OR kode_pesanan LIKE '%MG-KR%'")->getResultArray();
        foreach ($badOrderCodes as $r) {
            $issues[] = "Kode pesanan mengandung label skenario: {$r['kode_pesanan']} (ID: {$r['id']})";
        }

        $badKreditCodes = $db->query("SELECT id, kode_kredit FROM kredit WHERE kode_kredit LIKE '%MG-DEMO%' OR kode_kredit LIKE '%MG-KR%'")->getResultArray();
        foreach ($badKreditCodes as $r) {
            $issues[] = "Kode kredit mengandung label skenario: {$r['kode_kredit']} (ID: {$r['id']})";
        }

        $kreditPattern = $db->query("SELECT id, kode_kredit FROM kredit")->getResultArray();
        foreach ($kreditPattern as $r) {
            if (!preg_match('/^KRD-\d{4,}$/', $r['kode_kredit'])) {
                $issues[] = "Kode kredit tidak sesuai format generator (KRD-xxxx): {$r['kode_kredit']} (ID: {$r['id']})";
            }
        }

        // Output
        CLI::newLine();
        if (empty($issues)) {
            CLI::write('✅ Tidak ada masalah ditemukan.', 'green');
        } else {
            CLI::write('⚠️  Ditemukan ' . count($issues) . ' masalah:', 'yellow');
            CLI::newLine();
            foreach ($issues as $i => $issue) {
                CLI::write('  ' . ($i + 1) . '. ' . $issue, 'red');
            }
        }

        CLI::newLine();
        CLI::write('═══════════════════════════════════════════', 'cyan');
        CLI::write('Total issues: ' . count($issues), empty($issues) ? 'green' : 'yellow');
        CLI::write('═══════════════════════════════════════════', 'cyan');
    }
}
