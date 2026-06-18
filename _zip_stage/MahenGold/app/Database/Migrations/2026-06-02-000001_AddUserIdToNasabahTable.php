<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserIdToNasabahTable extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('nasabah', [
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
                'after'    => 'kode_nasabah',
                'comment'  => 'Tautan opsional ke akun pelanggan (users.role = pelanggan)',
            ],
        ]);

        // FK ke users sekaligus membuat index pada kolom user_id.
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE', 'nasabah_user_id_foreign');
        $this->forge->processIndexes('nasabah');
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('nasabah', 'nasabah_user_id_foreign');
        $this->forge->dropColumn('nasabah', 'user_id');
    }
}
