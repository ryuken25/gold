<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDpVerificationFieldsToKredit extends Migration
{
    public function up(): void
    {
        $db = $this->db;

        if (!$this->fieldExists('kredit', 'dp_verified_at')) {
            $db->query("ALTER TABLE `kredit` ADD COLUMN `dp_verified_at` DATETIME NULL DEFAULT NULL AFTER `uang_muka`");
        }

        if (!$this->fieldExists('kredit', 'dp_status')) {
            $db->query("ALTER TABLE `kredit` ADD COLUMN `dp_status` VARCHAR(50) NOT NULL DEFAULT 'menunggu' AFTER `dp_verified_at`");
        }
    }

    public function down(): void
    {
        $db = $this->db;

        if ($this->fieldExists('kredit', 'dp_verified_at')) {
            $db->query("ALTER TABLE `kredit` DROP COLUMN `dp_verified_at`");
        }

        if ($this->fieldExists('kredit', 'dp_status')) {
            $db->query("ALTER TABLE `kredit` DROP COLUMN `dp_status`");
        }
    }

    private function fieldExists(string $table, string $field): bool
    {
        return $this->db->fieldExists($field, $table);
    }
}
