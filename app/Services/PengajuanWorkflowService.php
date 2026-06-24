<?php

namespace App\Services;

use App\Models\PengajuanAktivitasModel;
use App\Models\PengajuanModel;
use App\Models\PengaturanSistemModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use RuntimeException;

class PengajuanWorkflowService
{
    protected BaseConnection $db;

    public function __construct(
        protected ?PengajuanModel $pengajuanModel = null,
        protected ?PengajuanAktivitasModel $aktivitasModel = null,
        protected ?PengaturanSistemModel $pengaturanModel = null,
        protected ?CreditTransactionService $creditService = null,
        protected ?EmailNotificationService $emailService = null,
    ) {
        $this->pengajuanModel ??= new PengajuanModel();
        $this->aktivitasModel ??= new PengajuanAktivitasModel();
        $this->pengaturanModel ??= new PengaturanSistemModel();
        $this->creditService  ??= new CreditTransactionService();
        $this->emailService   ??= new EmailNotificationService();
        $this->db = Database::connect();
    }

    /**
     * Transisi baru/diproses → disetujui.
     */
    public function verify(int $pengajuanId, int $adminId): array
    {
        $this->db->transStart();

        $pengajuan = $this->lockPengajuan($pengajuanId);

        $this->assertTransition($pengajuan, ['baru', 'diproses'], 'disetujui');

        // Determine pembayaran_status:
        // - For credit orders, auto-set to 'terverifikasi' (verif pesanan = dp di-verif otomatis)
        // - For cash orders, preserve the existing status (waiting for full payment proof verification)
        $pembayaranStatus = $pengajuan['pembayaran_status'] ?? 'belum';
        if ($pengajuan['metode_pembayaran'] === 'kredit') {
            $pembayaranStatus = 'terverifikasi';

            // Auto-verify any associated DP payment proofs that are still 'menunggu'
            $this->db->table('bukti_pembayaran')
                ->where('pengajuan_id', $pengajuanId)
                ->where('tipe', 'dp')
                ->where('status', 'menunggu')
                ->update([
                    'status'            => 'terverifikasi',
                    'diverifikasi_oleh' => $adminId,
                    'diverifikasi_pada' => date('Y-m-d H:i:s'),
                ]);
        }

        $ok = $this->pengajuanModel->update($pengajuanId, [
            'status'            => 'disetujui',
            'diverifikasi_pada' => date('Y-m-d H:i:s'),
            'diverifikasi_oleh' => $adminId,
            'pembayaran_status' => $pembayaranStatus,
        ]);
        if (!$ok) {
            $this->db->transRollback();
            throw new RuntimeException('Gagal menyetujui pesanan.');
        }

        $this->aktivitasModel->log($pengajuanId, 'diverifikasi', 'Pesanan disetujui admin.', 'admin');

        // Auto-create kredit untuk metode kredit
        if ($pengajuan['metode_pembayaran'] === 'kredit') {
            try {
                $marginDefault = (float) $this->pengaturanModel->getPengaturan()['margin_default'];
                $hasil = $this->creditService->createFromPengajuan($pengajuan, $marginDefault);
                if (!empty($hasil['kredit'])) {
                    $this->aktivitasModel->log($pengajuanId, 'kredit_dibuat',
                        'Kredit otomatis dibuat: ' . $hasil['kredit']['kode_kredit'], 'admin');
                }
            } catch (\Throwable $e) {
                log_message('error', 'createFromPengajuan failed: ' . $e->getMessage());
                $this->db->transRollback();
                throw new RuntimeException('Gagal membuat kredit: ' . $e->getMessage());
            }
        }

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            throw new RuntimeException('Gagal memverifikasi pesanan.');
        }

        // Email setelah commit — fetch updated pengajuan
        $updatedPengajuan = $this->pengajuanModel->find($pengajuanId);
        $this->kirimEmailVerifikasi($updatedPengajuan);

