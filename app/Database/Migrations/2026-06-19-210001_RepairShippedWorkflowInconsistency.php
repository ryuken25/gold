<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RepairShippedWorkflowInconsistency extends Migration
{
    public function up()
    {
        $db = $this->db;

        // Ensure the 'status' ENUM field has all valid values including 'dikirim'
        $db->query("ALTER TABLE `pengajuan` MODIFY COLUMN `status` ENUM('baru', 'diproses', 'disetujui', 'dikirim', 'ditolak', 'dibatalkan', 'selesai') NOT NULL DEFAULT 'baru'");

        // Find pengajuan that has a 'dikirim' activity log but status is NOT 'dikirim' or 'selesai'
        $rows = $db->query("
            SELECT p.id, pa.created_at, pa.aktor
            FROM pengajuan p
            JOIN pengajuan_aktivitas pa ON pa.pengajuan_id = p.id
            WHERE pa.id IN (
                SELECT MAX(id)
                FROM pengajuan_aktivitas
                WHERE aksi = 'dikirim'
                GROUP BY pengajuan_id
            )
            AND p.status NOT IN ('dikirim', 'selesai')
        ")->getResultArray();

        foreach ($rows as $r) {
            $pengajuanId = (int)$r['id'];
            $dikirimPada = $r['created_at'];
            $aktorName = $r['aktor'] ?? '';

            // Resolve admin ID from actor name if possible, otherwise default to 1
            $adminId = 1;
            if ($aktorName !== '') {
                $adminRow = $db->query("SELECT id FROM users WHERE nama = ? OR username = ? LIMIT 1", [$aktorName, $aktorName])->getRow();
                if ($adminRow) {
                    $adminId = (int)$adminRow->id;
                } else if (is_numeric($aktorName)) {
                    $adminId = (int)$aktorName;
                }
            }

            $db->query("
                UPDATE pengajuan
                SET status = 'dikirim',
                    dikirim_pada = COALESCE(dikirim_pada, ?),
                    dikirim_oleh = COALESCE(dikirim_oleh, ?)
                WHERE id = ?
            ", [$dikirimPada, $adminId, $pengajuanId]);
        }
    }

    public function down()
    {
        // Non-destructive, up-only migration
    }
}
