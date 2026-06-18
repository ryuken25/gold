<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropWaktuSesiFromPengajuan extends Migration
{
    public function up(): void
    {
        $this->forge->dropColumn('pengajuan', ['waktu_sesi', 'reminder_sesi_terkirim']);
    }

    public function down(): void
    {
        $this->forge->addColumn('pengajuan', [
            'waktu_sesi' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'reminder_sesi_terkirim' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
            ],
        ]);
    }
}
