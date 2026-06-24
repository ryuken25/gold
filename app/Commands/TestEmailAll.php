<?php

namespace App\Commands;

use App\Services\EmailNotificationService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestEmailAll extends BaseCommand
{
    protected $group       = 'email';
    protected $name        = 'email:test-all';
    protected $description = 'Test SEMUA flow email notifikasi ke kadeknadi98';

    private array $testEmails = ['kadeknadi98@gmail.com'];

    public function run(array $params)
    {
        $service = new EmailNotificationService();
        $results = [];

        // ========== 1. Pesanan Dibuat (Menunggu) ==========
        CLI::write('1/6 Testing: Pesanan Dibuat (Menunggu)...', 'yellow');
        $payload = $this->basePayload('PST-0001');
        $payload['user_id'] = 1; // dummy
        $ok = $this->sendDirect('Pesanan Diterima — Menunggu Verifikasi', $this->renderPesananDibuat($payload));
        $results[] = ['Pesanan Dibuat (Menunggu)', $ok];

        // ========== 2. Pesanan Diverifikasi ==========
        CLI::write('2/6 Testing: Pesanan Diverifikasi...', 'yellow');
        $payload = $this->basePayload('PST-0002');
        $payload['user_id'] = 1;
        $ok = $this->sendDirect('Pesanan Diverifikasi & Disetujui', $this->renderPesananDiverifikasi($payload));
        $results[] = ['Pesanan Diverifikasi', $ok];

        // ========== 3. Status Dikirim ==========
        CLI::write('3/6 Testing: Status Dikirim...', 'yellow');
        $payload = $this->basePayload('PST-0003');
        $payload['user_id'] = 1;
        $ok = $this->sendDirect('Status Pesanan — Sedang Dikirim', $this->renderStatusDikirim($payload));
        $results[] = ['Status Dikirim', $ok];

        // ========== 4. Status Selesai ==========
        CLI::write('4/6 Testing: Status Selesai...', 'yellow');
        $payload = $this->basePayload('PST-0004');
        $payload['user_id'] = 1;
        $ok = $this->sendDirect('Status Pesanan — Selesai', $this->renderStatusSelesai($payload));
        $results[] = ['Status Selesai', $ok];

        // ========== 5. Pembayaran DP Terverifikasi ==========
        CLI::write('5/6 Testing: Pembayaran DP Terverifikasi...', 'yellow');
        $ok = $this->sendDirect('Pembayaran DP Terverifikasi', $this->renderPembayaranDP());
        $results[] = ['Pembayaran DP', $ok];

        // ========== 6. Pembayaran Angsuran Terverifikasi ==========
        CLI::write('6/6 Testing: Pembayaran Angsuran Terverifikasi...', 'yellow');
        $ok = $this->sendDirect('Pembayaran Angsuran Terverifikasi', $this->renderPembayaranAngsuran());
        $results[] = ['Pembayaran Angsuran', $ok];

        // ========== Ringkasan ==========
        CLI::newLine();
        CLI::write('═══════════════════════════════════════════', 'cyan');
        CLI::write('RINGKASAN TEST EMAIL', 'cyan');
        CLI::write('═══════════════════════════════════════════', 'cyan');
        $allOk = true;
        foreach ($results as [$label, $ok]) {
            $icon = $ok ? '✅' : '❌';
            $color = $ok ? 'green' : 'red';
            CLI::write("  $icon $label", $color);
            if (!$ok) $allOk = false;
        }
        CLI::write('═══════════════════════════════════════════', 'cyan');
        if ($allOk) {
            CLI::write('🎉 SEMUA EMAIL BERHASIL TERKIRIM!', 'green');
        } else {
            CLI::write('⚠️  Ada yang gagal — cek log di atas', 'red');
        }
        CLI::write('Ke: kadeknadi98@gmail.com', 'cyan');
    }

    private function basePayload(string $kode): array
    {
        return [
            'kode_pesanan'      => $kode,
            'nama'              => 'Nadiari Test',
            'nama_produk'       => 'Cincin Emas 1gr',
            'kode_produk'       => 'CIN-001',
            'metode_pembayaran' => 'kredit',
            'tenor_bulan'       => 12,
            'periode_angsuran'  => 'bulanan',
            'total_harga_kredit'=> 3600000,
            'uang_muka'         => 200000,
            'nominal_angsuran'  => 283333,
            'periode_label'     => 'bulan',
        ];
    }

    // ============================================================
    // Direct send — bypass EmailNotificationService, langsung SMTP
    // ============================================================
    private function sendDirect(string $subject, string $html): bool
    {
        $email = \Config\Services::email(null, false);
        $email->clear();
        $email->setFrom('mahengoldofficial@gmail.com', 'Mahen Gold');
        $email->setTo($this->testEmails);
        $email->setSubject('[MahenGold] ' . $subject);
        $email->setMailType('html');
        $email->setMessage($html);

        $ok = $email->send(false);
        if ($ok) {
            CLI::write("  → Terkirim!", 'green');
        } else {
            CLI::write("  → GAGAL: " . $email->printDebugger(['headers']), 'red');
        }
        return $ok;
    }

    // ============================================================
    // Render email templates
    // ============================================================
    private function baseLayout(string $judul, string $pembuka, string $isi): string
    {
        return '<!doctype html><html lang="id"><body style="margin:0;padding:0;background:#f4eee1;font-family:Arial,Helvetica,sans-serif;color:#2b2b2b;">'
            . '<div style="max-width:560px;margin:0 auto;padding:24px 16px;">'
            . '<div style="background:#1c1a17;border-radius:14px 14px 0 0;padding:22px 24px;text-align:center;">'
            . '<span style="color:#C9A24B;font-size:22px;font-weight:800;letter-spacing:1px;">MahenGold</span>'
            . '<div style="color:#9c9385;font-size:11px;letter-spacing:2px;margin-top:4px;">PENJUALAN &amp; KREDIT EMAS</div>'
            . '</div>'
            . '<div style="background:#ffffff;padding:24px;border:1px solid #ece3d2;border-top:0;">'
            . '<h2 style="margin:0 0 10px;color:#1c1a17;font-size:18px;">' . $judul . '</h2>'
            . '<p style="margin:0 0 16px;color:#4a4538;font-size:14px;">' . $pembuka . '</p>'
            . $isi
            . '</div>'
            . '<div style="background:#1c1a17;border-radius:0 0 14px 14px;padding:16px 24px;color:#9c9385;font-size:12px;text-align:center;">'
            . '<span style="color:#6f6757;">Email otomatis — mohon tidak membalas pesan ini.</span>'
            . '</div>'
            . '</div></body></html>';
    }

    private function summaryTable(array $rows): string
    {
        $html = '<table style="width:100%;border-collapse:collapse;font-size:14px;">';
        foreach ($rows as $label => $value) {
            $html .= '<tr>'
                . '<td style="padding:6px 0;color:#7a7263;width:42%;vertical-align:top;">' . $label . '</td>'
                . '<td style="padding:6px 0;color:#1c1a17;font-weight:600;">' . $value . '</td>'
                . '</tr>';
        }
        return $html . '</table>';
    }

    private function renderPesananDibuat(array $p): string
    {
        $baris = $this->summaryTable([
            'Nomor Pesanan' => $p['kode_pesanan'],
            'Produk'        => $p['nama_produk'] . ' (' . $p['kode_produk'] . ')',
            'Metode'        => 'Kredit',
            'Tenor'         => $p['tenor_bulan'] . ' bulan (' . $p['periode_angsuran'] . ')',
            'Estimasi Angsuran' => 'Rp ' . number_format($p['nominal_angsuran']) . ' / ' . $p['periode_label'],
        ]);
        $isi = $baris
            . '<p style="margin:16px 0 0;">Pesanan Anda sedang <strong>menunggu verifikasi admin</strong>. '
            . 'Anda dapat memantau statusnya di menu <strong>Pesanan</strong> pada akun MahenGold.</p>'
            . '<p style="color:#999;font-size:12px;margin-top:20px;">⚠️ Jika ini bukan pesanan Anda, abaikan email ini.</p>';
        return $this->baseLayout(
            'Pesanan ' . $p['kode_pesanan'] . ' Diterima',
            'Halo ' . $p['nama'] . ', pesanan Anda berhasil dibuat.',
            $isi
        );
    }

    private function renderPesananDiverifikasi(array $p): string
    {
        $baris = $this->summaryTable([
            'Nomor Pesanan' => $p['kode_pesanan'],
            'Produk'        => $p['nama_produk'] . ' (' . $p['kode_produk'] . ')',
            'Metode'        => 'Kredit',
            'Status'        => '<span style="color:#1f8a4c;font-weight:bold;">✅ Diverifikasi & Disetujui</span>',
        ]);
        $isi = $baris
            . '<p style="margin:16px 0 0;">Pesanan Anda telah <strong style="color:#1f8a4c;">diverifikasi &amp; disetujui</strong>. '
            . 'Jadwal angsuran Anda sudah aktif. Lihat rincian di halaman akun.</p>'
            . '<p style="margin:14px 0 0;"><a href="http://localhost:8080/akun" style="background:#C9A24B;color:#1c1a17;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:700;">Buka Akun Saya</a></p>';
        return $this->baseLayout(
            'Pesanan ' . $p['kode_pesanan'] . ' Diverifikasi',
            'Halo ' . $p['nama'] . ', kabar baik! Pesanan Anda sudah diverifikasi admin.',
            $isi
        );
    }

    private function renderStatusDikirim(array $p): string
    {
        $baris = $this->summaryTable([
            'Nomor Pesanan' => $p['kode_pesanan'],
            'Produk'        => $p['nama_produk'] . ' (' . $p['kode_produk'] . ')',
            'Status'        => '<span style="color:#0d6efd;font-weight:bold;">🚚 Sedang Dikirim</span>',
        ]);
        $isi = $baris
            . '<p style="margin:16px 0 0;">Pesanan Anda sedang <strong>dalam perjalanan</strong> ke alamat tujuan. '
            . 'Anda akan menerima konfirmasi lagi setelah pesanan sampai.</p>'
            . '<p style="margin:14px 0 0;"><a href="http://localhost:8080/akun/pesanan" style="background:#C9A24B;color:#1c1a17;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:700;">Lihat Pesanan</a></p>';
        return $this->baseLayout(
            'Pesanan ' . $p['kode_pesanan'] . ' Sedang Dikirim',
            'Halo ' . $p['nama'] . ', pesanan Anda sedang dalam perjalanan.',
            $isi
        );
    }

    private function renderStatusSelesai(array $p): string
    {
        $baris = $this->summaryTable([
            'Nomor Pesanan' => $p['kode_pesanan'],
            'Produk'        => $p['nama_produk'] . ' (' . $p['kode_produk'] . ')',
            'Status'        => '<span style="color:#198754;font-weight:bold;">✅ Selesai</span>',
        ]);
        $isi = $baris
            . '<p style="margin:16px 0 0;">Pesanan Anda telah <strong>selesai</strong>. '
            . 'Terima kasih telah mempercayai MahenGold sebagai mitra investasi emas Anda. 🏅</p>';
        return $this->baseLayout(
            'Pesanan ' . $p['kode_pesanan'] . ' Selesai',
            'Halo ' . $p['nama'] . ', pesanan Anda telah selesai.',
            $isi
        );
    }

    private function renderPembayaranDP(): string
    {
        $baris = $this->summaryTable([
            'Kode Bukti'    => 'BKT-0001',
            'Nomor Pesanan' => 'PST-0001',
            'Nominal'       => 'Rp 200.000',
            'Status'        => '<span style="color:#198754;font-weight:bold;">✅ Terverifikasi</span>',
        ]);
        $isi = $baris
            . '<p style="margin:16px 0 0;">Pembayaran Uang Muka (DP) Anda telah <strong>kami verifikasi</strong>. '
            . 'Pesanan akan segera diproses untuk pengiriman.</p>';
        return $this->baseLayout(
            'Pembayaran DP Terverifikasi',
            'Halo Nadiari Test, pembayaran DP Anda sudah kami verifikasi.',
            $isi
        );
    }

    private function renderPembayaranAngsuran(): string
    {
        $baris = $this->summaryTable([
            'Kode Bukti'    => 'BKT-0002',
            'Kredit'        => 'KRD-0001',
            'Angsuran Ke'   => '3',
            'Nominal'       => 'Rp 283.333',
            'Total Terbayar'=> 'Rp 850.000',
            'Sisa Piutang'  => 'Rp 2.550.000',
            'Status Kredit' => 'Aktif',
        ]);
        $isi = $baris
            . '<p style="margin:16px 0 0;">Pembayaran angsuran Anda telah <strong>kami verifikasi</strong>. '
            . 'Lanjutkan pembayaran berikutnya sesuai jadwal di akun Anda.</p>';
        return $this->baseLayout(
            'Pembayaran Angsuran Terverifikasi',
            'Halo Nadiari Test, pembayaran angsuran Anda sudah kami verifikasi.',
            $isi
        );
    }
}
