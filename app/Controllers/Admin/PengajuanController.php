<?php

namespace App\Controllers\Admin;

use App\Models\PengajuanAktivitasModel;
use App\Models\PengajuanModel;
use App\Services\CreditCalculatorService;
use App\Services\CreditTransactionService;
use App\Services\EmailNotificationService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

class PengajuanController extends BaseAdminController
{
    protected PengajuanModel $pengajuanModel;

    protected PengajuanAktivitasModel $aktivitasModel;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->pengajuanModel = new PengajuanModel();
        $this->aktivitasModel = new PengajuanAktivitasModel();
    }

    public function index(): string
    {
        $db = Database::connect();
        $status = (string) $this->request->getGet('status');

        $builder = $db->table('pengajuan pg')
            ->select('pg.*, p.nama_produk, p.kode_produk, u.nama as nama_user, u.email as email_user')
            ->join('produk_emas p', 'p.id = pg.produk_emas_id', 'left')
            ->join('users u', 'u.id = pg.user_id', 'left')
            ->orderBy('pg.created_at', 'DESC');

        if ($status !== '') {
            $builder->where('pg.status', $status);
        }

        return $this->render('admin/pengajuan/index', [
            'pageTitle'  => 'Pengajuan Masuk',
            'pengajuan'  => $builder->get()->getResultArray(),
            'status'     => $status,
            'statusList' => $this->statusList(),
        ]);
    }

    public function show(int $id): string
    {
        $pengajuan = $this->ambilPengajuan($id);
        if (!$pengajuan) {
            throw PageNotFoundException::forPageNotFound('Pengajuan tidak ditemukan.');
        }

        $simulasi = $this->hitungSimulasi($pengajuan);

        // UPDATED: Hapus semua kode WA manual — notifikasi hanya via email
        return $this->render('admin/pengajuan/show', [
            'pageTitle'  => 'Detail Pengajuan',
            'pengajuan'  => $pengajuan,
            'simulasi'   => $simulasi,
            'aktivitas'  => $this->aktivitasModel->where('pengajuan_id', $id)->orderBy('id', 'DESC')->findAll(),
            'statusList' => $this->statusList(),
        ]);
    }

    public function verifikasi(int $id)
    {
        $pengajuan = $this->ambilPengajuan($id);
        if (!$pengajuan) {
            throw PageNotFoundException::forPageNotFound('Pengajuan tidak ditemukan.');
        }

        $this->pengajuanModel->update($id, ['status' => 'disetujui']);
        $this->aktivitasModel->log($id, 'diverifikasi', 'Pesanan disetujui admin.', $this->adminName());

        // Untuk kredit: otomatis bentuk nasabah + kredit + jadwal angsuran.
        if ($pengajuan['metode_pembayaran'] === 'kredit') {
            try {
                $marginDefault = (float) $this->pengaturanModel->getPengaturan()['margin_default'];
                $hasil = (new CreditTransactionService())->createFromPengajuan($pengajuan, $marginDefault);
                if (!empty($hasil['kredit'])) {
                    $this->aktivitasModel->log($id, 'kredit_dibuat', 'Kredit otomatis dibuat: ' . $hasil['kredit']['kode_kredit'], $this->adminName());
                }
            } catch (\Throwable $e) {
                log_message('error', 'Auto-create kredit gagal (pengajuan ' . $id . '): ' . $e->getMessage());
                session()->setFlashdata('warning', 'Pesanan disetujui, tetapi pembuatan kredit otomatis gagal: ' . $e->getMessage());
            }
        }

        $this->kirimEmailVerifikasi($pengajuan);

        return redirect()->to('/admin/pengajuan/' . $id)->with('success', 'Pesanan diverifikasi & email konfirmasi dikirim.');
    }

    public function tolak(int $id)
    {
        $pengajuan = $this->pengajuanModel->find($id);
        if (!$pengajuan) {
            throw PageNotFoundException::forPageNotFound('Pengajuan tidak ditemukan.');
        }

        if (!in_array($pengajuan['status'], ['baru', 'diproses'], true)) {
            return redirect()->to('/admin/pengajuan/' . $id)
                ->with('error', 'Pesanan sudah diproses/diverifikasi, tidak bisa ditolak.');
        }

        if (!$this->validate(['alasan' => 'required|max_length[1000]'])) {
            return redirect()->to('/admin/pengajuan/' . $id)
                ->with('error', 'Alasan penolakan wajib diisi.');
        }

        $alasan = (string) $this->request->getPost('alasan');
        $this->pengajuanModel->update($id, ['status' => 'ditolak', 'catatan' => $alasan]);
        $this->aktivitasModel->log($id, 'ditolak', $alasan, $this->adminName());

        return redirect()->to('/admin/pengajuan/' . $id)->with('success', 'Pesanan ditolak.');
    }

    public function batalkan(int $id)
    {
        $pengajuan = $this->pengajuanModel->find($id);
        if (!$pengajuan) {
            throw PageNotFoundException::forPageNotFound('Pengajuan tidak ditemukan.');
        }

        $this->pengajuanModel->update($id, ['status' => 'dibatalkan']);
        $this->aktivitasModel->log($id, 'dibatalkan', 'Pesanan dibatalkan oleh admin.', $this->adminName());

        return redirect()->to('/admin/pengajuan/' . $id)->with('success', 'Pesanan dibatalkan.');
    }

    /**
     * Transisi status tambahan (diproses / selesai) via dropdown lanjutan.
     */
    public function updateStatus(int $id)
    {
        $pengajuan = $this->pengajuanModel->find($id);
        if (!$pengajuan) {
            throw PageNotFoundException::forPageNotFound('Pengajuan tidak ditemukan.');
        }

        $rules = [
            // UPDATED: tambahkan 'dikirim' sebagai status valid
            'status'  => 'required|in_list[baru,diproses,disetujui,dikirim,ditolak,dibatalkan,selesai]',
            'catatan' => 'permit_empty|max_length[1000]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->to('/admin/pengajuan/' . $id)
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $status = (string) $this->request->getPost('status');
        $this->pengajuanModel->update($id, [
            'status'  => $status,
            'catatan' => $this->request->getPost('catatan') ?: $pengajuan['catatan'],
        ]);
        $this->aktivitasModel->log($id, 'status_diubah', 'Status diubah menjadi ' . $status, $this->adminName());

        // UPDATED: Kirim email notifikasi setiap perubahan status
        $this->kirimEmailStatusUpdate($pengajuan, $status);

        return redirect()->to('/admin/pengajuan/' . $id)->with('success', 'Status pengajuan diperbarui.');
    }

    /**
     * Sajikan foto KTP dari folder writable (hanya admin).
     */
    public function ktp(int $id): ResponseInterface
    {
        $pengajuan = $this->pengajuanModel->find($id);
        if (!$pengajuan || empty($pengajuan['foto_ktp'])) {
            throw PageNotFoundException::forPageNotFound('Foto KTP tidak ditemukan.');
        }

        $path = WRITEPATH . 'uploads/ktp/' . basename((string) $pengajuan['foto_ktp']);
        if (!is_file($path)) {
            throw PageNotFoundException::forPageNotFound('File KTP tidak ada di server.');
        }

        return $this->response
            ->setHeader('Content-Type', mime_content_type($path) ?: 'application/octet-stream')
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($path) . '"')
            ->setBody((string) file_get_contents($path));
    }

    // ------------------------------------------------------------------

    protected function statusList(): array
    {
        // UPDATED: tambahkan 'dikirim' sebagai status valid
        return ['baru', 'diproses', 'disetujui', 'dikirim', 'ditolak', 'dibatalkan', 'selesai'];
    }

    protected function adminName(): string
    {
        return current_admin()['nama'] ?? 'Admin';
    }

    protected function ambilPengajuan(int $id): ?array
    {
        return Database::connect()->table('pengajuan pg')
            ->select('pg.*, p.nama_produk, p.kode_produk, p.jenis_emas, p.kadar, p.berat_gram, p.harga_pokok, u.nama as nama_user, u.email as email_user, u.no_telepon as telepon_user')
            ->join('produk_emas p', 'p.id = pg.produk_emas_id', 'left')
            ->join('users u', 'u.id = pg.user_id', 'left')
            ->where('pg.id', $id)
            ->get()->getRowArray();
    }

    protected function hitungSimulasi(?array $pengajuan): ?array
    {
        if (!$pengajuan || $pengajuan['metode_pembayaran'] !== 'kredit' || empty($pengajuan['tenor_bulan'])) {
            return null;
        }

        $marginDefault = (float) $this->pengaturanModel->getPengaturan()['margin_default'];

        return (new CreditCalculatorService())->calculate(
            (float) $pengajuan['harga_pokok'],
            $marginDefault,
            (int) $pengajuan['tenor_bulan'],
            (string) ($pengajuan['periode_angsuran'] ?? 'bulanan'),
            (int) ($pengajuan['uang_muka'] ?? 0)
        );
    }

    protected function kirimEmailVerifikasi(array $pengajuan): void
    {
        $simulasi = $this->hitungSimulasi($pengajuan);

        $payload = [
            'user_id'           => (int) $pengajuan['user_id'],
            'pengajuan_id'      => (int) $pengajuan['id'],
            'nama'              => $pengajuan['nama'],
            'kode_pesanan'      => $pengajuan['kode_pesanan'],
            'nama_produk'       => $pengajuan['nama_produk'] ?? '-',
            'kode_produk'       => $pengajuan['kode_produk'] ?? '',
            'metode_pembayaran' => $pengajuan['metode_pembayaran'],
        ];

        if ($simulasi) {
            $payload += [
                'tenor_bulan'        => $pengajuan['tenor_bulan'],
                'periode_angsuran'   => $pengajuan['periode_angsuran'],
                'total_harga_kredit' => $simulasi['total_harga_kredit'],
                'nominal_angsuran'   => $simulasi['nominal_angsuran'],
                'periode_label'      => $simulasi['periode_label'],
            ];
        }

        try {
            (new EmailNotificationService())->kirimPesananDiverifikasi($payload);
        } catch (\Throwable $e) {
            log_message('error', 'Email pesanan_diverifikasi gagal: ' . $e->getMessage());
        }
    }

    /**
     * UPDATED: Kirim email notifikasi saat status pesanan berubah.
     */
    protected function kirimEmailStatusUpdate(array $pengajuan, string $newStatus): void
    {
        $payload = [
            'user_id'           => (int) $pengajuan['user_id'],
            'pengajuan_id'      => (int) $pengajuan['id'],
            'nama'              => $pengajuan['nama'],
            'kode_pesanan'      => $pengajuan['kode_pesanan'],
            'nama_produk'       => $pengajuan['nama_produk'] ?? '-',
            'kode_produk'       => $pengajuan['kode_produk'] ?? '',
            'metode_pembayaran' => $pengajuan['metode_pembayaran'],
            'status_baru'       => $newStatus,
        ];

        try {
            (new EmailNotificationService())->kirimStatusPesanan($payload);
        } catch (\Throwable $e) {
            log_message('error', 'Email status_update gagal: ' . $e->getMessage());
        }
    }
}
