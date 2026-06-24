<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestEmail extends BaseCommand
{
    protected $group       = 'email';
    protected $name        = 'email:test';
    protected $description = 'Test kirim email ke kadeknadi98';

    public function run(array $params)
    {
        $email = \Config\Services::email(null, false);

        $email->clear();
        $email->setFrom('mahengoldofficial@gmail.com', 'Mahen Gold');
        $email->setTo(['kadeknadi98@gmail.com']);
        $email->setSubject('[MahenGold] TEST EMAIL - ' . date('d/m/Y H:i:s'));
        $email->setMailType('html');
        $email->setMessage('
            <div style="font-family:Arial; padding:20px; background:#f4f4f4;">
                <div style="max-width:500px; margin:0 auto; background:#fff; border-radius:12px; padding:30px; border:1px solid #ddd;">
                    <h2 style="color:#C9A24B; text-align:center;">✅ Test Email Berhasil!</h2>
                    <p>Sistem notifikasi <strong>MahenGold</strong> sudah aktif.</p>
                    <p>Waktu: <strong>' . date('d M Y H:i:s') . '</strong></p>
                    <p style="color:#999; font-size:12px;">Email otomatis — mohon tidak membalas.</p>
                </div>
            </div>
        ');

        CLI::write('Mengirim email test...', 'yellow');
        CLI::write('Ke: kadeknadi98@gmail.com', 'cyan');

        if ($email->send(false)) {
            CLI::write('✅ EMAIL BERHASIL TERKIRIM!', 'green');
            CLI::write('Debug: ' . $email->printDebugger(['headers']), 'gray');
        } else {
            CLI::write('❌ EMAIL GAGAL!', 'red');
            CLI::write('Debug: ' . $email->printDebugger(), 'red');
        }
    }
}
