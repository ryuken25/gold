<?php

namespace App\Services;

use App\Models\EmailLogModel;
use App\Models\PengaturanSistemModel;
use App\Models\UserModel;
use Config\Services;
use Throwable;

/**
 * Notifikasi email otomatis MahenGold (SMTP).
 *
 * Tiga pemicu: pesanan dibuat, pesanan diverifikasi, dan reminder
 * 30 menit sebelum sesi kedatangan. Semua pengiriman dibungkus try/catch
 * dan dicatat ke email_logs; kegagalan TIDAK menggagalkan alur pemanggil.
 */
class EmailNotificationService
{
    public function __construct(
        protected ?EmailLogModel $logModel = null,
        protected ?PengaturanSistemModel $pengaturanModel = null,
        protected ?UserModel $userModel = null,
    ) {
        helper(['mahen', 'text']);
        $this->logModel        ??= new EmailLogModel();
        $this->pengaturanModel ??= new PengaturanSistemModel();
        $this->userModel       ??= new UserModel();
    }

    public function kirimPesananDibuat(array $p): bool
    {
        $isi = $this->daftarHtml($this->barisPesanan($p))
            . '<p style="margin:16px 0 0;">Pesanan Anda sedang <strong>menunggu verifikasi admin</strong>. '
            . 'Anda dapat memantau statusnya kapan saja di menu <strong>Pesanan</strong> pada akun MahenGold.</p>';

        return $this->kirim(
            'pesanan_dibuat',
            (int) ($p['user_id'] ?? 0),
            'Pesanan ' . ($p['kode_pesanan'] ?? '') . ' diterima — menunggu verifikasi',
            'Halo ' . esc($p['nama'] ?? 'Pelanggan') . ', pesanan Anda berhasil dibuat. Berikut ringkasannya:',
            $isi,
            (int) ($p['pengajuan_id'] ?? 0),
        );
    }

    public function kirimPesananDiverifikasi(array $p): bool
    {
        $pengaturan = $this->pengaturanModel->getPengaturan();

        $baris = $this->barisPesanan($p);
        if (!empty($pengaturan['alamat_toko'])) {
            $baris['Alamat Toko'] = $pengaturan['alamat_toko'];
        }

        $isKredit = ($p['metode_pembayaran'] ?? '') === 'kredit';
        $extra = $isKredit
            ? 'Jadwal angsuran Anda sudah aktif. Lihat rincian jatuh tempo dan unggah bukti pembayaran di halaman akun.'
            : 'Silakan selesaikan pembayaran dan unggah buktinya di halaman akun.';

        $isi = $this->daftarHtml($baris)
            . '<p style="margin:16px 0 0;">Pesanan Anda telah <strong style="color:#1f8a4c;">diverifikasi &amp; disetujui</strong>. '
            . esc($extra) . ' Tim kami akan menghubungi Anda untuk proses selanjutnya.</p>'
            . '<p style="margin:14px 0 0;"><a href="' . esc(base_url('/akun')) . '" '
            . 'style="background:#C9A24B;color:#1c1a17;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:700;">Buka Akun Saya</a></p>';

        return $this->kirim(
            'pesanan_diverifikasi',
            (int) ($p['user_id'] ?? 0),
            'Pesanan ' . ($p['kode_pesanan'] ?? '') . ' diverifikasi',
            'Halo ' . esc($p['nama'] ?? 'Pelanggan') . ', kabar baik! Pesanan Anda sudah diverifikasi admin.',
            $isi,
            (int) ($p['pengajuan_id'] ?? 0),
        );
    }

    public function kirimPembayaranTerverifikasi(array $p): bool
    {
        $isKredit = ($p['tipe'] ?? '') === 'cicilan';

        $baris = ['Kode Bukti' => $p['kode'] ?? '-'];
        if ($isKredit) {
            $baris['Kredit'] = $p['kode_kredit'] ?? '-';
            if (!empty($p['angsuran_ke'])) {
                $baris['Angsuran Ke'] = $p['angsuran_ke'];
            }
            $baris['Nominal']       = format_rupiah($p['nominal'] ?? 0);
            $baris['Total Terbayar'] = format_rupiah($p['total_terbayar'] ?? 0);
            $baris['Sisa Piutang']  = format_rupiah($p['sisa_piutang'] ?? 0);
            $baris['Status Kredit'] = ucfirst((string) ($p['status_kredit'] ?? 'aktif'));
        } else {
            $baris['Nomor Pesanan'] = $p['kode_pesanan'] ?? '-';
            $baris['Nominal']       = format_rupiah($p['nominal'] ?? 0);
        }

        $lunas   = $isKredit && ($p['status_kredit'] ?? '') === 'lunas';
        $penutup = $lunas
            ? 'Selamat! Seluruh angsuran kredit emas Anda telah <strong>LUNAS</strong>. Terima kasih telah mempercayai MahenGold.'
            : ($isKredit
                ? 'Pembayaran angsuran Anda telah kami verifikasi. Lanjutkan pembayaran berikutnya sesuai jadwal di akun Anda.'
                : 'Pembayaran Anda telah kami verifikasi. Pesanan sedang <strong>dipersiapkan untuk proses pengiriman</strong>. Terima kasih.');

        $isi = $this->daftarHtml($baris) . '<p style="margin:16px 0 0;">' . $penutup . '</p>';

        return $this->kirim(
            'pembayaran_terverifikasi',
            (int) ($p['user_id'] ?? 0),
            'Pembayaran ' . ($p['kode'] ?? '') . ' terverifikasi',
            'Halo ' . esc($p['nama'] ?? 'Pelanggan') . ', pembayaran Anda sudah kami verifikasi.',
            $isi,
            (int) ($p['related_id'] ?? 0),
        );
    }

