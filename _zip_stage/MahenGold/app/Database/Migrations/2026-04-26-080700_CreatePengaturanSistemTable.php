<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePengaturanSistemTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nama_toko' => ['type' => 'VARCHAR', 'constraint' => 150, 'default' => 'MahenGold'],
            'nomor_whatsapp_toko' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => '6282146575233'],
            'margin_default' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 10.00],
            'logo_text' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'MG'],
            'alamat_toko' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('pengaturan_sistem');
    }

    public function down()
    {
        $this->forge->dropTable('pengaturan_sistem', true);
    }
}
