<?php

namespace App\Services;

use App\Models\JadwalAngsuranModel;
use App\Models\KreditModel;
use App\Models\NasabahModel;
use App\Models\ReminderAngsuranLogModel;
use App\Models\UserModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use RuntimeException;

class ReminderAngsuranService
{
    protected BaseConnection $db;

    public function __construct(
        protected ?ReminderAngsuranLogModel $logModel = null,
        protected ?KreditModel $kreditModel = null,
        protected ?JadwalAngsuranModel $jadwalModel = null,
        protected ?NasabahModel $nasabahModel = null,
        protected ?UserModel $userModel = null,
        protected ?EmailNotificationService $emailService = null,
    ) {
        $this->logModel     ??= new ReminderAngsuranLogModel();
        $this->kreditModel  ??= new KreditModel();
        $this->jadwalModel  ??= new JadwalAngsuranModel();
        $this->nasabahModel ??= new NasabahModel();
        $this->userModel    ??= new UserModel();
        $this->emailService ??= new EmailNotificationService();
        $this->db = Database::connect();
    }

    /**
     * Kirim reminder manual untuk satu jadwal angsuran.
     */
    public function sendManual(int $kreditId, int $jadwalId, int $adminId): array
    {
        $kredit = $this->kreditModel->find($kreditId);
        if (!$kredit) {
            throw new RuntimeException('Kredit tidak ditemukan.');
        }

        $jadwal = $this->jadwalModel
            ->where('id', $jadwalId)
            ->where('kredit_id', $kreditId)
            ->first();
        if (!$jadwal) {
            throw new RuntimeException('Jadwal angsuran tidak ditemukan.');
        }

        if ($jadwal['status'] === 'dibayar') {
            throw new RuntimeException('Angsuran ini sudah lunas.');
        }

        $nasabah = $this->nasabahModel->find($kredit['nasabah_id']);
        if (!$nasabah) {
            throw new RuntimeException('Data nasabah tidak ditemukan.');
        }

        $user = $this->userModel->find($nasabah['user_id'] ?? 0);
        if (!$user || empty($user['email'])) {
            throw new RuntimeException('Email pelanggan tidak ditemukan.');
        }

        $sisaTagihan = max(0, (float) $jadwal['nominal_tagihan'] - (float) $jadwal['nominal_dibayar']);
        $tanggal = date('d/m/Y', strtotime($jadwal['tanggal_jatuh_tempo']));
        $nama = $nasabah['nama'];
        $kode = $kredit['kode_kredit'];

        $pesan = "Yth. Bapak/Ibu {$nama}, kami menginformasikan bahwa pembayaran angsuran ke-{$jadwal['angsuran_ke']} "
            . "sebesar " . format_rupiah($sisaTagihan) . " untuk kredit {$kode} "
            . "dijadwalkan jatuh tempo pada {$tanggal}. "
            . "Mohon memastikan pembayaran dilakukan tepat waktu. Terima kasih.";

        $subjek = "[MahenGold] Pengingat Angsuran ke-{$jadwal['angsuran_ke']} — {$kode}";

        // Kirim email
        $emailSuccess = $this->kirimEmailReminder($user['email'], $subjek, $pesan);

        // Simpan log
        $logId = $this->logModel->insert([
            'kredit_id'           => $kreditId,
            'jadwal_angsuran_id'  => $jadwalId,
            'user_id'             => $nasabah['user_id'] ?? null,
            'nasabah_id'          => $kredit['nasabah_id'],
            'jenis'               => 'manual',
            'channel'             => 'email',
            'tujuan'              => $user['email'],
            'subjek'              => $subjek,
            'pesan'               => $pesan,
            'status'              => $emailSuccess ? 'terkirim' : 'gagal',
            'error'               => $emailSuccess ? null : 'Email gagal dikirim',
            'dikirim_oleh'        => $adminId,
            'tanggal_referensi'   => date('Y-m-d'),
        ], true);

        return [
            'log_id'   => $logId,
            'success'  => $emailSuccess,
            'pesan'    => $pesan,
            'subjek'   => $subjek,
        ];
    }

