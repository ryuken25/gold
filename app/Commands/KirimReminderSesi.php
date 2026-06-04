<?php

namespace App\Commands;

use App\Models\PengajuanModel;
use App\Services\EmailNotificationService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Kirim email pengingat 30 menit sebelum sesi kedatangan pelanggan.
 *
 * Idempoten lewat flag `reminder_sesi_terkirim`, aman dijalankan tiap menit.
 * Cron (tiap 5 menit):
 *   star/5 * * * * cd /path/proyek && /usr/bin/php spark notif:reminder-sesi >> writable/logs/reminder.log 2>&1
 */
class KirimReminderSesi extends BaseCommand
{
    protected $group = 'Notifikasi';

    protected $name = 'notif:reminder-sesi';

    protected $description = 'Kirim email pengingat 30 menit sebelum sesi kedatangan pelanggan yang sudah disetujui.';

    protected $usage = 'notif:reminder-sesi';

    public function run(array $params)
    {
        $db = Database::connect();

        $rows = $db->table('pengajuan pg')
            ->select('pg.id, pg.kode_pesanan, pg.user_id, pg.nama, pg.waktu_sesi, p.nama_produk, p.kode_produk')
            ->join('produk_emas p', 'p.id = pg.produk_emas_id', 'left')
            ->where('pg.status', 'disetujui')
            ->where('pg.waktu_sesi IS NOT NULL', null, false)
            ->where('pg.reminder_sesi_terkirim', 0)
            ->where('TIMESTAMPDIFF(MINUTE, NOW(), pg.waktu_sesi) >=', 0)
            ->where('TIMESTAMPDIFF(MINUTE, NOW(), pg.waktu_sesi) <=', 30)
            ->get()->getResultArray();

        if (!$rows) {
            CLI::write('Tidak ada sesi yang perlu diingatkan.', 'yellow');
            return;
        }

        $service        = new EmailNotificationService();
        $pengajuanModel = new PengajuanModel();
        $ok    = 0;
        $gagal = 0;

        foreach ($rows as $r) {
            $sent = $service->kirimReminderSesi([
                'user_id'      => (int) $r['user_id'],
                'pengajuan_id' => (int) $r['id'],
                'nama'         => $r['nama'],
                'kode_pesanan' => $r['kode_pesanan'],
                'nama_produk'  => $r['nama_produk'],
                'waktu_sesi'   => $r['waktu_sesi'],
            ]);

            if ($sent) {
                // Tandai agar tidak terkirim dobel pada cron berikutnya.
                $pengajuanModel->update($r['id'], ['reminder_sesi_terkirim' => 1]);
                $ok++;
                CLI::write('  [OK] ' . $r['kode_pesanan'] . ' -> ' . $r['nama'], 'green');
            } else {
                $gagal++;
                CLI::write('  [GAGAL] ' . $r['kode_pesanan'] . ' (cek email_logs / konfigurasi SMTP)', 'red');
            }
        }

        CLI::write("Selesai. Terkirim: {$ok}, Gagal: {$gagal}.", 'white');
    }
}
