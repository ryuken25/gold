<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateJadwalAngsuranTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'kredit_id' => ['type' => 'INT', 'unsigned' => true],
            'angsuran_ke' => ['type' => 'INT'],
            'tanggal_jatuh_tempo' => ['type' => 'DATE'],
            'nominal_tagihan' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'nominal_dibayar' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'status' => ['type' => 'ENUM', 'constraint' => ['belum_dibayar', 'sebagian', 'dibayar', 'terlambat'], 'default' => 'belum_dibayar'],
            'tanggal_dibayar' => ['type' => 'DATE', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('kredit_id', 'kredit', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('jadwal_angsuran');
    }

    public function down()
    {
        $this->forge->dropTable('jadwal_angsuran', true);
    }
}
