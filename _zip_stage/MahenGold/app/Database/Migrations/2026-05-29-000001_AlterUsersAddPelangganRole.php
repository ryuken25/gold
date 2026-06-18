<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterUsersAddPelangganRole extends Migration
{
    public function up(): void
    {
        // Tambah kolom no_telepon untuk pelanggan
        $this->forge->addColumn('users', [
            'no_telepon' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'default'    => null,
                'after'      => 'email',
            ],
        ]);

        // Ubah ENUM role agar mencakup 'pelanggan'
        $this->forge->modifyColumn('users', [
            'role' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'pelanggan'],
                'default'    => 'pelanggan',
                'null'       => false,
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', 'no_telepon');

        $this->forge->modifyColumn('users', [
            'role' => [
                'type'       => 'ENUM',
                'constraint' => ['admin'],
                'default'    => 'admin',
                'null'       => false,
            ],
        ]);
    }
}
