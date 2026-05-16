<?php

namespace App\Controllers\Admin;

use App\Models\ProdukEmasModel;

class ProdukController extends BaseAdminController
{
    protected ProdukEmasModel $produkModel;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->produkModel = new ProdukEmasModel();
    }

    public function index(): string
    {
        $q = trim((string) $this->request->getGet('q'));
        $builder = $this->produkModel->orderBy('created_at', 'DESC');
        if ($q !== '') {
            $builder->groupStart()
                ->like('kode_produk', $q)
                ->orLike('nama_produk', $q)
                ->orLike('jenis_emas', $q)
                ->groupEnd();
        }

        return $this->render('admin/produk/index', [
            'pageTitle' => 'Produk Emas',
            'produk' => $builder->paginate(10),
            'pager' => $this->produkModel->pager,
            'q' => $q,
        ]);
    }

    public function create(): string
    {
        return $this->render('admin/produk/form', [
            'pageTitle' => 'Tambah Produk',
            'produk' => null,
        ]);
    }

    public function store()
    {
        $rules = [
            'kode_produk' => 'required|is_unique[produk_emas.kode_produk]',
            'nama_produk' => 'required|min_length[3]',
            'jenis_emas' => 'required',
            'kadar' => 'required',
            'berat_gram' => 'required|decimal',
            'harga_pokok' => 'required|decimal',
            'stok' => 'required|integer',
            'status' => 'required|in_list[aktif,nonaktif]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->produkModel->insert($this->collectPayload());

        return redirect()->to('/admin/produk')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(int $id): string
    {
        $produk = $this->produkModel->find($id);
        if (!$produk) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Produk tidak ditemukan.');
        }

        return $this->render('admin/produk/form', [
            'pageTitle' => 'Edit Produk',
            'produk' => $produk,
        ]);
    }

    public function update(int $id)
    {
        $produk = $this->produkModel->find($id);
        if (!$produk) {
            return redirect()->to('/admin/produk')->with('error', 'Produk tidak ditemukan.');
        }

        $rules = [
            'kode_produk' => 'required|is_unique[produk_emas.kode_produk,id,' . $id . ']',
            'nama_produk' => 'required|min_length[3]',
            'jenis_emas' => 'required',
            'kadar' => 'required',
            'berat_gram' => 'required|decimal',
            'harga_pokok' => 'required|decimal',
            'stok' => 'required|integer',
            'status' => 'required|in_list[aktif,nonaktif]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->produkModel->update($id, $this->collectPayload());

        return redirect()->to('/admin/produk')->with('success', 'Produk berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        $this->produkModel->delete($id);

        return redirect()->to('/admin/produk')->with('success', 'Produk berhasil dihapus.');
    }

    protected function collectPayload(): array
    {
        return [
            'kode_produk' => strtoupper((string) $this->request->getPost('kode_produk')),
            'nama_produk' => $this->request->getPost('nama_produk'),
            'jenis_emas' => $this->request->getPost('jenis_emas'),
            'kadar' => $this->request->getPost('kadar'),
            'berat_gram' => $this->request->getPost('berat_gram'),
            'harga_pokok' => $this->request->getPost('harga_pokok'),
            'stok' => $this->request->getPost('stok'),
            'deskripsi' => $this->request->getPost('deskripsi'),
            'gambar_url' => $this->request->getPost('gambar_url') ?: null,
            'status' => $this->request->getPost('status'),
        ];
    }
}
