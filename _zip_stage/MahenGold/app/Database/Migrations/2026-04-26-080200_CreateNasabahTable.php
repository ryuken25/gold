<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNasabahTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'kode_nasabah' => ['type' => 'VARCHAR', 'constraint' => 50],
            'nama' => ['type' => 'VARCHAR', 'constraint' => 150],
            'no_telepon' => ['type' => 'VARCHAR', 'constraint' => 30],
            'alamat' => ['type' => 'TEXT'],
            'catatan' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('kode_nasabah');
        $this->forge->createTable('nasabah');
    }

    public function down()
    {
        $this->forge->dropTable('nasabah', true);
    }
}
