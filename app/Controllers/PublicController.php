<?php

namespace App\Controllers;

use App\Models\PengajuanModel;
use App\Models\PengaturanSistemModel;
use App\Models\ProdukEmasModel;
use App\Services\CreditCalculatorService;
use App\Services\WhatsAppTemplateService;
use CodeIgniter\HTTP\ResponseInterface;

class PublicController extends BaseController
{
    protected ProdukEmasModel $produkModel;

    protected PengaturanSistemModel $pengaturanModel;

    protected CreditCalculatorService $calculator;

    protected WhatsAppTemplateService $whatsAppService;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->produkModel = new ProdukEmasModel();
        $this->pengaturanModel = new PengaturanSistemModel();
        $this->calculator = new CreditCalculatorService();
        $this->whatsAppService = new WhatsAppTemplateService();
    }

    public function index(): string
    {
        $produk = $this->produkModel->aktif()->orderBy('id', 'DESC')->findAll(3);

        return view('public/home', [
            'pageTitle' => 'MahenGold - Penjualan dan Kredit Emas',
            'pengaturan' => $this->pengaturanModel->getPengaturan(),
            'produkUnggulan' => $produk,
        ]);
    }

    public function katalog(): string
    {
        $produk = $this->produkModel->aktif()->orderBy('nama_produk', 'ASC')->findAll();
        $marginDefault = (float) $this->pengaturanModel->getPengaturan()['margin_default'];
        $q = trim((string) $this->request->getGet('q'));
        $jenis = trim((string) $this->request->getGet('jenis'));

        if ($q !== '' || $jenis !== '') {
            $produk = array_values(array_filter($produk, static function (array $item) use ($q, $jenis): bool {
                $matchesSearch = $q === '' || stripos($item['kode_produk'] . ' ' . $item['nama_produk'] . ' ' . $item['jenis_emas'] . ' ' . $item['kadar'], $q) !== false;
                $matchesType = $jenis === '' || strcasecmp($item['jenis_emas'], $jenis) === 0;

                return $matchesSearch && $matchesType;
            }));
        }

        foreach ($produk as &$item) {
            $item['simulasi_bulanan'] = $this->calculator->calculate($item['harga_pokok'], $marginDefault, 12, 'bulanan');
            $item['simulasi_mingguan'] = $this->calculator->calculate($item['harga_pokok'], $marginDefault, 12, 'mingguan');
        }
        unset($item);

        return view('public/katalog', [
            'pageTitle' => 'Katalog Emas MahenGold',
            'pengaturan' => $this->pengaturanModel->getPengaturan(),
            'produk' => $produk,
            'marginDefault' => $marginDefault,
        ]);
    }

    public function detail(string $kode): string
    {
        $produk = $this->produkModel->byKode($kode);
        if (!$produk) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Produk tidak ditemukan.');
        }

        $marginDefault = (float) $this->pengaturanModel->getPengaturan()['margin_default'];
        $simulasiDefault = $this->calculator->calculate($produk['harga_pokok'], $marginDefault, 12, 'bulanan');
        $simulasiMingguan = $this->calculator->calculate($produk['harga_pokok'], $marginDefault, 12, 'mingguan');

        return view('public/detail_produk', [
            'pageTitle' => $produk['nama_produk'] . ' - MahenGold',
            'pengaturan' => $this->pengaturanModel->getPengaturan(),
            'produk' => $produk,
            'marginDefault' => $marginDefault,
            'simulasiDefault' => $simulasiDefault,
            'simulasiMingguan' => $simulasiMingguan,
        ]);
    }

    public function simulasi(): ResponseInterface
    {
        $produkId = (int) $this->request->getGet('produk_id');
        $tenor = (int) ($this->request->getGet('tenor_bulan') ?? 12);
        $periode = (string) ($this->request->getGet('periode_angsuran') ?? 'bulanan');
        $nama = trim((string) $this->request->getGet('nama'));
        $alamat = trim((string) $this->request->getGet('alamat'));

        $produk = $this->produkModel->find($produkId);
        if (!$produk) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Produk tidak ditemukan.']);
        }

        $marginDefault = (float) $this->pengaturanModel->getPengaturan()['margin_default'];
        $kalkulasi = $this->calculator->calculate($produk['harga_pokok'], $marginDefault, $tenor, $periode);

        $message = null;
        if ($nama !== '' && $alamat !== '') {
            $message = $this->whatsAppService->createPengajuanLink(array_merge($kalkulasi, [
                'nama' => $nama,
                'alamat' => $alamat,
                'kode_produk' => $produk['kode_produk'],
                'nama_produk' => $produk['nama_produk'],
                'jenis_emas' => $produk['jenis_emas'],
                'kadar' => $produk['kadar'],
                'berat_gram' => $produk['berat_gram'],
                'harga_pokok' => $produk['harga_pokok'],
                'produk_id' => $produk['id'],
            ]));
        }

        return $this->response->setJSON([
            'kalkulasi' => $kalkulasi,
            'preview_message' => $message['message'] ?? null,
            'wa_url' => $message['wa_url'] ?? null,
        ]);
    }

    public function waPengajuan()
    {
        $metode = $this->request->getPost('metode_pembayaran') ?? 'kredit';

        $rules = [
            'produk_id'         => 'required|integer',
            'metode_pembayaran' => 'required|in_list[cash,kredit]',
            'nama'              => 'required|min_length[3]|max_length[150]',
            'alamat'            => 'required|min_length[5]',
        ];

        if ($metode === 'kredit') {
            $rules['tenor_bulan']      = 'required|in_list[6,10,12]';
            $rules['periode_angsuran'] = 'required|in_list[bulanan,mingguan]';
            $rules['foto_ktp']         = 'uploaded[foto_ktp]|is_image[foto_ktp]|mime_in[foto_ktp,image/jpeg,image/jpg,image/png]|max_size[foto_ktp,3072]';
        }

        if (!$this->validate($rules)) {
            $payload = ['errors' => $this->validator->getErrors()];
            return $this->request->isAJAX()
                ? $this->response->setStatusCode(422)->setJSON($payload)
                : redirect()->back()->withInput()->with('error', implode(' ', $payload['errors']));
        }

        $produk = $this->produkModel->find((int) $this->request->getPost('produk_id'));
        if (!$produk) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Produk tidak ditemukan.']);
        }

        $namaFile = null;
        if ($metode === 'kredit') {
            $file = $this->request->getFile('foto_ktp');
            if ($file && $file->isValid() && !$file->hasMoved()) {
                $ktpDir = WRITEPATH . 'uploads/ktp/';
                if (!is_dir($ktpDir)) {
                    mkdir($ktpDir, 0755, true);
                    file_put_contents($ktpDir . 'index.html', '');
                }
                $namaFile = $file->getRandomName();
                $file->move($ktpDir, $namaFile);
            }
        }

        $marginDefault  = (float) $this->pengaturanModel->getPengaturan()['margin_default'];
        $pengajuanData  = [
            'user_id'           => is_pelanggan_logged_in() ? current_pelanggan()['id'] : null,
            'produk_emas_id'    => $produk['id'],
            'metode_pembayaran' => $metode,
            'nama'              => $this->request->getPost('nama'),
            'alamat'            => $this->request->getPost('alamat'),
            'foto_ktp'          => $namaFile,
            'status'            => 'baru',
        ];

        if ($metode === 'kredit') {
            $pengajuanData['tenor_bulan']      = (int) $this->request->getPost('tenor_bulan');
            $pengajuanData['periode_angsuran'] = $this->request->getPost('periode_angsuran');

            $kalkulasi = $this->calculator->calculate(
                $produk['harga_pokok'],
                $marginDefault,
                $pengajuanData['tenor_bulan'],
                (string) $pengajuanData['periode_angsuran']
            );

            (new PengajuanModel())->insert($pengajuanData);

            $result = $this->whatsAppService->createPengajuanLink(array_merge($kalkulasi, [
                'nama'        => $pengajuanData['nama'],
                'alamat'      => $pengajuanData['alamat'],
                'kode_produk' => $produk['kode_produk'],
                'nama_produk' => $produk['nama_produk'],
                'jenis_emas'  => $produk['jenis_emas'],
                'kadar'       => $produk['kadar'],
                'berat_gram'  => $produk['berat_gram'],
                'harga_pokok' => $produk['harga_pokok'],
                'produk_id'   => $produk['id'],
            ]));
        } else {
            (new PengajuanModel())->insert($pengajuanData);

            $result = $this->whatsAppService->createPembelianCashLink([
                'nama'        => $pengajuanData['nama'],
                'alamat'      => $pengajuanData['alamat'],
                'kode_produk' => $produk['kode_produk'],
                'nama_produk' => $produk['nama_produk'],
                'jenis_emas'  => $produk['jenis_emas'],
                'kadar'       => $produk['kadar'],
                'berat_gram'  => $produk['berat_gram'],
                'harga_pokok' => $produk['harga_pokok'],
                'produk_id'   => $produk['id'],
            ]);
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON($result);
        }

        return redirect()->to($result['wa_url']);
    }
}