        return $updatedPengajuan;
    }

    /**
     * Transisi baru/diproses → ditolak.
     */
    public function reject(int $pengajuanId, int $adminId, string $reason): array
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw new RuntimeException('Alasan penolakan minimal 5 karakter.');
        }
        if (mb_strlen($reason) > 1000) {
            throw new RuntimeException('Alasan penolakan maksimal 1000 karakter.');
        }

        $this->db->transStart();

        $pengajuan = $this->lockPengajuan($pengajuanId);

        $this->assertTransition($pengajuan, ['baru', 'diproses'], 'ditolak');

        $ok = $this->pengajuanModel->update($pengajuanId, [
            'status'         => 'ditolak',
            'catatan'        => $reason,
            'ditolak_pada'   => date('Y-m-d H:i:s'),
            'ditolak_oleh'   => $adminId,
        ]);
        if (!$ok) {
            $this->db->transRollback();
            throw new RuntimeException('Gagal menolak pesanan.');
        }

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
     */
    public function ship(int $pengajuanId, int $adminId, string $method, string $reference): array
    {
        if (!in_array($method, ['resi', 'no_hp'], true)) {
            throw new RuntimeException('Metode pengiriman tidak valid.');
        }
        $reference = trim($reference);
        if ($reference === '') {
            throw new RuntimeException('Referensi pengiriman wajib diisi.');
        }
        if ($method === 'no_hp') {
            $reference = wa_number_normalize($reference);
            if (mb_strlen($reference) < 10) {
                throw new RuntimeException('Nomor HP pengiriman tidak valid.');
            }
        }
        if (mb_strlen($reference) > 255) {
            throw new RuntimeException('Referensi pengiriman terlalu panjang (maks 255 karakter).');
        }

        $this->db->transStart();

        $pengajuan = $this->lockPengajuan($pengajuanId);

        $this->assertTransition($pengajuan, ['disetujui'], 'dikirim');

        // Cek pembayaran terverifikasi
        $this->assertPaymentPrerequisite($pengajuan);

        $ok = $this->pengajuanModel->update($pengajuanId, [
            'status'                 => 'dikirim',
            'metode_pengiriman'      => $method,
            'referensi_pengiriman'   => $reference,
            'dikirim_pada'           => date('Y-m-d H:i:s'),
            'dikirim_oleh'           => $adminId,
        ]);
        if (!$ok) {
            $this->db->transRollback();
            throw new RuntimeException('Gagal menandai pesanan dikirim.');
        }

        $this->aktivitasModel->log($pengajuanId, 'dikirim',
            'Pesanan dikirim via ' . $method . ': ' . $reference, 'admin');

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            throw new RuntimeException('Gagal menandai pesanan dikirim.');
        }

        // Refetch updated row so email contains shipping method/reference details
        $updatedPengajuan = $this->pengajuanModel->find($pengajuanId);
        $this->kirimEmailStatusUpdate($updatedPengajuan, 'dikirim');

        return $updatedPengajuan;
    }

    /**
     * @deprecated Status selesai is now automated by the system. Use receive() to mark received.
     */
    public function complete(int $pengajuanId, int $adminId): array
    {
        throw new RuntimeException('Status selesai tidak bisa dilakukan manual. Konfirmasi diterima terlebih dahulu; sistem akan menyelesaikan otomatis saat pembayaran/kredit lunas.');
    }

    /**
     * Transisi dikirim → diterima.
     */
    public function receive(int $pengajuanId, int $adminId): array
    {
        $this->db->transStart();

        $pengajuan = $this->lockPengajuan($pengajuanId);

        $this->assertTransition($pengajuan, ['dikirim'], 'diterima');

        $ok = $this->pengajuanModel->update($pengajuanId, [
            'status'        => 'diterima',
            'diterima_pada' => date('Y-m-d H:i:s'),
            'diterima_oleh' => $adminId,
        ]);
        if (!$ok) {
            $this->db->transRollback();
            throw new RuntimeException('Gagal menandai pesanan diterima.');
        }

        $this->aktivitasModel->log($pengajuanId, 'diterima', 'Pesanan dikonfirmasi sudah diterima pelanggan.', 'admin');

        // Check and trigger auto-completion immediately inside transition if eligible
        $this->autoCompleteIfEligible($pengajuanId);

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            throw new RuntimeException('Gagal menandai pesanan diterima.');
        }

        // Send appropriate status email outside transaction
        $updatedPengajuan = $this->pengajuanModel->find($pengajuanId);
        if ($updatedPengajuan['status'] === 'selesai') {
            $this->kirimEmailStatusUpdate($updatedPengajuan, 'selesai');
        } else {
            $this->kirimEmailStatusUpdate($updatedPengajuan, 'diterima');
        }

        return $updatedPengajuan;
    }

    /**
     * Selesaikan pesanan secara otomatis jika pembayaran atau kredit telah lunas.
     */
    public function autoCompleteIfEligible(int $pengajuanId): ?array
    {
        $pengajuan = $this->pengajuanModel->find($pengajuanId);
        if (!$pengajuan || $pengajuan['status'] !== 'diterima') {
            return null;
        }

        $eligible = false;
        if ($pengajuan['metode_pembayaran'] === 'cash') {
            $eligible = ($pengajuan['pembayaran_status'] === 'terverifikasi');
        } elseif ($pengajuan['metode_pembayaran'] === 'kredit') {
            $credit = $this->db->table('kredit')->where('pengajuan_id', $pengajuanId)->get()->getRowArray();
            if ($credit) {
                $eligible = ($credit['status'] === 'lunas' || (float)$credit['sisa_piutang'] <= 0);
            }
        }

        if ($eligible) {
            $this->db->transStart();
            $this->pengajuanModel->update($pengajuanId, [
                'status'       => 'selesai',
                'selesai_pada' => date('Y-m-d H:i:s'),
                'selesai_oleh' => null, // system-driven
            ]);
            $this->aktivitasModel->log($pengajuanId, 'selesai', 'Pesanan selesai otomatis karena pembayaran/kredit sudah lunas.', 'system');
            $this->db->transComplete();

            if ($this->db->transStatus()) {
                $updated = $this->pengajuanModel->find($pengajuanId);
                $this->kirimEmailStatusUpdate($updated, 'selesai');
                return $updated;
            }
        }

        return null;
    }

    // ------------------------------------------------------------------

    /**
     * Lock row pengajuan dengan SELECT ... FOR UPDATE (raw SQL).
     */
    protected function lockPengajuan(int $pengajuanId): array
    {
        $row = $this->db->query("SELECT * FROM pengajuan WHERE id = ? FOR UPDATE", [$pengajuanId])->getRowArray();
        if (!$row) {
            $this->db->transRollback();
            throw new RuntimeException('Pengajuan tidak ditemukan.');
        }
        return $row;
    }

    protected function assertTransition(array $pengajuan, array $allowedFrom, string $to): void
    {
        $current = $pengajuan['status'];
        if (!in_array($current, $allowedFrom, true)) {
            throw new RuntimeException(
                'Transisi tidak valid: ' . $current . ' → ' . $to
                . '. Hanya bisa dari: ' . implode(', ', $allowedFrom)
            );
        }
    }

    protected function assertPaymentPrerequisite(array $pengajuan): void
    {
        $metode = $pengajuan['metode_pembayaran'];
        $payStatus = $pengajuan['pembayaran_status'] ?? 'belum';

        if ($metode === 'cash') {
            // Cash: harus terverifikasi sebelum kirim
            if ($payStatus !== 'terverifikasi') {
                throw new RuntimeException('Pembayaran cash belum terverifikasi. Verifikasi pembayaran terlebih dahulu.');
            }
        } elseif ($metode === 'kredit') {
            // Kredit: cek DP
            $uangMuka = (int) ($pengajuan['uang_muka'] ?? 0);
            if ($uangMuka > 0 && $payStatus !== 'terverifikasi') {
                throw new RuntimeException('DP belum terverifikasi. Verifikasi pembayaran DP terlebih dahulu.');
            }
        }
    }

    protected function kirimEmailVerifikasi(array $pengajuan): void
    {
        try {
            // Ambil data produk untuk payload email
            $produk = $this->db->table('produk_emas')
                ->where('id', $pengajuan['produk_emas_id'])
                ->get()->getRowArray();

            $payload = [
                'user_id'           => (int) $pengajuan['user_id'],
                'pengajuan_id'      => (int) $pengajuan['id'],
                'nama'              => $pengajuan['nama'],
                'kode_pesanan'      => $pengajuan['kode_pesanan'],
                'nama_produk'       => $produk['nama_produk'] ?? '-',
                'kode_produk'       => $produk['kode_produk'] ?? '',
                'metode_pembayaran' => $pengajuan['metode_pembayaran'],
            ];

            if ($pengajuan['metode_pembayaran'] === 'kredit') {
                $payload += [
                    'tenor_bulan'        => $pengajuan['tenor_bulan'],
                    'periode_angsuran'   => $pengajuan['periode_angsuran'],
                ];
            }

            $this->emailService->kirimPesananDiverifikasi($payload);

            // Trigger DP verified email if it is credit and DP is required
            if ($pengajuan['metode_pembayaran'] === 'kredit' && (int) ($pengajuan['uang_muka'] ?? 0) > 0) {
                $kredit = $this->db->table('kredit')
                    ->where('pengajuan_id', $pengajuan['id'])
                    ->get()->getRowArray();

                $bukti = $this->db->table('bukti_pembayaran')
                    ->where('pengajuan_id', $pengajuan['id'])
                    ->where('tipe', 'dp')
                    ->where('status', 'terverifikasi')
                    ->get()->getRowArray();

                $dpPayload = [
                    'user_id'              => (int) $pengajuan['user_id'],
                    'nama'                 => $pengajuan['nama'],
                    'nama_pelanggan'       => $pengajuan['nama'],
                    'kode_kredit'          => $kredit['kode_kredit'] ?? '-',
                    'kode_pesanan'         => $pengajuan['kode_pesanan'],
                    'produk'               => trim(($produk['nama_produk'] ?? '-') . (!empty($produk['kode_produk']) ? ' (' . $produk['kode_produk'] . ')' : '')),
                    'nominal_dp'           => $pengajuan['uang_muka'],
                    'tanggal_bayar_dp'     => !empty($bukti['created_at']) ? format_tanggal_id($bukti['created_at']) : format_tanggal_id(date('Y-m-d H:i:s')),
                    'bulan_bayar_dp'       => !empty($bukti['created_at']) ? format_tanggal_id($bukti['created_at'], 'F Y') : format_tanggal_id(date('Y-m-d H:i:s'), 'F Y'),
                    'total_harga_kredit'   => $kredit['total_harga_kredit'] ?? 0,
                    'sisa_piutang'         => $kredit['sisa_piutang'] ?? 0,
                    'status_verifikasi_dp' => 'Terverifikasi',
                    'kredit_id'            => $kredit['id'] ?? 0,
                ];
                $this->emailService->kirimDpTerverifikasi($dpPayload);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Email verifikasi gagal: ' . $e->getMessage());
        }
    }

    protected function kirimEmailStatusUpdate(array $pengajuan, string $newStatus): void
    {
        try {
            $produk = $this->db->table('produk_emas')
                ->where('id', $pengajuan['produk_emas_id'])
                ->get()->getRowArray();

            $payload = [
                'user_id'           => (int) $pengajuan['user_id'],
                'pengajuan_id'      => (int) $pengajuan['id'],
                'nama'              => $pengajuan['nama'],
                'kode_pesanan'      => $pengajuan['kode_pesanan'],
                'nama_produk'       => $produk['nama_produk'] ?? '-',
                'kode_produk'       => $produk['kode_produk'] ?? '',
                'metode_pembayaran' => $pengajuan['metode_pembayaran'],
                'status_baru'       => $newStatus,
            ];

            if ($newStatus === 'dikirim') {
                $payload['metode_pengiriman']    = $pengajuan['metode_pengiriman'] ?? '';
                $payload['referensi_pengiriman'] = $pengajuan['referensi_pengiriman'] ?? '';
            }

            $this->emailService->kirimStatusPesanan($payload);
        } catch (\Throwable $e) {
            log_message('error', 'Email status_update gagal: ' . $e->getMessage());
        }
    }
}
