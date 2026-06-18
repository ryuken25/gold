<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePengajuanTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
                'comment'  => 'null = pengajuan anonim via WA lama',
            ],
            'produk_emas_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'metode_pembayaran' => [
                'type'       => 'ENUM',
                'constraint' => ['cash', 'kredit'],
                'null'       => false,
            ],
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'no_telepon' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'default'    => null,
            ],
            'alamat' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'tenor_bulan' => [
                'type'    => 'TINYINT',
                'null'    => true,
                'default' => null,
            ],
            'periode_angsuran' => [
                'type'       => 'ENUM',
                'constraint' => ['bulanan', 'mingguan'],
                'null'       => true,
                'default'    => null,
            ],
            'foto_ktp' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['baru', 'diproses', 'disetujui', 'dikirim', 'ditolak', 'dibatalkan', 'selesai'],
                'default'    => 'baru',
                'null'       => false,
            ],
            'catatan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->addKey('user_id');

        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('produk_emas_id', 'produk_emas', 'id', 'RESTRICT', 'CASCADE');

        $this->forge->createTable('pengajuan', false, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('pengajuan', true);
    }
}
