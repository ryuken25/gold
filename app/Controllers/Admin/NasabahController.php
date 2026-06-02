<?php

namespace App\Controllers\Admin;

use App\Models\KreditModel;
use App\Models\NasabahModel;
use App\Models\UserModel;
use Config\Database;

class NasabahController extends BaseAdminController
{
    protected NasabahModel $nasabahModel;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->nasabahModel = new NasabahModel();
    }

    /**
     * Daftar akun pelanggan yang bisa ditautkan ke nasabah.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function pelangganOptions(): array
    {
        return (new UserModel())
            ->where('role', 'pelanggan')
            ->orderBy('nama', 'ASC')
            ->findAll();
    }

    public function index(): string
    {
        $q = trim((string) $this->request->getGet('q'));
        $builder = $this->nasabahModel->orderBy('created_at', 'DESC');
        if ($q !== '') {
            $builder->groupStart()->like('kode_nasabah', $q)->orLike('nama', $q)->orLike('no_telepon', $q)->groupEnd();
        }

        return $this->render('admin/nasabah/index', [
            'pageTitle' => 'Nasabah',
            'nasabah' => $builder->paginate(10),
            'pager' => $this->nasabahModel->pager,
            'q' => $q,
        ]);
    }

    public function create(): string
    {
        return $this->render('admin/nasabah/form', [
            'pageTitle'  => 'Tambah Nasabah',
            'nasabah'    => null,
            'pelanggan'  => $this->pelangganOptions(),
        ]);
    }

    public function store()
    {
        $rules = [
            'nama' => 'required|min_length[3]',
            'no_telepon' => 'required|min_length[10]',
            'alamat' => 'required|min_length[5]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $next = $this->nasabahModel->withDeleted()->countAllResults() + 1;
        $this->nasabahModel->insert([
            'kode_nasabah' => generate_kode('NSB', $next),
            'user_id' => $this->resolveUserId($this->request->getPost('user_id')),
            'nama' => $this->request->getPost('nama'),
            'no_telepon' => wa_number_normalize((string) $this->request->getPost('no_telepon')),
            'alamat' => $this->request->getPost('alamat'),
            'catatan' => $this->request->getPost('catatan') ?: null,
        ]);

        return redirect()->to('/admin/nasabah')->with('success', 'Nasabah berhasil ditambahkan.');
    }

    public function edit(int $id): string
    {
        $nasabah = $this->nasabahModel->find($id);
        if (!$nasabah) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Nasabah tidak ditemukan.');
        }

        return $this->render('admin/nasabah/form', [
            'pageTitle'  => 'Edit Nasabah',
            'nasabah'    => $nasabah,
            'pelanggan'  => $this->pelangganOptions(),
        ]);
    }

    public function update(int $id)
    {
        if (!$this->validate(['nama' => 'required|min_length[3]', 'no_telepon' => 'required|min_length[10]', 'alamat' => 'required|min_length[5]'])) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->nasabahModel->update($id, [
            'user_id' => $this->resolveUserId($this->request->getPost('user_id')),
            'nama' => $this->request->getPost('nama'),
            'no_telepon' => wa_number_normalize((string) $this->request->getPost('no_telepon')),
            'alamat' => $this->request->getPost('alamat'),
            'catatan' => $this->request->getPost('catatan') ?: null,
        ]);

        return redirect()->to('/admin/nasabah')->with('success', 'Nasabah berhasil diperbarui.');
    }

    /**
     * Validasi user_id terpilih benar-benar akun pelanggan, jika tidak kembalikan null.
     */
    protected function resolveUserId($value): ?int
    {
        $id = (int) $value;
        if ($id <= 0) {
            return null;
        }

        $user = (new UserModel())->where('id', $id)->where('role', 'pelanggan')->first();

        return $user ? (int) $user['id'] : null;
    }

    public function delete(int $id)
    {
        $this->nasabahModel->delete($id);
        return redirect()->to('/admin/nasabah')->with('success', 'Nasabah berhasil dihapus.');
    }

    public function kartuPiutang(int $id): string
    {
        $nasabah = $this->nasabahModel->find($id);
        if (!$nasabah) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Nasabah tidak ditemukan.');
        }

        $db = Database::connect();
        $credits = $db->table('kredit k')
            ->select('k.*, p.nama_produk')
            ->join('produk_emas p', 'p.id = k.produk_emas_id')
            ->where('k.nasabah_id', $id)
            ->orderBy('k.created_at', 'DESC')
            ->get()->getResultArray();

        $summary = $db->table('kredit')
            ->select('SUM(total_harga_kredit) as total_kredit, SUM(total_terbayar) as total_terbayar, SUM(sisa_piutang) as sisa_piutang')
            ->where('nasabah_id', $id)
            ->get()->getRowArray();

        return $this->render('admin/nasabah/kartu_piutang', [
            'pageTitle' => 'Kartu Piutang Nasabah',
            'nasabah' => $nasabah,
            'credits' => $credits,
            'summary' => $summary,
        ]);
    }
}