    /**
     * Kirim reminder otomatis untuk semua jadwal yang memenuhi kriteria.
     */
    public function processAutomatic(): array
    {
        $today = date('Y-m-d');
        $h3 = date('Y-m-d', strtotime('+3 days'));
        $stats = ['diperiksa' => 0, 'terkirim' => 0, 'dilewati' => 0, 'gagal' => 0];

        // Get all unpaid schedules
        $jadwals = $this->db->table('jadwal_angsuran j')
            ->select('j.*, k.kode_kredit, k.nasabah_id, n.user_id, n.nama as nama_nasabah')
            ->join('kredit k', 'k.id = j.kredit_id')
            ->join('nasabah n', 'n.id = k.nasabah_id')
            ->whereNotIn('j.status', ['dibayar'])
            ->where('k.status', 'aktif')
            ->get()->getResultArray();

        foreach ($jadwals as $jadwal) {
            $stats['diperiksa']++;
            $jatuhTempo = $jadwal['tanggal_jatuh_tempo'];

            // Determine reminder type
            $jenis = null;
            if ($jatuhTempo === $h3) {
                $jenis = 'h3';
            } elseif ($jatuhTempo === $today) {
                $jenis = 'jatuh_tempo';
            } elseif ($jatuhTempo < $today) {
                $jenis = 'terlambat';
            }

            if ($jenis === null) {
                $stats['dilewati']++;
                continue;
            }

            // Check idempotency — already sent today?
            $existing = $this->logModel
                ->where('jadwal_angsuran_id', $jadwal['id'])
                ->where('jenis', $jenis)
                ->where('tanggal_referensi', $today)
                ->where('channel', 'email')
                ->first();

            if ($existing) {
                $stats['dilewati']++;
                continue;
            }

            // Get user email
            $user = $this->userModel->find($jadwal['user_id']);
            if (!$user || empty($user['email'])) {
                $stats['dilewati']++;
                continue;
            }

            $sisaTagihan = max(0, (float) $jadwal['nominal_tagihan'] - (float) $jadwal['nominal_dibayar']);
            $nama = $jadwal['nama_nasabah'];
            $kode = $jadwal['kode_kredit'];
            $tanggal = date('d/m/Y', strtotime($jatuhTempo));

            $pesan = match ($jenis) {
                'h3' => "Yth. Bapak/Ibu {$nama}, kami menginformasikan bahwa pembayaran angsuran ke-{$jadwal['angsuran_ke']} "
                    . "sebesar " . format_rupiah($sisaTagihan) . " untuk kredit {$kode} "
                    . "dijadwalkan jatuh tempo pada {$tanggal}. "
                    . "Mohon memastikan pembayaran dilakukan tepat waktu. Terima kasih.",
                'jatuh_tempo' => "Yth. Bapak/Ibu {$nama}, pembayaran angsuran ke-{$jadwal['angsuran_ke']} "
                    . "sebesar " . format_rupiah($sisaTagihan) . " untuk kredit {$kode} "
                    . "jatuh tempo hari ini, {$tanggal}. "
                    . "Mohon melakukan pembayaran sesuai jadwal. Terima kasih.",
                'terlambat' => $this->buildTerlambatMessage($jadwal, $sisaTagihan),
            };

            $labelJenis = match ($jenis) {
                'h3'           => 'H-3',
                'jatuh_tempo'  => 'Jatuh Tempo',
                'terlambat'    => 'Terlambat',
            };

            $subjek = "[MahenGold] {$labelJenis} — Angsuran ke-{$jadwal['angsuran_ke']} — {$kode}";

            $emailSuccess = $this->kirimEmailReminder($user['email'], $subjek, $pesan);

            $this->logModel->insert([
                'kredit_id'           => $jadwal['kredit_id'],
                'jadwal_angsuran_id'  => $jadwal['id'],
                'user_id'             => $jadwal['user_id'],
                'nasabah_id'          => $jadwal['nasabah_id'],
                'jenis'               => $jenis,
                'channel'             => 'email',
                'tujuan'              => $user['email'],
                'subjek'              => $subjek,
                'pesan'               => $pesan,
                'status'              => $emailSuccess ? 'terkirim' : 'gagal',
                'error'               => $emailSuccess ? null : 'Email gagal dikirim',
                'tanggal_referensi'   => $today,
            ]);

            if ($emailSuccess) {
                $stats['terkirim']++;
            } else {
                $stats['gagal']++;
            }
        }

        return $stats;
    }

    protected function buildTerlambatMessage(array $jadwal, float $sisaTagihan): string
    {
        $today = new \DateTime('today');
        $dueDate = new \DateTime($jadwal['tanggal_jatuh_tempo']);
        $hari = (int) $today->diff($dueDate)->format('%a');
        $tanggal = date('d/m/Y', strtotime($jadwal['tanggal_jatuh_tempo']));

        return "Yth. Bapak/Ibu {$jadwal['nama_nasabah']}, pembayaran angsuran ke-{$jadwal['angsuran_ke']} "
            . "sebesar " . format_rupiah($sisaTagihan) . " untuk kredit {$jadwal['kode_kredit']} "
            . "telah melewati jatuh tempo selama {$hari} hari sejak {$tanggal}. "
            . "Mohon segera melakukan pembayaran atau menghubungi admin MahenGold apabila memerlukan bantuan.";
    }

    protected function kirimEmailReminder(string $email, string $subjek, string $pesan): bool
    {
        try {
            $emailService = \Config\Services::email(null, false);
            $emailService->clear();
            $emailService->setFrom('mahengoldofficial@gmail.com', 'Mahen Gold');
            $emailService->setTo($email);
            $emailService->setSubject($subjek);
            $emailService->setMailType('html');
            $emailService->setMessage('<div style="font-family:Arial; padding:20px; background:#f4f4f4;">'
                . '<div style="max-width:500px; margin:0 auto; background:#fff; border-radius:12px; padding:30px; border:1px solid #ddd;">'
                . '<h2 style="color:#C9A24B; text-align:center;">Pengingat Angsuran</h2>'
                . '<p style="font-size:14px; line-height:1.6;">' . nl2br(esc($pesan)) . '</p>'
                . '<hr style="border:0; border-top:1px solid #eee; margin:20px 0;">'
                . '<p style="color:#999; font-size:12px; text-align:center;">Email otomatis — mohon tidak membalas pesan ini.</p>'
                . '</div></div>');

            return $emailService->send(false);
        } catch (\Throwable $e) {
            log_message('error', 'Reminder email gagal: ' . $e->getMessage());
            return false;
        }
    }
}
