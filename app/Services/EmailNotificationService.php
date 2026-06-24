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

        if ($isKredit) {
            $angsuran_ke = $p['angsuran_ke'] ?? '-';
            $subjek = "Pembayaran Angsuran ke-{$angsuran_ke} Terverifikasi";
            $pembuka = 'Halo ' . esc($p['nama'] ?? 'Pelanggan') . ", pembayaran angsuran ke-{$angsuran_ke} Anda telah diverifikasi.";

            $baris = [
                'Kode Kredit' => $p['kode_kredit'] ?? '-',
                'Angsuran Ke' => $angsuran_ke,
                'Periode' => ucfirst((string) ($p['periode_angsuran'] ?? 'bulanan')),
            ];

            $periode = strtolower((string) ($p['periode_angsuran'] ?? 'bulanan'));
            $jt = $p['tanggal_jatuh_tempo'] ?? '';
            if ($periode === 'mingguan') {
                if (!empty($jt)) {
                    $start = date('Y-m-d', strtotime($jt . ' - 6 days'));
                    $baris['Minggu Tagihan'] = format_tanggal_id($start) . ' - ' . format_tanggal_id($jt);
                } else {
                    $baris['Minggu Tagihan'] = '-';
                }
            } else {
                $baris['Bulan Tagihan'] = !empty($jt) ? format_tanggal_id($jt, 'F Y') : '-';
            }

            $baris['Jatuh Tempo'] = !empty($jt) ? format_tanggal_id($jt) : '-';
            $baris['Dibayar Pada'] = !empty($p['tanggal_bayar']) ? format_tanggal_id($p['tanggal_bayar']) : '-';
            $baris['Diverifikasi Pada'] = !empty($p['diverifikasi_pada']) ? format_tanggal_id($p['diverifikasi_pada']) : '-';
            $baris['Nominal Pembayaran Ini'] = format_rupiah($p['nominal'] ?? 0);
            $baris['Total Sudah Dibayar'] = format_rupiah($p['total_terbayar'] ?? 0);
            $baris['Sisa Piutang'] = format_rupiah($p['sisa_piutang'] ?? 0);
            $baris['Status Kredit'] = ($p['status_kredit'] ?? '') === 'lunas' ? 'Lunas' : 'Aktif';

            $allocHtml = '';
            if (!empty($p['allocations']) && count($p['allocations']) > 1) {
                $allocHtml .= '<p style="margin:16px 0 8px;font-weight:bold;color:#1c1a17;font-size:14px;">Rincian Alokasi Pembayaran:</p>';
                $allocHtml .= '<ul style="margin:0;padding-left:20px;font-size:14px;color:#2b2b2b;line-height:1.6;">';
                foreach ($p['allocations'] as $alloc) {
                    $ke = $alloc['angsuran_ke'] ?? '?';
                    $nom = format_rupiah($alloc['nominal_alokasi'] ?? 0);
                    $jt_alloc = !empty($alloc['tanggal_jatuh_tempo']) ? format_tanggal_id($alloc['tanggal_jatuh_tempo']) : '-';
                    $allocHtml .= "<li>Angsuran ke-{$ke} — {$nom} — Jatuh tempo {$jt_alloc}</li>";
                }
                $allocHtml .= '</ul>';
            }

            $lunas = ($p['status_kredit'] ?? '') === 'lunas';
            $penutup = $lunas
                ? 'Selamat! Seluruh angsuran kredit emas Anda telah lunas. Pesanan Anda akan otomatis diselesaikan jika barang sudah diterima.'
                : 'Pembayaran Anda sudah tercatat. Silakan lanjutkan pembayaran angsuran berikutnya sesuai jadwal di akun MahenGold.';

            $isi = $this->daftarHtml($baris) . $allocHtml . '<p style="margin:16px 0 0;">' . $penutup . '</p>';

        } else {
            $isDP = ($p['tipe'] ?? '') === 'dp';
            $subjek = 'Pembayaran ' . ($p['kode'] ?? '') . ' terverifikasi';
            $pembuka = 'Halo ' . esc($p['nama'] ?? 'Pelanggan') . ', pembayaran Anda sudah kami verifikasi.';

            $baris = ['Kode Bukti' => $p['kode'] ?? '-'];
            $baris['Nomor Pesanan'] = $p['kode_pesanan'] ?? '-';
            $baris['Nominal'] = format_rupiah($p['nominal'] ?? 0);

            $penutup = $isDP 
                ? 'Pembayaran uang muka (DP) Anda telah kami verifikasi. Status pesanan Anda akan segera diperbarui.'
                : 'Pembayaran Anda telah kami verifikasi. Pesanan sedang <strong>dipersiapkan untuk proses pengiriman</strong>. Terima kasih.';

            $isi = $this->daftarHtml($baris) . '<p style="margin:16px 0 0;">' . $penutup . '</p>';
        }

        return $this->kirim(
            'pembayaran_terverifikasi',
            (int) ($p['user_id'] ?? 0),
            $subjek,
            $pembuka,
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
        $isDiterima = $status === 'diterima';
        $isDitolak  = in_array($status, ['ditolak', 'dibatalkan'], true);

        $baris = $this->barisPesanan($p);
        $baris['Status'] = $label;

        $penutup = match (true) {
            $isSelesai  => 'Pesanan Anda telah <strong>selesai</strong>. Terima kasih telah mempercayai MahenGold sebagai mitra investasi emas Anda.',
            $isDikirim  => 'Pesanan Anda sedang <strong>dalam perjalanan</strong> ke alamat tujuan. Anda akan menerima konfirmasi lagi setelah pesanan sampai.',
            $isDiterima => 'Pesanan Anda telah <strong>diterima</strong> oleh pelanggan. Sistem akan otomatis menandai pesanan selesai jika seluruh pembayaran/kredit Anda telah lunas terverifikasi.',
            $isDitolak  => 'Pesanan Anda telah <strong>dibatalkan/ditolak</strong>. Jika ada pertanyaan, silakan hubungi admin.',
            default     => 'Status pesanan Anda telah diperbarui ke <strong>' . esc($label) . '</strong>. Pantau terus di menu Pesanan pada akun MahenGold Anda.',
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

    public function kirimDpTerverifikasi(array $p): bool
    {
        $isi = $this->daftarHtml([
            'Kode Kredit'            => $p['kode_kredit'] ?? '-',
            'Nomor Pesanan'          => $p['kode_pesanan'] ?? '-',
            'Nama Pelanggan'         => $p['nama_pelanggan'] ?? '-',
            'Produk'                 => $p['produk'] ?? '-',
            'Nominal Uang Muka (DP)' => format_rupiah($p['nominal_dp'] ?? 0),
            'Tanggal Pembayaran DP'  => $p['tanggal_bayar_dp'] ?? '-',
            'Bulan Pembayaran DP'    => $p['bulan_bayar_dp'] ?? '-',
            'Total Harga Kredit'     => format_rupiah($p['total_harga_kredit'] ?? 0),
            'Sisa Piutang'           => format_rupiah($p['sisa_piutang'] ?? 0),
            'Status Verifikasi DP'   => $p['status_verifikasi_dp'] ?? 'Terverifikasi',
        ]) . '<p style="margin:16px 0 0;">Pembayaran uang muka (DP) Anda telah sukses diverifikasi oleh admin. Jadwal angsuran kredit Anda kini telah aktif.</p>'
        . '<p style="margin:14px 0 0;"><a href="' . esc(base_url('/akun/kredit/' . ($p['kredit_id'] ?? 0))) . '" '
        . 'style="background:#C9A24B;color:#1c1a17;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:700;">Lihat Detail Kredit &amp; Nota DP</a></p>';

        return $this->kirim(
            'dp_terverifikasi',
            (int) ($p['user_id'] ?? 0),
            'Pembayaran Uang Muka (DP) Terverifikasi — ' . ($p['kode_kredit'] ?? ''),
            'Halo ' . esc($p['nama'] ?? 'Pelanggan') . ', pembayaran uang muka (DP) Anda telah diverifikasi.',
            $isi,
            (int) ($p['kredit_id'] ?? 0)
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
            $email->setFrom($cfg->fromEmail ?: 'no-reply@mahengold.com', $cfg->fromName ?: 'Mahen Gold');
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
