<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PengaturanSistemModel;
use App\Models\ProdukEmasModel;
use App\Services\CreditTransactionService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class KreditApiController extends BaseController
{
    public function preview(): ResponseInterface
    {
        try {
            $pengaturan = (new PengaturanSistemModel())->getPengaturan();
            $service = new CreditTransactionService();
            $data = $service->preview($this->request->getVar(), (float) $pengaturan['margin_default']);

            return $this->response->setJSON(['success' => true, 'data' => $data]);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function produkSimulasi(int $id): ResponseInterface
    {
        $produk = (new ProdukEmasModel())->find($id);
        if (!$produk) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Produk tidak ditemukan.']);
        }

        $pengaturan = (new PengaturanSistemModel())->getPengaturan();
        $service = new CreditTransactionService();
        $data = $service->preview([
            'produk_id' => $id,
            'tenor_bulan' => $this->request->getGet('tenor_bulan') ?? 12,
            'periode_angsuran' => $this->request->getGet('periode_angsuran') ?? 'bulanan',
        ], (float) $pengaturan['margin_default']);

        return $this->response->setJSON(['produk' => $produk, 'kalkulasi' => $data]);
    }

    public function referensiHarga(): ResponseInterface
    {
        return $this->response->setJSON([
            'success' => true,
            'mode' => 'stub',
            'message' => 'Referensi harga emas bersifat informasi pendukung. Harga produk tetap ditentukan admin.',
            'source' => 'manual_stub',
            'fetched_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
