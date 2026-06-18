<?php

namespace App\Commands;

use App\Services\ReminderAngsuranService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ReminderAngsuran extends BaseCommand
{
    protected $group       = 'reminder';
    protected $name        = 'reminder:angsuran';
    protected $description = 'Kirim reminder otomatis angsuran (H-3, hari H, terlambat)';

    public function run(array $params)
    {
        CLI::write('Memproses reminder angsuran otomatis...', 'yellow');
        CLI::newLine();

        try {
            $service = new ReminderAngsuranService();
            $stats = $service->processAutomatic();

            CLI::write('═══════════════════════════════════════════', 'cyan');
            CLI::write('HASIL REMINDER OTOMATIS', 'cyan');
            CLI::write('═══════════════════════════════════════════', 'cyan');
            CLI::write("  Diperiksa : {$stats['diperiksa']}", 'white');
            CLI::write("  Terkirim  : {$stats['terkirim']}", 'green');
            CLI::write("  Dilewati  : {$stats['dilewati']}", 'yellow');
            CLI::write("  Gagal     : {$stats['gagal']}", 'red');
            CLI::write('═══════════════════════════════════════════', 'cyan');

            if ($stats['gagal'] > 0) {
                CLI::newLine();
                CLI::write('⚠️  Beberapa email gagal dikirim. Cek email_logs untuk detail.', 'yellow');
            }
        } catch (\Throwable $e) {
            CLI::write('❌ Error: ' . $e->getMessage(), 'red');
            CLI::write($e->getTraceAsString(), 'gray');
        }
    }
}
