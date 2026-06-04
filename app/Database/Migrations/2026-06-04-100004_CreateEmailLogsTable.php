<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmailLogsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'tipe' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => false,
                'comment'    => 'pesanan_dibuat | pesanan_diverifikasi | reminder_sesi',
            ],
            'tujuan_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 190,
                'null'       => false,
            ],
            'nama_tujuan' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'subjek' => [
                'type'       => 'VARCHAR',
                'constraint' => 190,
                'null'       => true,
            ],
            'body' => [
                'type' => 'MEDIUMTEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['terkirim', 'gagal'],
                'default'    => 'terkirim',
            ],
            'error' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'related_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'related_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['related_type', 'related_id']);
        $this->forge->addKey('tipe');

        $this->forge->createTable('email_logs', false, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('email_logs', true);
    }
}
