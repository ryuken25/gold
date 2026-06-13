<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDpTipeToBuktiPembayaran extends Migration
{
    public function up(): void
    {
        // Tambah nilai 'dp' (uang muka) ke ENUM tipe bukti pembayaran.
        $this->db->query("ALTER TABLE `bukti_pembayaran` MODIFY `tipe` ENUM('cash','cicilan','dp') NOT NULL");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE `bukti_pembayaran` MODIFY `tipe` ENUM('cash','cicilan') NOT NULL");
    }
}
