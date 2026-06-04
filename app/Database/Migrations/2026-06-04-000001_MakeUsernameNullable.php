<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeUsernameNullable extends Migration
{
    public function up(): void
    {
        // username dipakai hanya untuk admin — pelanggan tidak punya username,
        // jadi kolom ini harus nullable agar register pelanggan bisa insert null.
        $this->forge->modifyColumn('users', [
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
            ],
        ]);
    }

    public function down(): void
    {
        // Isi null → placeholder sebelum ubah kembali ke NOT NULL
        $this->db->query("UPDATE users SET username = CONCAT('user_', id) WHERE username IS NULL");

        $this->forge->modifyColumn('users', [
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
        ]);
    }
}
