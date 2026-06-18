<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWhatsappLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tipe' => ['type' => 'ENUM', 'constraint' => ['pengajuan_kredit', 'pengingat_jatuh_tempo', 'pembayaran_diterima', 'kredit_lunas', 'info_transaksi'], 'default' => 'pengajuan_kredit'],
            'target' => ['type' => 'ENUM', 'constraint' => ['pelanggan', 'admin'], 'default' => 'pelanggan'],
            'tujuan_nomor' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'nama_tujuan' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'pesan' => ['type' => 'TEXT'],
            'wa_url' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['dibuat', 'dibuka', 'dikirim_manual', 'gagal'], 'default' => 'dibuat'],
            'related_type' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'related_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('whatsapp_logs');
    }

    public function down()
    {
        $this->forge->dropTable('whatsapp_logs', true);
    }
}
