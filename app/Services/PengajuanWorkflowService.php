<?php

namespace App\Services;

use App\Models\PengajuanAktivitasModel;
use App\Models\PengajuanModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use RuntimeException;

class PengajuanWorkflowService
{
    protected BaseConnection $db;

    public function __construct(
        protected ?PengajuanModel $pengajuanModel = null,
        protected ?PengajuanAktivitasModel $aktivitasModel = null,
        protected ?CreditTransactionService $creditService = null,
        protected ?EmailNotificationService $emailService = null,
    ) {
        $this->pengajuanModel ??= new PengajuanModel();
        $this->aktivitasModel ??= new PengajuanAktivitasModel();
        $this->creditService  ??= new CreditTransactionService();
        $this->emailService   ??= new EmailNotificationService();
        $this->db = Database::connect();
    }

    /**
     * Transisi baru/diproses → disetujui.
     * Membuat kredit otomatis bila metode kredit.
     */
    public function verify(int $pengajuanId, int $adminId): array
    {
        $pengajuan = $this->ambilPengajuan($pengajuanId);
        $this->assertTransition($pengajuan, ['baru', 'diproses'], 'disetujui');

        $this->db->transStart();

        $this->pengajuanModel->update($pengajuanId, [
            'status'              => 'disetujui',
            'diverifikasi_pada'   => date('Y-m-d H:i:s'),
            'diverifikasi_oleh'   => $adminId,
        ]);

        $this->aktivitasModel->log($pengajuanId, 'diverifikasi', 'Pesanan disetujui admin.', 'admin');

        // Auto-create kredit + jadwal untuk metode kredit
        if ($pengajuan['metode_pembayaran'] === 'kredit') {
            try {
                $marginDefault = (float) (new PengaturanSistemModel())->getPengaturan()['margin_default'];
                $hasil = $this->creditService->createFromPengajuan($pengajuan, $marginDefault);
                if (!empty($hasil['kredit'])) {
                    $this->aktivitasModel->log($pengajuanId, 'kredit_dibuat',
                        'Kredit otomatis dibuat: ' . $hasil['kredit']['kode_kredit'], 'admin');
                }
            } catch (\Throwable $e) {
                $this->db->transRollback();
                throw new RuntimeException('Gagal membuat kredit: ' . $e->getMessage());
            }
        }

        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            throw new RuntimeException('Gagal memverifikasi pesanan.');
        }

        // Email setelah commit
        $this->kirimEmailVerifikasi($pengajuan);

