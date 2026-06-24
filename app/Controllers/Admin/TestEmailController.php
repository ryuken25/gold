<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class TestEmailController extends BaseController
{
    /**
     * Test email configuration — kirim ke 2 alamat sekaligus.
     * Akses: /admin/test-email
     */
    public function sendTest()
    {
        $email = \Config\Services::email(null, false);

        // Clear dulu
        $email->clear();

        // Konfigurasi
        $email->setFrom('mahengoldofficial@gmail.com', 'Mahen Gold');
        $email->setTo(['kadeknadi98@gmail.com']);
        $email->setSubject('[MahenGold] TEST - Email Notifikasi ' . date('d/m/Y H:i:s'));
        $email->setMailType('html');
        $email->setMessage('
            <!DOCTYPE html>
            <html>
            <head><meta charset="utf-8"></head>
            <body style="font-family:Arial,sans-serif; padding:20px; background:#f4f4f4;">
                <div style="max-width:500px; margin:0 auto; background:#fff; border-radius:12px; padding:30px; border:1px solid #ddd;">
                    <h2 style="color:#C9A24B; text-align:center;">✅ Test Email Berhasil!</h2>
                    <p>Email notifikasi dari sistem <strong>MahenGold</strong> sudah aktif.</p>
                    <table style="width:100%; border-collapse:collapse; margin:16px 0;">
                        <tr><td style="padding:6px 0; color:#777;">Waktu</td><td style="padding:6px 0; font-weight:bold;">' . date('d M Y H:i:s') . '</td></tr>
                        <tr><td style="padding:6px 0; color:#777;">Server</td><td style="padding:6px 0; font-weight:bold;">' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '</td></tr>
                        <tr><td style="padding:6px 0; color:#777;">Status</td><td style="padding:6px 0; font-weight:bold; color:#19B85A;">Terkirim</td></tr>
                    </table>
                    <p style="color:#999; font-size:12px; text-align:center; margin-top:20px;">Email otomatis — mohon tidak membalas pesan ini.</p>
                </div>
            </body>
            </html>
        ');

        if ($email->send(false)) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Email terkirim ke kadeknadi98@gmail.com!',
                'debug'   => $email->printDebugger(['headers']),
            ]);
        } else {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Email gagal terkirim.',
                'debug'   => $email->printDebugger(['headers', 'subject', 'body']),
            ]);
        }
    }
}
