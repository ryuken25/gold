<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class AuditKreditSchema extends BaseCommand
{
    protected $group = 'Audit';

    protected $name = 'audit:kredit-schema';

    protected $description = 'Verify kredit table schema has required columns for workflow.';

    protected $usage = 'audit:kredit-schema';

    public function run(array $params)
    {
        $db = Database::connect();
        $errors = [];

        $requiredCols = [
            'pengajuan_id'       => 'INT UNSIGNED NULL — links to pengajuan',
            'uang_muka'          => 'DECIMAL(15,2) — down payment amount',
            'sisa_pokok_kredit'  => 'DECIMAL(15,2) — principal after DP',
            'total_terbayar'     => 'DECIMAL(15,2) — total paid so far',
            'sisa_piutang'       => 'DECIMAL(15,2) — remaining receivable',
            'status'             => 'VARCHAR(20) — aktif/lunas/dibatalkan',
        ];

        CLI::write('Kredit Schema Audit', 'cyan');
        CLI::write(str_repeat('=', 60), 'cyan');

        foreach ($requiredCols as $col => $desc) {
            $result = $db->query(
                "SELECT COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kredit' AND COLUMN_NAME = ?",
                [$col]
            )->getRow();

            if (!$result) {
                $errors[] = "MISSING: kredit.{$col} — {$desc}";
                CLI::write("  ✗ {$col} — MISSING ({$desc})", 'red');
            } else {
                CLI::write("  ✓ {$col} — {$result->COLUMN_TYPE} (nullable: {$result->IS_NULLABLE})", 'green');
            }
        }

        // Check unique constraint on pengajuan_id
        $hasUnique = $db->query(
            "SELECT COUNT(*) as cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kredit' AND INDEX_NAME = 'uniq_kredit_pengajuan'"
        )->getRow();

        if ($hasUnique && $hasUnique->cnt > 0) {
            CLI::write("  ✓ uniq_kredit_pengajuan — EXISTS", 'green');
        } else {
            $errors[] = "UNIQUE INDEX uniq_kredit_pengajuan missing on kredit.pengajuan_id";
            CLI::write("  ⚠ uniq_kredit_pengajuan — not found (may cause duplicate credits)", 'yellow');
        }

        // Check pembayaran_alokasi schema
        $hasAlokasi = $db->query("SHOW TABLES LIKE 'pembayaran_alokasi'")->getRowArray();
        if ($hasAlokasi) {
            $nominalType = $db->query(
                "SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pembayaran_alokasi' AND COLUMN_NAME = 'nominal_alokasi'"
            )->getRow();
            if ($nominalType && strtolower($nominalType->DATA_TYPE) === 'decimal') {
                CLI::write("  ✓ pembayaran_alokasi.nominal_alokasi — DECIMAL", 'green');
            } else {
                $errors[] = "pembayaran_alokasi.nominal_alokasi should be DECIMAL(15,2), got: " . ($nominalType->DATA_TYPE ?? 'MISSING');
                CLI::write("  ✗ pembayaran_alokasi.nominal_alokasi — NOT DECIMAL", 'red');
            }
        } else {
            $errors[] = "pembayaran_alokasi table missing";
            CLI::write("  ✗ pembayaran_alokasi — TABLE MISSING", 'red');
        }

        CLI::newLine();

        if (!empty($errors)) {
            CLI::error('Schema audit FAILED — ' . count($errors) . ' issue(s).');
            return;
        }

        CLI::write('Schema audit PASSED.', 'green');
    }
}