    /**
     * UPDATED: Kirim email notifikasi perubahan status pesanan.
     */
    public function kirimStatusPesanan(array $p): bool
    {
        $status     = $p['status_baru'] ?? '';
        $label      = email_status_label($status);
        $isSelesai  = $status === 'selesai';
        $isDikirim  = $status === 'dikirim';
        $isDitolak  = in_array($status, ['ditolak', 'dibatalkan'], true);

        $baris = $this->barisPesanan($p);
        $baris['Status'] = $label;

        $penutup = match (true) {
            $isSelesai => 'Pesanan Anda telah <strong>selesai</strong>. Terima kasih telah mempercayai MahenGold sebagai mitra investasi emas Anda.',
            $isDikirim => 'Pesanan Anda sedang <strong>dalam perjalanan</strong> ke alamat tujuan. Anda akan menerima konfirmasi lagi setelah pesanan sampai.',
            $isDitolak => 'Pesanan Anda telah <strong>dibatalkan/ditolak</strong>. Jika ada pertanyaan, silakan hubungi admin.',
            default    => 'Status pesanan Anda telah diperbarui ke <strong>' . esc($label) . '</strong>. Pantau terus di menu Pesanan pada akun MahenGold Anda.',
        };

        $isi = $this->daftarHtml($baris)
            . '<p style="margin:16px 0 0;">' . $penutup . '</p>'
            . '<p style="margin:14px 0 0;"><a href="' . esc(base_url('/akun/pesanan')) . '" '
            . 'style="background:#C9A24B;color:#1c1a17;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:700;">Lihat Pesanan</a></p>';

        return $this->kirim(
            'status_pesanan',
            (int) ($p['user_id'] ?? 0),
            'Status Pesanan ' . ($p['kode_pesanan'] ?? '') . ' — ' . $label,
            'Halo ' . esc($p['nama'] ?? 'Pelanggan') . ', ada pembaruan status pesanan Anda.',
            $isi,
            (int) ($p['pengajuan_id'] ?? 0),
        );
    }

    public function kirimReminderManual(array $p): bool
    {
        $messageHtml = '<p style="font-size:15px;line-height:1.6;color:#2b2b2b;">' . esc($p['message']) . '</p>';
        return $this->kirim(
            'reminder_manual',
            (int) $p['user_id'],
            $p['subject'],
            'Halo ' . esc($p['nama']) . ',',
            $messageHtml,
            (int) $p['kredit_id']
        );
    }

    // ------------------------------------------------------------------

    /**
     * Susun baris ringkasan pesanan (label => nilai) untuk email.
     */
    protected function barisPesanan(array $p): array
    {
        $baris = [
            'Nomor Pesanan' => $p['kode_pesanan'] ?? '-',
            'Produk'        => trim(($p['nama_produk'] ?? '-') . (!empty($p['kode_produk']) ? ' (' . $p['kode_produk'] . ')' : '')),
            'Metode'        => ucfirst((string) ($p['metode_pembayaran'] ?? '-')),
        ];

        if (($p['metode_pembayaran'] ?? '') === 'kredit') {
            $baris['Tenor'] = ($p['tenor_bulan'] ?? '-') . ' bulan (' . ucfirst((string) ($p['periode_angsuran'] ?? '-')) . ')';
            if (!empty($p['total_harga_kredit'])) {
                $baris['Total Harga Kredit'] = format_rupiah($p['total_harga_kredit']);
            }
            if (!empty($p['nominal_angsuran'])) {
                $baris['Estimasi Angsuran'] = format_rupiah($p['nominal_angsuran']) . ' / ' . ($p['periode_label'] ?? 'bulan');
            }
        }

        return $baris;
    }

    protected function daftarHtml(array $rows): string
    {
        $html = '<table style="width:100%;border-collapse:collapse;font-size:14px;">';
        foreach ($rows as $label => $value) {
            $html .= '<tr>'
                . '<td style="padding:6px 0;color:#7a7263;width:42%;vertical-align:top;">' . esc($label) . '</td>'
                . '<td style="padding:6px 0;color:#1c1a17;font-weight:600;">' . esc($value) . '</td>'
                . '</tr>';
        }
        return $html . '</table>';
    }

