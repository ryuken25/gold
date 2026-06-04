<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPesananFieldsToPengajuan extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('pengajuan', [
            'kode_pesanan' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'default'    => null,
                'after'      => 'id',
            ],
            'metode_konfirmasi' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'default'    => null,
                'after'      => 'metode_pembayaran',
            ],
            'waktu_sesi' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
                'comment' => 'Jadwal kedatangan / akad pelanggan',
            ],
            'reminder_sesi_terkirim' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
            ],
        ]);

        // Tambah status 'dibatalkan'
        $this->forge->modifyColumn('pengajuan', [
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['baru', 'diproses', 'disetujui', 'ditolak', 'dibatalkan', 'selesai'],
                'default'    => 'baru',
                'null'       => false,
            ],
        ]);

        // Index unik untuk kode_pesanan (NULL boleh berulang di MySQL).
        $this->db->query('ALTER TABLE pengajuan ADD UNIQUE INDEX idx_kode_pesanan (kode_pesanan)');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE pengajuan DROP INDEX idx_kode_pesanan');

        $this->db->query("UPDATE pengajuan SET status = 'ditolak' WHERE status = 'dibatalkan'");
        $this->forge->modifyColumn('pengajuan', [
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['baru', 'diproses', 'disetujui', 'ditolak', 'selesai'],
                'default'    => 'baru',
                'null'       => false,
            ],
        ]);

        $this->forge->dropColumn('pengajuan', ['kode_pesanan', 'metode_konfirmasi', 'waktu_sesi', 'reminder_sesi_terkirim']);
    }
}
