<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPengajuanIdToKredit extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('kredit', [
            'pengajuan_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
                'after'    => 'id',
            ],
        ]);

        $this->db->query('ALTER TABLE kredit ADD CONSTRAINT fk_kredit_pengajuan FOREIGN KEY (pengajuan_id) REFERENCES pengajuan(id) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE kredit DROP FOREIGN KEY fk_kredit_pengajuan');
        $this->forge->dropColumn('kredit', 'pengajuan_id');
    }
}