    /**
     * Kerangka email HTML konsisten brand MahenGold.
     */
    protected function render(string $judul, string $pembuka, string $isiHtml): string
    {
        $p        = $this->pengaturanModel->getPengaturan();
        $namaToko = esc($p['nama_toko'] ?? 'MahenGold');
        $alamat   = esc($p['alamat_toko'] ?? '');
        $wa       = esc($p['nomor_whatsapp_toko'] ?? '');

        return '<!doctype html><html lang="id"><body style="margin:0;padding:0;background:#f4eee1;font-family:Arial,Helvetica,sans-serif;color:#2b2b2b;">'
            . '<div style="max-width:560px;margin:0 auto;padding:24px 16px;">'
            . '<div style="background:#1c1a17;border-radius:14px 14px 0 0;padding:22px 24px;text-align:center;">'
            . '<span style="color:#C9A24B;font-size:22px;font-weight:800;letter-spacing:1px;">' . $namaToko . '</span>'
            . '<div style="color:#9c9385;font-size:11px;letter-spacing:2px;margin-top:4px;">PENJUALAN &amp; KREDIT EMAS</div>'
            . '</div>'
            . '<div style="background:#ffffff;padding:24px;border:1px solid #ece3d2;border-top:0;">'
            . '<h2 style="margin:0 0 10px;color:#1c1a17;font-size:18px;">' . esc($judul) . '</h2>'
            . '<p style="margin:0 0 16px;color:#4a4538;font-size:14px;">' . $pembuka . '</p>'
            . $isiHtml
            . '</div>'
            . '<div style="background:#1c1a17;border-radius:0 0 14px 14px;padding:16px 24px;color:#9c9385;font-size:12px;text-align:center;">'
            . ($alamat !== '' ? $alamat . '<br>' : '')
            . ($wa !== '' ? 'WhatsApp: ' . $wa . '<br>' : '')
            . '<span style="color:#6f6757;">Email otomatis — mohon tidak membalas pesan ini.</span>'
            . '</div>'
            . '</div></body></html>';
    }

    /**
     * Kirim email + catat ke email_logs. Tidak pernah melempar exception.
     */
    protected function kirim(string $tipe, int $userId, string $subjek, string $pembuka, string $isiHtml, ?int $relatedId = null): bool
    {
        // Selalu ambil email & nama TERBARU dari tabel users.
        $user = $userId > 0 ? $this->userModel->find($userId) : null;

        if (!$user || empty($user['email'])) {
            $this->logModel->insert([
                'tipe'         => $tipe,
                'tujuan_email' => $user['email'] ?? '(kosong)',
                'nama_tujuan'  => $user['nama'] ?? null,
                'subjek'       => $subjek,
                'body'         => null,
                'status'       => 'gagal',
                'error'        => 'Email pelanggan tidak ditemukan.',
                'related_type' => 'pengajuan',
                'related_id'   => $relatedId,
            ]);
            return false;
        }

        $tujuanEmail = $user['email'];
        $namaTujuan  = $user['nama'];

        if (ENVIRONMENT === 'development' || ENVIRONMENT === 'testing') {
            $tujuanEmail = 'winayaarya@gmail.com';
            $subjek = '[MahenGold TEST] ' . $subjek;
        }

        $body   = $this->render($subjek, $pembuka, $isiHtml);
        $status = 'terkirim';
        $error  = null;

        $cfg = config('Email');

        // SMTP belum dikonfigurasi: jangan mencoba kirim (mencegah hang),
        // cukup catat sebagai gagal agar alur tetap berjalan.
        if ($cfg->protocol === 'smtp' && trim((string) $cfg->SMTPHost) === '') {
            $this->logModel->insert([
                'tipe'         => $tipe,
                'tujuan_email' => $tujuanEmail,
                'nama_tujuan'  => $namaTujuan,
                'subjek'       => $subjek,
                'body'         => $body,
                'status'       => 'gagal',
                'error'        => 'SMTP belum dikonfigurasi (email.SMTPHost kosong di .env).',
                'related_type' => 'pengajuan',
                'related_id'   => $relatedId,
            ]);
            return false;
        }

        try {
            $email = Services::email(null, false);
            $email->clear(); // UPDATED: WAJIB clear sebelum setiap kirim baru
            $email->setFrom($cfg->fromEmail ?: 'no-reply@mahengold.test', $cfg->fromName ?: 'Mahen Gold');
            $email->setTo($tujuanEmail);
            $email->setSubject($subjek);
            $email->setMailType('html');
            $email->setMessage($body);

            if (!$email->send(false)) {
                $status = 'gagal';
                $error  = trim(strip_tags((string) $email->printDebugger(['headers'])));
            }
        } catch (Throwable $e) {
            $status = 'gagal';
            $error  = $e->getMessage();
        }

        $this->logModel->insert([
            'tipe'         => $tipe,
            'tujuan_email' => $tujuanEmail,
            'nama_tujuan'  => $namaTujuan,
            'subjek'       => $subjek,
            'body'         => $body,
            'status'       => $status,
            'error'        => $error ? mb_substr($error, 0, 2000) : null,
            'related_type' => 'pengajuan',
            'related_id'   => $relatedId,
        ]);

        return $status === 'terkirim';
    }
}
