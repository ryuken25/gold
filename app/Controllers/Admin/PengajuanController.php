<?php

namespace App\Controllers\Admin;

use App\Models\PengajuanModel;
use App\Services\CreditCalculatorService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

class PengajuanController extends BaseAdminController
{
    protected PengajuanModel $pengajuanModel;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->pengajuanModel = new PengajuanModel();
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
            'statusList' => ['baru', 'diproses', 'disetujui', 'ditolak', 'selesai'],
        ]);
    }

    public function show(int $id): string
    {
        $db = Database::connect();
        $pengajuan = $db->table('pengajuan pg')
            ->select('pg.*, p.nama_produk, p.kode_produk, p.jenis_emas, p.kadar, p.berat_gram, p.harga_pokok, u.nama as nama_user, u.email as email_user, u.no_telepon as telepon_user')
            ->join('produk_emas p', 'p.id = pg.produk_emas_id', 'left')
            ->join('users u', 'u.id = pg.user_id', 'left')
            ->where('pg.id', $id)
            ->get()->getRowArray();

        if (!$pengajuan) {
            throw PageNotFoundException::forPageNotFound('Pengajuan tidak ditemukan.');
        }

        $simulasi = null;
        if ($pengajuan['metode_pembayaran'] === 'kredit' && $pengajuan['tenor_bulan']) {
            $marginDefault = (float) $this->pengaturanModel->getPengaturan()['margin_default'];
            $simulasi = (new CreditCalculatorService())->calculate(
                (float) $pengajuan['harga_pokok'],
                $marginDefault,
                (int) $pengajuan['tenor_bulan'],
                (string) ($pengajuan['periode_angsuran'] ?? 'bulanan')
            );
        }

        return $this->render('admin/pengajuan/show', [
            'pageTitle'  => 'Detail Pengajuan',
            'pengajuan'  => $pengajuan,
            'simulasi'   => $simulasi,
            'statusList' => ['baru', 'diproses', 'disetujui', 'ditolak', 'selesai'],
        ]);
    }

    public function updateStatus(int $id)
    {
        $pengajuan = $this->pengajuanModel->find($id);
        if (!$pengajuan) {
            throw PageNotFoundException::forPageNotFound('Pengajuan tidak ditemukan.');
        }

        $rules = [
            'status'  => 'required|in_list[baru,diproses,disetujui,ditolak,selesai]',
            'catatan' => 'permit_empty|max_length[1000]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->to('/admin/pengajuan/' . $id)
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->pengajuanModel->update($id, [
            'status'  => $this->request->getPost('status'),
            'catatan' => $this->request->getPost('catatan') ?: null,
        ]);

        return redirect()->to('/admin/pengajuan/' . $id)->with('success', 'Status pengajuan diperbarui.');
    }

    /**
     * Sajikan foto KTP dari folder writable (tidak public-accessible).
     * Hanya bisa diakses admin (route di dalam grup adminauth).
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
}
