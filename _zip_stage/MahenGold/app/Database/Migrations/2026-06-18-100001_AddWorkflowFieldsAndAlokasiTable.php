<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * UPDATED: Tambah kolom workflow pengajuan + tabel pembayaran_alokasi + index.
 * Additive only — aman untuk database existing.
 */
class AddWorkflowFieldsAndAlokasiTable extends Migration
{
    public function up()
    {
        // 1. Tambah kolom workflow ke pengajuan
        $this->forge->addColumn('pengajuan', [
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
        ]);

        // 2. Tambah index ke pengajuan
        $this->forge->addKey('pengajuan', 'status');
        $this->forge->addKey('pengajuan', 'user_id');
        $this->forge->addKey('pengajuan', 'created_at');
        $this->forge->addKey('pengajuan', 'metode_pembayaran');
        $this->forge->addKey('pengajuan', 'pembayaran_status');
        $this->forge->createTable('pengajuan', true);

        // 3. Tambah kolom bukti_pembayaran_id ke pembayaran_angsuran
        $this->forge->addColumn('pembayaran_angsuran', [
            'bukti_pembayaran_id' => ['type' => 'int', 'constraint' => 11, 'null' => true, 'after' => 'jadwal_angsuran_id'],
        ]);

        // 4. Tabel pembayaran_alokasi
        $this->forge->addField([
            'id'                    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'pembayaran_angsuran_id'=> ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'jadwal_angsuran_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'nominal_alokasi'       => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'created_at'            => ['type' => 'datetime', 'null' => true],
            'updated_at'            => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('pembayaran_angsuran_id');
        $this->forge->addKey('jadwal_angsuran_id');
        $this->forge->createTable('pembayaran_alokasi');

        // 5. Tambah unique constraint ke kredit.pengajuan_id
        $this->db->query('ALTER TABLE kredit ADD UNIQUE INDEX idx_pengajuan_unique (pengajuan_id)');

        // 6. Tabel reminder_angsuran_logs
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

    public function down()
    {
        $this->forge->dropTable('reminder_angsuran_logs', true);
        $this->forge->dropTable('pembayaran_alokasi', true);
        $this->db->query('ALTER TABLE kredit DROP INDEX idx_pengajuan_unique');

        // Drop columns from pengajuan
        $cols = ['metode_pengiriman','referensi_pengiriman','diverifikasi_pada','diverifikasi_oleh',
                 'dikirim_pada','dikirim_oleh','selesai_pada','selesai_oleh','ditolak_pada','ditolak_oleh'];
        foreach ($cols as $col) {
            $this->forge->dropColumn('pengajuan', $col);
        }

        $this->forge->dropColumn('pembayaran_angsuran', 'bukti_pembayaran_id');
    }
}
