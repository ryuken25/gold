<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePembayaranAngsuranTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'kode_pembayaran' => ['type' => 'VARCHAR', 'constraint' => 50],
            'kredit_id' => ['type' => 'INT', 'unsigned' => true],
            'jadwal_angsuran_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'tanggal_bayar' => ['type' => 'DATE'],
            'nominal_bayar' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'metode_pembayaran' => ['type' => 'ENUM', 'constraint' => ['transfer', 'cash', 'lainnya'], 'default' => 'transfer'],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'dicatat_oleh' => ['type' => 'INT', 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('kode_pembayaran');
        $this->forge->addForeignKey('kredit_id', 'kredit', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('jadwal_angsuran_id', 'jadwal_angsuran', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('dicatat_oleh', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pembayaran_angsuran');
    }

    public function down()
    {
        $this->forge->dropTable('pembayaran_angsuran', true);
    }
}
