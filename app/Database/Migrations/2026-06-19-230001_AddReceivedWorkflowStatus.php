<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReceivedWorkflowStatus extends Migration
{
    public function up()
    {
        $db = $this->db;

        $db->query("ALTER TABLE `pengajuan` MODIFY COLUMN `status` ENUM('baru', 'diproses', 'disetujui', 'dikirim', 'diterima', 'ditolak', 'dibatalkan', 'selesai') NOT NULL DEFAULT 'baru'");

        if (!$this->fieldExists('pengajuan', 'diterima_pada')) {
            $db->query("ALTER TABLE `pengajuan` ADD COLUMN `diterima_pada` DATETIME NULL DEFAULT NULL AFTER `dikirim_oleh`");
        }

        if (!$this->fieldExists('pengajuan', 'diterima_oleh')) {
            $db->query("ALTER TABLE `pengajuan` ADD COLUMN `diterima_oleh` INT UNSIGNED NULL DEFAULT NULL AFTER `diterima_pada`");
        }

        $this->addIndexIfMissing('pengajuan', 'idx_pengajuan_status', 'status');
        $this->addIndexIfMissing('pengajuan', 'idx_pengajuan_diterima_pada', 'diterima_pada');

        if (!$this->fkExists('pengajuan', 'fk_pengajuan_diterima_oleh')) {
            try {
                $db->query("ALTER TABLE `pengajuan` ADD CONSTRAINT `fk_pengajuan_diterima_oleh` FOREIGN KEY (`diterima_oleh`) REFERENCES `users`(`id`) ON DELETE SET NULL");
            } catch (\Throwable $e) {
                log_message('warning', 'Skipping fk_pengajuan_diterima_oleh: ' . $e->getMessage());
            }
        }
    }

    public function down()
    {
        // Non-destructive. Keeping received metadata is safer for rollback.
    }

    protected function fieldExists(string $table, string $column): bool
    {
        $result = $this->db->query(
            "SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        )->getRow();

        return $result && (int) $result->cnt > 0;
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        $result = $this->db->query(
            "SELECT COUNT(*) as cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [$table, $indexName]
        )->getRow();

        return $result && (int) $result->cnt > 0;
    }

    protected function fkExists(string $table, string $fkName): bool
    {
        $result = $this->db->query(
            "SELECT COUNT(*) as cnt FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table, $fkName]
        )->getRow();

        return $result && (int) $result->cnt > 0;
    }

    protected function addIndexIfMissing(string $table, string $indexName, string $column): void
    {
        if (!$this->indexExists($table, $indexName)) {
            try {
                $this->db->query("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (`{$column}`)");
            } catch (\Throwable $e) {
                log_message('warning', "Skipping index {$indexName}: " . $e->getMessage());
            }
        }
    }
}
