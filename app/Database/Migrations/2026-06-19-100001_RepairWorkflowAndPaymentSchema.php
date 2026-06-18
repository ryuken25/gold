<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RepairWorkflowAndPaymentSchema extends Migration
{
    public function up()
    {
        $db = $this->db;

        // ============================================================
        // 1. PENGAJUAN — workflow columns (additive, safe)
        // ============================================================
        $pengajuanCols = [
            'metode_pengiriman'    => "VARCHAR(20) NULL DEFAULT NULL AFTER `pembayaran_status`",
            'referensi_pengiriman' => "VARCHAR(255) NULL DEFAULT NULL AFTER `metode_pengiriman`",
            'diverifikasi_pada'    => "DATETIME NULL DEFAULT NULL AFTER `referensi_pengiriman`",
            'diverifikasi_oleh'    => "INT UNSIGNED NULL DEFAULT NULL AFTER `diverifikasi_pada`",
            'dikirim_pada'         => "DATETIME NULL DEFAULT NULL AFTER `diverifikasi_oleh`",
            'dikirim_oleh'         => "INT UNSIGNED NULL DEFAULT NULL AFTER `dikirim_pada`",
            'selesai_pada'         => "DATETIME NULL DEFAULT NULL AFTER `dikirim_oleh`",
            'selesai_oleh'         => "INT UNSIGNED NULL DEFAULT NULL AFTER `selesai_pada`",
            'ditolak_pada'         => "DATETIME NULL DEFAULT NULL AFTER `selesai_oleh`",
            'ditolak_oleh'         => "INT UNSIGNED NULL DEFAULT NULL AFTER `ditolak_pada`",
        ];

        foreach ($pengajuanCols as $col => $def) {
            if (!$this->fieldExists('pengajuan', $col)) {
                $db->query("ALTER TABLE `pengajuan` ADD COLUMN `{$col}` {$def}");
            }
        }

        // Indexes — use ADD INDEX (not createTable)
        $indexes = ['status', 'user_id', 'created_at', 'metode_pembayaran', 'pembayaran_status'];
        foreach ($indexes as $idx) {
            $existing = $db->query("SHOW INDEX FROM `pengajuan` WHERE Column_name = '{$idx}'")->getRowArray();
            if (!$existing) {
                $db->query("ALTER TABLE `pengajuan` ADD INDEX `idx_{$idx}` (`{$idx}`)");
            }
        }

        // Foreign keys for workflow nullable fields
        $fkCols = ['diverifikasi_oleh', 'dikirim_oleh', 'selesai_oleh', 'ditolak_oleh'];
        foreach ($fkCols as $fkCol) {
            $fkName = "fk_pengajuan_{$fkCol}";
            $check = $db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pengajuan' AND CONSTRAINT_NAME = '{$fkName}'")->getRow();
            if (!$check) {
                $db->query("ALTER TABLE `pengajuan` ADD CONSTRAINT `{$fkCol}` FOREIGN KEY (`{$fkCol}`) REFERENCES `users`(`id`) ON DELETE SET NULL");
            }
        }

        // ============================================================
        // 2. PEMBAYARAN ANGSURAN — tambah bukti_pembayaran_id
        // ============================================================
        if (!$this->fieldExists('pembayaran_angsuran', 'bukti_pembayaran_id')) {
            $db->query("ALTER TABLE `pembayaran_angsuran` ADD COLUMN `bukti_pembayaran_id` INT UNSIGNED NULL DEFAULT NULL AFTER `jadwal_angsuran_id`");
            $db->query("ALTER TABLE `pembayaran_angsuran` ADD UNIQUE INDEX `uniq_bukti_pembayaran` (`bukti_pembayaran_id`)");
            $db->query("ALTER TABLE `pembayaran_angsuran` ADD CONSTRAINT `fk_pembayaran_bukti` FOREIGN KEY (`bukti_pembayaran_id`) REFERENCES `bukti_pembayaran`(`id`) ON DELETE SET NULL");
        }

        // ============================================================
        // 3. PEMBAYARAN ALOKASI — recreate if needed (safe)
        // ============================================================
        $hasAlokasi = $db->query("SHOW TABLES LIKE 'pembayaran_alokasi'")->getRowArray();
        if (!$hasAlokasi) {
            $this->forge->addField([
                'id'                     => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'pembayaran_angsuran_id' => ['type' => 'INT', 'unsigned' => true],
                'jadwal_angsuran_id'     => ['type' => 'INT', 'unsigned' => true],
                'nominal_alokasi'        => ['type' => 'DECIMAL', 'constraint' => '15,2'],
                'created_at'             => ['type' => 'DATETIME', 'null' => true],
                'updated_at'             => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('pembayaran_angsuran_id');
            $this->forge->addKey('jadwal_angsuran_id');
            $this->forge->addUniqueKey(['pembayaran_angsuran_id', 'jadwal_angsuran_id']);
            $this->forge->createTable('pembayaran_alokasi');
        } else {
            // Fix nominal_alokasi to DECIMAL if it's INT
            $colType = $db->query("SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pembayaran_alokasi' AND COLUMN_NAME = 'nominal_alokasi'")->getRow();
            if ($colType && strtolower($colType->DATA_TYPE) === 'int') {
                $db->query("ALTER TABLE `pembayaran_alokasi` MODIFY COLUMN `nominal_alokasi` DECIMAL(15,2) NOT NULL");
            }
            // Add unique constraint if missing
            $uniqCheck = $db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pembayaran_alokasi' AND CONSTRAINT_TYPE = 'UNIQUE' AND CONSTRAINT_NAME LIKE '%alokasi%'")->getRow();
            if (!$uniqCheck) {
                $db->query("ALTER TABLE `pembayaran_alokasi` ADD UNIQUE INDEX `uniq_pembayaran_jadwal` (`pembayaran_angsuran_id`, `jadwal_angsuran_id`)");
            }
        }

        // ============================================================
        // 4. REMINDER ANGSURAN LOGS
        // ============================================================
        $hasReminder = $db->query("SHOW TABLES LIKE 'reminder_angsuran_logs'")->getRowArray();
        if (!$hasReminder) {
            $this->forge->addField([
                'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'kredit_id'           => ['type' => 'INT', 'unsigned' => true],
                'jadwal_angsuran_id'  => ['type' => 'INT', 'unsigned' => true],
                'user_id'             => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'nasabah_id'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'jenis'               => ['type' => 'VARCHAR', 'constraint' => 20],
                'channel'             => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'email'],
                'tujuan'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'subjek'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'pesan'               => ['type' => 'TEXT', 'null' => true],
                'status'              => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'menunggu'],
                'error'               => ['type' => 'TEXT', 'null' => true],
                'dikirim_oleh'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'tanggal_referensi'   => ['type' => 'DATE'],
                'created_at'          => ['type' => 'DATETIME', 'null' => true],
                'updated_at'          => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('kredit_id');
            $this->forge->addKey('jadwal_angsuran_id');
            $this->forge->addUniqueKey(['jadwal_angsuran_id', 'jenis', 'tanggal_referensi', 'channel']);
            $this->forge->createTable('reminder_angsuran_logs');
        }

        // ============================================================
        // 5. UNIQUE kredit.pengajuan_id — check duplicates first
        // ============================================================
        $hasUnique = $db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kredit' AND CONSTRAINT_TYPE = 'UNIQUE' AND CONSTRAINT_NAME LIKE '%pengajuan%'")->getRow();
        if (!$hasUnique) {
            // Check for duplicates
            $dups = $db->query("SELECT pengajuan_id, COUNT(*) as cnt FROM kredit WHERE pengajuan_id IS NOT NULL GROUP BY pengajuan_id HAVING cnt > 1")->getResultArray();
            if (empty($dups)) {
                $db->query("ALTER TABLE `kredit` ADD UNIQUE INDEX `uniq_kredit_pengajuan` (`pengajuan_id`)");
            } else {
                log_message('warning', 'Skipping kredit.pengajuan_id unique constraint — found ' . count($dups) . ' duplicates');
            }
        }
    }

    public function down()
    {
        // Downgrade not recommended for production — schema is additive
    }

    protected function fieldExists(string $table, string $column): bool
    {
        $result = $this->db->query(
            "SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        )->getRow();
        return $result && $result->cnt > 0;
    }
}
