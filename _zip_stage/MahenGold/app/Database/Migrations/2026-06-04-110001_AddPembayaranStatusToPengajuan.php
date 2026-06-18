<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPembayaranStatusToPengajuan extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('pengajuan', [
            'pembayaran_status' => [
                'type'       => 'ENUM',
                'constraint' => ['belum', 'menunggu', 'terverifikasi'],
                'default'    => 'belum',
                'null'       => false,
                'after'      => 'status',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('pengajuan', 'pembayaran_status');
    }
}
