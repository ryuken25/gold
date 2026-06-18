<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBuktiPembayaranTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'kode' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'tipe' => [
                'type'       => 'ENUM',
                'constraint' => ['cash', 'cicilan', 'dp'],
                'null'       => false,
            ],
            'pengajuan_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
            ],
            'kredit_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
            ],
            'jadwal_angsuran_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'nominal' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['menunggu', 'terverifikasi', 'ditolak'],
                'default'    => 'menunggu',
            ],
            'catatan_admin' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'diverifikasi_oleh' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
            ],
            'diverifikasi_pada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('kode');
        $this->forge->addKey('status');
        $this->forge->addKey('tipe');

        $this->forge->addForeignKey('pengajuan_id', 'pengajuan', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('kredit_id', 'kredit', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('jadwal_angsuran_id', 'jadwal_angsuran', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('diverifikasi_oleh', 'users', 'id', 'SET NULL', 'CASCADE');

        $this->forge->createTable('bukti_pembayaran', false, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('bukti_pembayaran', true);
    }
}
