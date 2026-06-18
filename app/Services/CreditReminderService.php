<?php

namespace App\Services;

use App\Models\KreditModel;
use App\Models\JadwalAngsuranModel;
use App\Models\ReminderAngsuranLogModel;
use App\Models\UserModel;
use App\Models\NasabahModel;
use RuntimeException;

class CreditReminderService
{
    protected KreditModel $kreditModel;
    protected JadwalAngsuranModel $jadwalModel;
    protected ReminderAngsuranLogModel $logModel;
    protected UserModel $userModel;
    protected NasabahModel $nasabahModel;
    protected EmailNotificationService $emailService;

    public function __construct()
    {
        $this->kreditModel = new KreditModel();
        $this->jadwalModel = new JadwalAngsuranModel();
        $this->logModel = new ReminderAngsuranLogModel();
        $this->userModel = new UserModel();
        $this->nasabahModel = new NasabahModel();
        $this->emailService = new EmailNotificationService();
    }

    public function sendManualReminder(int $kreditId, int $jadwalId, int $adminId): array
    {
        $kredit = $this->kreditModel->find($kreditId);
        if (!$kredit) {
            throw new RuntimeException('Kredit tidak ditemukan.');
        }

        if (in_array($kredit['status'], ['lunas', 'dibatalkan'], true)) {
            throw new RuntimeException('Reminder tidak dapat dikirim untuk kredit yang sudah lunas atau dibatalkan.');
        }

        $jadwal = $this->jadwalModel->where('id', $jadwalId)->where('kredit_id', $kreditId)->first();
        if (!$jadwal) {
            throw new RuntimeException('Jadwal angsuran tidak ditemukan.');
        }

        if ($jadwal['status'] === 'dibayar') {
            throw new RuntimeException('Reminder tidak dapat dikirim untuk angsuran yang sudah lunas.');
        }

        $nasabah = $this->nasabahModel->find($kredit['nasabah_id']);
        if (!$nasabah) {
            throw new RuntimeException('Data nasabah tidak ditemukan.');
        }

        // Ambil user untuk mendapatkan email
        $user = $nasabah['user_id'] ? $this->userModel->find($nasabah['user_id']) : null;
        $emailTujuan = $user['email'] ?? '';
        if (!$emailTujuan) {
            throw new RuntimeException('Pelanggan tidak memiliki alamat email tertaut.');
        }

        $nominalTagihan = (int) round((float) $jadwal['nominal_tagihan']);
        $nominalDibayar = (int) round((float) $jadwal['nominal_dibayar']);
        $sisaPembayaran = max(0, $nominalTagihan - $nominalDibayar);
        $sisaPembayaranFormatted = format_rupiah($sisaPembayaran);
        $tanggalJatuhTempo = $jadwal['tanggal_jatuh_tempo'];
        $tanggalJatuhTempoFormatted = format_tanggal($tanggalJatuhTempo);

        $today = date('Y-m-d');
        $diffSecs = strtotime($tanggalJatuhTempo) - strtotime($today);
        $hariSelisih = (int) round($diffSecs / 86400);

        $ke = $jadwal['angsuran_ke'];

        // smart message generation
        if ($hariSelisih < 0) {
            $hariTelat = abs($hariSelisih);
            $message = "Pembayaran angsuran ke-{$ke} sudah telat {$hariTelat} hari. Sisa pembayaran sebesar {$sisaPembayaranFormatted}. Mohon segera lakukan pembayaran.";
        } elseif ($hariSelisih == 0) {
            $message = "Hari ini tenor pembayaran angsuran ke-{$ke} sebesar {$sisaPembayaranFormatted} jatuh tempo. Mohon segera melakukan pembayaran.";
        } elseif ($hariSelisih == 1) {
            $message = "Besok tenor pembayaran angsuran ke-{$ke} sebesar {$sisaPembayaranFormatted} jatuh tempo. Mohon segera melakukan pembayaran.";
        } elseif ($hariSelisih == 3) {
            $message = "3 hari lagi tenor pembayaran angsuran ke-{$ke} sebesar {$sisaPembayaranFormatted} jatuh tempo pada {$tanggalJatuhTempoFormatted}. Mohon lakukan pembayaran tepat waktu.";
        } else {
            $message = "Reminder pembayaran angsuran ke-{$ke} sebesar {$sisaPembayaranFormatted}, jatuh tempo pada {$tanggalJatuhTempoFormatted}.";
        }

        $subject = "Reminder Pembayaran Angsuran ke-{$ke}";

        // Kirim email
        $statusEmail = $this->emailService->kirimReminderManual([
            'user_id'   => $user['id'],
            'kredit_id' => $kreditId,
            'nama'      => $nasabah['nama'],
            'email'     => $emailTujuan,
            'subject'   => $subject,
            'message'   => $message,
        ]);

        $statusStr = $statusEmail ? 'terkirim' : 'gagal';
        $errorMessage = $statusEmail ? null : 'Pengiriman email gagal. Periksa log email.';

        // Simpan log reminder
        $logId = $this->logModel->insert([
            'kredit_id'          => $kreditId,
            'jadwal_angsuran_id' => $jadwalId,
            'user_id'            => $user['id'],
            'nasabah_id'         => $nasabah['id'],
            'jenis'              => 'manual',
            'channel'            => 'email',
            'tujuan'             => $emailTujuan,
            'subjek'             => $subject,
            'pesan'              => $message,
            'status'             => $statusStr,
            'error'              => $errorMessage,
            'dikirim_oleh'        => $adminId,
            'tanggal_referensi'   => $tanggalJatuhTempo,
        ], true);

        return [
            'success'       => $statusEmail,
            'message'       => $statusEmail 
                ? "Reminder angsuran ke-{$ke} berhasil dikirim." 
                : "Reminder gagal dikirim lewat email, tetapi sudah dicatat di log. Cek konfigurasi SMTP.",
            'sent_at'       => date('Y-m-d H:i:s'),
            'angsuran_ke'   => $ke,
            'status_email'  => $statusStr,
            'log_id'        => $logId,
        ];
    }
}
