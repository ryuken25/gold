<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterWhatsappLogsTipeAddKonfirmasiPesanan extends Migration
{
    public function up(): void
    {
        $this->forge->modifyColumn('whatsapp_logs', [
            'tipe' => [
                'type'       => 'ENUM',
                'constraint' => ['pengajuan_kredit', 'pengingat_jatuh_tempo', 'pembayaran_diterima', 'kredit_lunas', 'info_transaksi', 'konfirmasi_pesanan'],
                'default'    => 'pengajuan_kredit',
            ],
        ]);
    }

    public function down(): void
    {
        // Hindari baris dengan nilai baru menggagalkan revert
        $this->db->query("UPDATE whatsapp_logs SET tipe = 'info_transaksi' WHERE tipe = 'konfirmasi_pesanan'");

        $this->forge->modifyColumn('whatsapp_logs', [
            'tipe' => [
                'type'       => 'ENUM',
                'constraint' => ['pengajuan_kredit', 'pengingat_jatuh_tempo', 'pembayaran_diterima', 'kredit_lunas', 'info_transaksi'],
                'default'    => 'pengajuan_kredit',
            ],
        ]);
    }
}
