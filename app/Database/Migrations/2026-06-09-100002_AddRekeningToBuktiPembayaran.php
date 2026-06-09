<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRekeningToBuktiPembayaran extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('bukti_pembayaran', [
            'nama_pengirim' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'nominal',
            ],
            'no_rekening' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'nama_pengirim',
            ],
            'bank_pengirim' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'no_rekening',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('bukti_pembayaran', ['nama_pengirim', 'no_rekening', 'bank_pengirim']);
    }
}