        return $this->pengajuanModel->find($pengajuanId);
    }

    /**
     * Transisi baru/diproses → ditolak.
     */
    public function reject(int $pengajuanId, int $adminId, string $reason): array
    {
        $pengajuan = $this->ambilPengajuan($pengajuanId);
        $this->assertTransition($pengajuan, ['baru', 'diproses'], 'ditolak');

        $this->db->transStart();

        $this->pengajuanModel->update($pengajuanId, [
            'status'          => 'ditolak',
            'catatan'         => $reason,
            'ditolak_pada'    => date('Y-m-d H:i:s'),
            'ditolak_oleh'    => $adminId,
        ]);

        $this->aktivitasModel->log($pengajuanId, 'ditolak', $reason, 'admin');

        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            throw new RuntimeException('Gagal menolak pesanan.');
        }

        $this->kirimEmailStatusUpdate($pengajuan, 'ditolak');

        return $this->pengajuanModel->find($pengajuanId);
    }

    /**
     * Transisi disetujui → dikirim.
     * Referensi wajib diisi.
     */
    public function ship(int $pengajuanId, int $adminId, string $method, string $reference): array
    {
        $pengajuan = $this->ambilPengajuan($pengajuanId);
        $this->assertTransition($pengajuan, ['disetujui'], 'dikirim');

        if (!in_array($method, ['resi', 'no_hp'], true)) {
            throw new RuntimeException('Metode pengiriman tidak valid.');
        }
        $reference = trim($reference);
        if ($reference === '') {
            throw new RuntimeException('Referensi pengiriman wajib diisi.');
        }
        if ($method === 'no_hp') {
            $reference = wa_number_normalize($reference);
        }

        $this->db->transStart();

        $this->pengajuanModel->update($pengajuanId, [
            'status'                 => 'dikirim',
            'metode_pengiriman'      => $method,
            'referensi_pengiriman'   => $reference,
            'dikirim_pada'           => date('Y-m-d H:i:s'),
            'dikirim_oleh'           => $adminId,
        ]);

        $this->aktivitasModel->log($pengajuanId, 'dikirim',
            'Pesanan dikirim via ' . $method . ': ' . $reference, 'admin');

        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            throw new RuntimeException('Gagal menandai pesanan dikirim.');
        }

        $this->kirimEmailStatusUpdate($pengajuan, 'dikirim');

        return $this->pengajuanModel->find($pengajuanId);
    }

    /**
     * Transisi dikirim → selesai.
     */
    public function complete(int $pengajuanId, int $adminId): array
    {
        $pengajuan = $this->ambilPengajuan($pengajuanId);
        $this->assertTransition($pengajuan, ['dikirim'], 'selesai');

        $this->db->transStart();

        $this->pengajuanModel->update($pengajuanId, [
            'status'          => 'selesai',
            'selesai_pada'    => date('Y-m-d H:i:s'),
            'selesai_oleh'    => $adminId,
        ]);

        $this->aktivitasModel->log($pengajuanId, 'selesai', 'Pesanan selesai.', 'admin');

        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            throw new RuntimeException('Gagal menandai pesanan selesai.');
        }

        $this->kirimEmailStatusUpdate($pengajuan, 'selesai');

        return $this->pengajuanModel->find($pengajuanId);
    }

    // ------------------------------------------------------------------

    protected function ambilPengajuan(int $id): array
    {
        $pengajuan = $this->pengajuanModel->find($id);
        if (!$pengajuan) {
            throw new RuntimeException('Pengajuan tidak ditemukan.');
        }
        return $pengajuan;
    }

    protected function assertTransition(array $pengajuan, array $allowedFrom, string $to): void
    {
        $current = $pengajuan['status'];
        // diproses dianggap sama dengan baru
        if ($current === 'diproses' && in_array('baru', $allowedFrom, true)) {
            $allowedFrom[] = 'diproses';
        }
        if (!in_array($current, $allowedFrom, true)) {
            throw new RuntimeException(
                'Transisi tidak valid: ' . $current . ' → ' . $to
                . '. Hanya bisa dari: ' . implode(', ', $allowedFrom)
            );
        }
    }

    protected function kirimEmailVerifikasi(array $pengajuan): void
    {
        try {
            $this->emailService->kirimPesananDiverifikasi([
                'user_id'           => (int) $pengajuan['user_id'],
                'pengajuan_id'      => (int) $pengajuan['id'],
                'nama'              => $pengajuan['nama'],
                'kode_pesanan'      => $pengajuan['kode_pesanan'],
                'nama_produk'       => $pengajuan['nama_produk'] ?? '-',
                'kode_produk'       => $pengajuan['kode_produk'] ?? '',
                'metode_pembayaran' => $pengajuan['metode_pembayaran'],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Email verifikasi gagal: ' . $e->getMessage());
        }
    }

    protected function kirimEmailStatusUpdate(array $pengajuan, string $newStatus): void
    {
        try {
            $this->emailService->kirimStatusPesanan([
                'user_id'           => (int) $pengajuan['user_id'],
                'pengajuan_id'      => (int) $pengajuan['id'],
                'nama'              => $pengajuan['nama'],
                'kode_pesanan'      => $pengajuan['kode_pesanan'],
                'nama_produk'       => $pengajuan['nama_produk'] ?? '-',
                'kode_produk'       => $pengajuan['kode_produk'] ?? '',
                'metode_pembayaran' => $pengajuan['metode_pembayaran'],
                'status_baru'       => $newStatus,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Email status_update gagal: ' . $e->getMessage());
        }
    }
}
