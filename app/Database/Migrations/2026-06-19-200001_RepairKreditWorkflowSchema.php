<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RepairKreditWorkflowSchema extends Migration
{
    public function up()
    {
        $db = $this->db;

        // 1. pengajuan_id — tambah jika belum ada
        if (!$this->colExists('kredit', 'pengajuan_id')) {
            $db->query("ALTER TABLE `kredit` ADD COLUMN `pengajuan_id` INT UNSIGNED NULL AFTER `id`");
            $db->query("ALTER TABLE `kredit` ADD INDEX `idx_kredit_pengajuan_id` (`pengajuan_id`)");
        }

        // 2. uang_muka — tambah jika belum ada
        if (!$this->colExists('kredit', 'uang_muka')) {
            $db->query("ALTER TABLE `kredit` ADD COLUMN `uang_muka` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `total_harga_kredit`");
        }

        // 3. sisa_pokok_kredit — tambah jika belum ada
        if (!$this->colExists('kredit', 'sisa_pokok_kredit')) {
            $db->query("ALTER TABLE `kredit` ADD COLUMN `sisa_pokok_kredit` DECIMAL(15,2) NULL AFTER `uang_muka`");
        }

        // 4. Backfill sisa_pokok_kredit
        $db->query("UPDATE `kredit` SET `sisa_pokok_kredit` = GREATEST(`total_harga_kredit` - `uang_muka`, 0) WHERE `sisa_pokok_kredit` IS NULL");

        // 5. Unique constraint on pengajuan_id — only if no duplicates
        if (!$this->idxExists('kredit', 'uniq_kredit_pengajuan')) {
            $dups = $db->query("SELECT pengajuan_id, COUNT(*) as c FROM kredit WHERE pengajuan_id IS NOT NULL GROUP BY pengajuan_id HAVING c > 1")->getResultArray();
            if (empty($dups)) {
                try {
                    $db->query("ALTER TABLE `kredit` ADD UNIQUE INDEX `uniq_kredit_pengajuan` (`pengajuan_id`)");
                } catch (\Throwable $e) {
                    log_message('warning', 'Skipping uniq_kredit_pengajuan: ' . $e->getMessage());
                }
            } else {
                log_message('warning', 'Skipping uniq_kredit_pengajuan — ' . count($dups) . ' duplicates found');
            }
        }
    }

    public function down()
    {
        // Additive only — no downgrade
    }

    protected function colExists(string $table, string $col): bool
    {
        $r = $this->db->query("SELECT COUNT(*) as c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?", [$table, $col])->getRow();
        return $r && $r->c > 0;
    }

    protected function idxExists(string $table, string $idx): bool
    {
        $r = $this->db->query("SELECT COUNT(*) as c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?", [$table, $idx])->getRow();
        return $r && $r->c > 0;
    }
}
