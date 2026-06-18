<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class DebugKredit extends BaseCommand
{
    protected $group = 'debug';
    protected $name = 'debug:kredit';
    protected $description = 'Debug kredit creation issues';

    public function run(array $params)
    {
        $db = Database::connect();

        CLI::write('=== KREDIT TABLE STRUCTURE ===', 'cyan');
        $cols = $db->query("SHOW COLUMNS FROM kredit")->getResultArray();
        foreach ($cols as $c) {
            CLI::write("  {$c['Field']} | {$c['Type']} | Nullable: {$c['Null']} | Key: {$c['Key']}", 'white');
        }

        CLI::newLine();
        CLI::write('=== UNIQUE INDEXES ON KREDIT ===', 'cyan');
        $idx = $db->query("SHOW INDEX FROM kredit WHERE Non_unique = 0")->getResultArray();
        if ($idx) {
            foreach ($idx as $i) {
                CLI::write("  {$i['Column_name']} ({$i['Index_name']})", 'yellow');
            }
        } else {
            CLI::write('  No unique indexes found', 'green');
        }

        CLI::newLine();
        CLI::write('=== DUPLICATE pengajuan_id ===', 'cyan');
        $dups = $db->query("SELECT pengajuan_id, COUNT(*) as cnt FROM kredit WHERE pengajuan_id IS NOT NULL GROUP BY pengajuan_id HAVING cnt > 1")->getResultArray();
        if ($dups) {
            foreach ($dups as $d) {
                CLI::write("  pengajuan_id={$d['pengajuan_id']} → {$d['cnt']} kredit", 'red');
            }
        } else {
            CLI::write('  No duplicates', 'green');
        }

        CLI::newLine();
        CLI::write('=== KREDIT FOR PENGAJUAN 4 ===', 'cyan');
        $k = $db->query("SELECT id, kode_kredit, pengajuan_id, status, nasabah_id FROM kredit WHERE pengajuan_id = 4")->getResultArray();
        if ($k) {
            foreach ($k as $r) {
                CLI::write("  ID={$r['id']} kode={$r['kode_kredit']} status={$r['status']} nasabah={$r['nasabah_id']}", 'yellow');
            }
        } else {
            CLI::write('  No kredit for pengajuan 4', 'green');
        }

        CLI::newLine();
        CLI::write('=== ALL KREDIT ===', 'cyan');
        $all = $db->query("SELECT id, kode_kredit, pengajuan_id, status FROM kredit ORDER BY id DESC LIMIT 10")->getResultArray();
        foreach ($all as $r) {
            CLI::write("  ID={$r['id']} kode={$r['kode_kredit']} pengajuan={$r['pengajuan_id']} status={$r['status']}", 'white');
        }

        CLI::newLine();
        CLI::write('=== PENGAJUAN 4 STATUS ===', 'cyan');
        $p = $db->query("SELECT id, kode_pesanan, status, metode_pembayaran, pembayaran_status FROM pengajuan WHERE id = 4")->getRowArray();
        if ($p) {
            CLI::write("  status={$p['status']} metode={$p['metode_pembayaran']} bayar={$p['pembayaran_status']}", 'white');
        } else {
            CLI::write('  Pengajuan 4 not found', 'red');
        }
    }
}
