<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProdukEmasTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'kode_produk' => ['type' => 'VARCHAR', 'constraint' => 50],
            'nama_produk' => ['type' => 'VARCHAR', 'constraint' => 150],
            'jenis_emas' => ['type' => 'VARCHAR', 'constraint' => 100],
            'kadar' => ['type' => 'VARCHAR', 'constraint' => 50],
            'berat_gram' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'harga_pokok' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'stok' => ['type' => 'INT', 'default' => 0],
            'deskripsi' => ['type' => 'TEXT', 'null' => true],
            'gambar_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['aktif', 'nonaktif'], 'default' => 'aktif'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('kode_produk');
        $this->forge->createTable('produk_emas');
    }

    public function down()
    {
        $this->forge->dropTable('produk_emas', true);
    }
}
