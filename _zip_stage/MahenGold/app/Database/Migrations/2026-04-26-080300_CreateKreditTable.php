<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKreditTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'kode_kredit' => ['type' => 'VARCHAR', 'constraint' => 50],
            'nasabah_id' => ['type' => 'INT', 'unsigned' => true],
            'produk_emas_id' => ['type' => 'INT', 'unsigned' => true],
            'tanggal_kredit' => ['type' => 'DATE'],
            'harga_pokok_snapshot' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'margin_persen' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 10.00],
            'margin_nominal' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'total_harga_kredit' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'tenor_bulan' => ['type' => 'INT'],
            'periode_angsuran' => ['type' => 'ENUM', 'constraint' => ['bulanan', 'mingguan'], 'default' => 'bulanan'],
            'jumlah_periode' => ['type' => 'INT'],
            'nominal_angsuran' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'total_terbayar' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'sisa_piutang' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'status' => ['type' => 'ENUM', 'constraint' => ['aktif', 'lunas', 'dibatalkan'], 'default' => 'aktif'],
            'catatan' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('kode_kredit');
        $this->forge->addForeignKey('nasabah_id', 'nasabah', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('produk_emas_id', 'produk_emas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('kredit');
    }

    public function down()
    {
        $this->forge->dropTable('kredit', true);
    }
}
