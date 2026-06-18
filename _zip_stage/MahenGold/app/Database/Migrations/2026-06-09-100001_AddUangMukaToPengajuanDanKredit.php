<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUangMukaToPengajuanDanKredit extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('pengajuan', [
            'uang_muka' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => false,
                'default'    => 0,
                'after'      => 'periode_angsuran',
            ],
        ]);

        $this->forge->addColumn('kredit', [
            'uang_muka' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => false,
                'default'    => 0,
                'after'      => 'total_harga_kredit',
            ],
            'sisa_pokok_kredit' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => true,
                'default'    => null,
                'after'      => 'uang_muka',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('pengajuan', 'uang_muka');
        $this->forge->dropColumn('kredit', ['uang_muka', 'sisa_pokok_kredit']);
    }
}
