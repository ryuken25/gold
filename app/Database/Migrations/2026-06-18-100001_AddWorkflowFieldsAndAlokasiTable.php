<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * UPDATED: Tambah kolom workflow pengajuan + tabel pembayaran_alokasi + index.
 * Additive only — aman untuk database kosong, existing, dan rerun.
 */
class AddWorkflowFieldsAndAlokasiTable extends Migration
{
    public function up()
    {
        $db = $this->db;

        // 1. Tambah kolom workflow ke pengajuan (skip jika sudah ada)
        $pengajuanCols = [
            'metode_pengiriman'   => ['type' => 'varchar', 'constraint' => 20, 'null' => true, 'after' => 'pembayaran_status'],
            'referensi_pengiriman'=> ['type' => 'varchar', 'constraint' => 255, 'null' => true, 'after' => 'metode_pengiriman'],
            'diverifikasi_pada'   => ['type' => 'datetime', 'null' => true, 'after' => 'referensi_pengiriman'],
            'diverifikasi_oleh'   => ['type' => 'int', 'constraint' => 11, 'null' => true, 'after' => 'diverifikasi_pada'],
            'dikirim_pada'        => ['type' => 'datetime', 'null' => true, 'after' => 'diverifikasi_oleh'],
            'dikirim_oleh'        => ['type' => 'int', 'constraint' => 11, 'null' => true, 'after' => 'dikirim_pada'],
            'selesai_pada'        => ['type' => 'datetime', 'null' => true, 'after' => 'dikirim_oleh'],
            'selesai_oleh'        => ['type' => 'int', 'constraint' => 11, 'null' => true, 'after' => 'selesai_pada'],
            'ditolak_pada'        => ['type' => 'datetime', 'null' => true, 'after' => 'selesai_oleh'],
            'ditolak_oleh'        => ['type' => 'int', 'constraint' => 11, 'null' => true, 'after' => 'ditolak_pada'],
        ];

        foreach ($pengajuanCols as $col => $def) {
            if (!$this->fieldExists('pengajuan', $col)) {
                $this->forge->addColumn('pengajuan', [$col => $def]);
            }
        }

        // 2. Index ke pengajuan — gunakan raw ALTER TABLE (bukan Forge createTable)
        $this->addIndexIfMissing('pengajuan', 'idx_pa_status', 'status');
        $this->addIndexIfMissing('pengajuan', 'idx_pa_user_id', 'user_id');
        $this->addIndexIfMissing('pengajuan', 'idx_pa_created_at', 'created_at');
        $this->addIndexIfMissing('pengajuan', 'idx_pa_metode_pembayaran', 'metode_pembayaran');
        $this->addIndexIfMissing('pengajuan', 'idx_pa_pembayaran_status', 'pembayaran_status');

        // 3. Tambah kolom bukti_pembayaran_id ke pembayaran_angsuran
        if (!$this->fieldExists('pembayaran_angsuran', 'bukti_pembayaran_id')) {
            $this->forge->addColumn('pembayaran_angsuran', [
                'bukti_pembayaran_id' => ['type' => 'int', 'constraint' => 11, 'null' => true, 'after' => 'jadwal_angsuran_id'],
            ]);
        }

        // 4. Tabel pembayaran_alokasi — create if not exists
        $hasAlokasi = $db->query("SHOW TABLES LIKE 'pembayaran_alokasi'")->getRowArray();
        if (!$hasAlokasi) {
            $this->forge->addField([
                'id'                     => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'pembayaran_angsuran_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
                'jadwal_angsuran_id'     => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
                'nominal_alokasi'        => ['type' => 'decimal', 'constraint' => '15,2'],
                'created_at'             => ['type' => 'datetime', 'null' => true],
                'updated_at'             => ['type' => 'datetime', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('pembayaran_angsuran_id');
            $this->forge->addKey('jadwal_angsuran_id');
            $this->forge->createTable('pembayaran_alokasi');
        } else {
            // Fix nominal_alokasi to DECIMAL if it's INT
            $colType = $db->query(
                "SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pembayaran_alokasi' AND COLUMN_NAME = 'nominal_alokasi'"
            )->getRow();
            if ($colType && strtolower($colType->DATA_TYPE) === 'int') {
                $db->query("ALTER TABLE `pembayaran_alokasi` MODIFY COLUMN `nominal_alokasi` DECIMAL(15,2) NOT NULL");
            }
        }

        // 5. Unique constraint ke kredit.pengajuan_id — cek duplikat dulu
        if (!$this->indexExists('kredit', 'uniq_kredit_pengajuan')) {
            $dups = $db->query(
                "SELECT pengajuan_id, COUNT(*) as cnt FROM kredit WHERE pengajuan_id IS NOT NULL GROUP BY pengajuan_id HAVING cnt > 1"
            )->getResultArray();
            if (empty($dups)) {
                try {
                    $db->query("ALTER TABLE `kredit` ADD UNIQUE INDEX `uniq_kredit_pengajuan` (`pengajuan_id`)");
                } catch (\Throwable $e) {
                    log_message('warning', 'Skipping uniq_kredit_pengajuan: ' . $e->getMessage());
                }
            } else {
                log_message('warning', 'Skipping kredit.pengajuan_id unique — found ' . count($dups) . ' duplicates');
            }
        }

        // 6. Tabel reminder_angsuran_logs — create if not exists
        $hasReminder = $db->query("SHOW TABLES LIKE 'reminder_angsuran_logs'")->getRowArray();
        if (!$hasReminder) {
            $this->forge->addField([
                'id'                    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'kredit_id'             => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
                'jadwal_angsuran_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
                'user_id'               => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'nasabah_id'            => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'jenis'                 => ['type' => 'varchar', 'constraint' => 20],
                'channel'               => ['type' => 'varchar', 'constraint' => 20, 'default' => 'email'],
                'tujuan'                => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
                'subjek'                => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
                'pesan'                 => ['type' => 'text', 'null' => true],
                'status'                => ['type' => 'varchar', 'constraint' => 20, 'default' => 'menunggu'],
                'error'                 => ['type' => 'text', 'null' => true],
                'dikirim_oleh'          => ['type' => 'int', 'constraint' => 11, 'null' => true],
                'tanggal_referensi'     => ['type' => 'date'],
                'created_at'            => ['type' => 'datetime', 'null' => true],
                'updated_at'            => ['type' => 'datetime', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('kredit_id');
            $this->forge->addKey('jadwal_angsuran_id');
            $this->forge->createTable('reminder_angsuran_logs');
        }
    }

    public function down()
    {
        $db = $this->db;

        $this->forge->dropTable('reminder_angsuran_logs', true);
        $this->forge->dropTable('pembayaran_alokasi', true);

        if ($this->indexExists('kredit', 'uniq_kredit_pengajuan')) {
            $db->query('ALTER TABLE kredit DROP INDEX uniq_kredit_pengajuan');
        }

        // Drop columns from pengajuan
        $cols = ['metode_pengiriman','referensi_pengiriman','diverifikasi_pada','diverifikasi_oleh',
                 'dikirim_pada','dikirim_oleh','selesai_pada','selesai_oleh','ditolak_pada','ditolak_oleh'];
        foreach ($cols as $col) {
            if ($this->fieldExists('pengajuan', $col)) {
                $this->forge->dropColumn('pengajuan', $col);
            }
        }

        if ($this->fieldExists('pembayaran_angsuran', 'bukti_pembayaran_id')) {
            $this->forge->dropColumn('pembayaran_angsuran', 'bukti_pembayaran_id');
        }
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    protected function fieldExists(string $table, string $column): bool
    {
        $result = $this->db->query(
            "SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        )->getRow();
        return $result && $result->cnt > 0;
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        $result = $this->db->query(
            "SELECT COUNT(*) as cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [$table, $indexName]
        )->getRow();
        return $result && $result->cnt > 0;
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
