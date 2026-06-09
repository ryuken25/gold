<?php

namespace App\Controllers;

use App\Models\PengajuanAktivitasModel;
use App\Models\PengajuanModel;
use App\Models\PengaturanSistemModel;
use App\Models\ProdukEmasModel;
use App\Services\CreditCalculatorService;
use App\Services\EmailNotificationService;
use CodeIgniter\HTTP\ResponseInterface;

class PublicController extends BaseController
{
    protected ProdukEmasModel $produkModel;

    protected PengaturanSistemModel $pengaturanModel;

    protected CreditCalculatorService $calculator;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->produkModel = new ProdukEmasModel();
        $this->pengaturanModel = new PengaturanSistemModel();
        $this->calculator = new CreditCalculatorService();
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
        $uangMuka = (float) ($this->request->getGet('uang_muka') ?? 0);

        $produk = $this->produkModel->find($produkId);
        if (!$produk) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Produk tidak ditemukan.']);
        }

        $marginDefault = (float) $this->pengaturanModel->getPengaturan()['margin_default'];
        $kalkulasi = $this->calculator->calculate($produk['harga_pokok'], $marginDefault, $tenor, $periode, $uangMuka);

        // Kalkulasi angsuran (termasuk uang_muka & sisa_pokok) untuk live update ringkasan.
        return $this->response->setJSON(['kalkulasi' => $kalkulasi]);
    }

    public function ajukanPesanan()
    {
        if (!is_pelanggan_logged_in()) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(401)->setJSON([
                    'message' => 'Silakan masuk terlebih dahulu untuk memesan.',
                    'redirect' => base_url('/login'),
                    'csrf' => csrf_hash(),
                ]);
            }

            return redirect()->to('/login')->with('error', 'Silakan masuk terlebih dahulu untuk memesan.');
        }

        $metode = $this->request->getPost('metode_pembayaran') ?? 'kredit';

        $rules = [
            'produk_id'         => 'required|integer',
            'metode_pembayaran' => 'required|in_list[cash,kredit]',
            'nama'              => 'required|min_length[3]|max_length[150]',
            'no_telepon'        => 'required|min_length[8]|max_length[20]|regex_match[/^[0-9+\-\s]+$/]',
            'alamat'            => 'required|min_length[5]',
        ];

        if ($metode === 'kredit') {
            $rules['tenor_bulan']      = 'required|in_list[6,10,12]';
            $rules['periode_angsuran'] = 'required|in_list[bulanan,mingguan]';
            $rules['uang_muka']        = 'required|numeric';
            $rules['foto_ktp']         = 'uploaded[foto_ktp]|is_image[foto_ktp]|mime_in[foto_ktp,image/jpeg,image/jpg,image/png]|max_size[foto_ktp,3072]';
        }

        if (!$this->validate($rules)) {
            $payload = ['errors' => $this->validator->getErrors(), 'csrf' => csrf_hash()];
            return $this->request->isAJAX()
                ? $this->response->setStatusCode(422)->setJSON($payload)
                : redirect()->back()->withInput()->with('error', implode(' ', $payload['errors']));
        }

        $produk = $this->produkModel->find((int) $this->request->getPost('produk_id'));
        if (!$produk) {
            return $this->request->isAJAX()
                ? $this->response->setStatusCode(404)->setJSON(['message' => 'Produk tidak ditemukan.', 'csrf' => csrf_hash()])
                : redirect()->back()->withInput()->with('error', 'Produk tidak ditemukan.');
        }

        // Pra-validasi DP untuk kredit (sebelum upload KTP, hindari file yatim).
        if ($metode === 'kredit') {
            $pengaturan  = $this->pengaturanModel->getPengaturan();
            $dpMinimal   = (int) ($pengaturan['dp_minimal'] ?? 0);
            $uangMukaCek = (int) round((float) $this->request->getPost('uang_muka'));
            $cek = $this->calculator->calculate(
                $produk['harga_pokok'],
                (float) $pengaturan['margin_default'],
                (int) $this->request->getPost('tenor_bulan'),
                (string) $this->request->getPost('periode_angsuran'),
                $uangMukaCek
            );
            if ($uangMukaCek < $dpMinimal || $uangMukaCek >= $cek['total_harga_kredit']) {
                $msg = 'Uang muka minimal ' . format_rupiah($dpMinimal)
                    . ' dan harus lebih kecil dari total harga kredit (' . format_rupiah($cek['total_harga_kredit']) . ').';
                return $this->request->isAJAX()
                    ? $this->response->setStatusCode(422)->setJSON(['errors' => ['uang_muka' => $msg], 'csrf' => csrf_hash()])
                    : redirect()->back()->withInput()->with('error', $msg);
            }
        }

        $namaFile = null;
        if ($metode === 'kredit') {
            $file = $this->request->getFile('foto_ktp');
            if ($file && $file->isValid() && !$file->hasMoved()) {
                $ktpDir = WRITEPATH . 'uploads/ktp/';
                if (!is_dir($ktpDir) && !@mkdir($ktpDir, 0755, true) && !is_dir($ktpDir)) {
                    return $this->gagalUpload('Gagal menyiapkan folder upload KTP. Hubungi admin.');
                }
                if (!is_file($ktpDir . 'index.html')) {
                    @file_put_contents($ktpDir . 'index.html', '');
                }
                try {
                    $namaFile = $file->getRandomName();
                    $file->move($ktpDir, $namaFile);
                } catch (\Throwable $e) {
                    $namaFile = null;
                }
            }

            // Untuk kredit, KTP wajib benar-benar tersimpan sebelum lanjut.
            if ($namaFile === null || !is_file(WRITEPATH . 'uploads/ktp/' . $namaFile)) {
                return $this->gagalUpload('Foto KTP gagal diunggah. Coba lagi dengan file JPG/PNG maksimal 3 MB.');
            }
        }

        // No. WhatsApp dari form (fallback profil), disimpan ter-normalisasi.
        $noTeleponInput = trim((string) $this->request->getPost('no_telepon'));
        $noTelepon = wa_number_normalize($noTeleponInput !== '' ? $noTeleponInput : (string) (current_pelanggan()['no_telepon'] ?? ''));

        $pengajuanModel = new PengajuanModel();
        $kode = $pengajuanModel->generateKodePesanan();

        $marginDefault  = (float) $this->pengaturanModel->getPengaturan()['margin_default'];
        $pengajuanData  = [
            'kode_pesanan'      => $kode,
            'user_id'           => current_pelanggan()['id'],
            'no_telepon'        => $noTelepon,
            'produk_emas_id'    => $produk['id'],
            'metode_pembayaran' => $metode,
            'nama'              => $this->request->getPost('nama'),
            'alamat'            => $this->request->getPost('alamat'),
            'foto_ktp'          => $namaFile,
            'status'            => 'baru',
        ];

        // Payload untuk email notifikasi (dilengkapi simulasi bila kredit).
        $emailPayload = [
            'user_id'           => current_pelanggan()['id'],
            'nama'              => $pengajuanData['nama'],
            'kode_pesanan'      => $kode,
            'nama_produk'       => $produk['nama_produk'],
            'kode_produk'       => $produk['kode_produk'],
            'metode_pembayaran' => $metode,
        ];

        if ($metode === 'kredit') {
            $pengajuanData['tenor_bulan']      = (int) $this->request->getPost('tenor_bulan');
            $pengajuanData['periode_angsuran'] = $this->request->getPost('periode_angsuran');

            $kalkulasi = $this->calculator->calculate(
                $produk['harga_pokok'],
                $marginDefault,
                $pengajuanData['tenor_bulan'],
                (string) $pengajuanData['periode_angsuran'],
                (int) round((float) $this->request->getPost('uang_muka'))
            );

            $pengajuanData['uang_muka'] = $kalkulasi['uang_muka'];

            $emailPayload += [
                'tenor_bulan'        => $pengajuanData['tenor_bulan'],
                'periode_angsuran'   => $pengajuanData['periode_angsuran'],
                'total_harga_kredit' => $kalkulasi['total_harga_kredit'],
                'uang_muka'          => $kalkulasi['uang_muka'],
                'sisa_pokok'         => $kalkulasi['sisa_pokok'],
                'nominal_angsuran'   => $kalkulasi['nominal_angsuran'],
                'periode_label'      => $kalkulasi['periode_label'],
            ];
        }

        $pengajuanId = $pengajuanModel->insert($pengajuanData);

        if (!$pengajuanId) {
            return $this->gagalUpload('Pesanan gagal disimpan. Silakan coba lagi.');
        }

        $emailPayload['pengajuan_id'] = $pengajuanId;

        // Catat aktivitas & kirim email (email tidak boleh menggagalkan alur).
        (new PengajuanAktivitasModel())->log((int) $pengajuanId, 'dibuat', 'Pesanan dibuat oleh pelanggan', 'pelanggan');

        try {
            (new EmailNotificationService())->kirimPesananDibuat($emailPayload);
        } catch (\Throwable $e) {
            log_message('error', 'Email pesanan_dibuat gagal: ' . $e->getMessage());
        }

        $pesanSukses = 'Pesanan ' . $kode . ' berhasil dibuat dan menunggu verifikasi admin.';

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success'      => true,
                'kode_pesanan' => $kode,
                'message'      => $pesanSukses,
                'redirect'     => base_url('/akun/pesanan'),
                'csrf'         => csrf_hash(),
            ]);
        }

        return redirect()->to('/akun/pesanan')->with('success', $pesanSukses);
    }

    /**
     * Respons kegagalan upload KTP: JSON untuk AJAX, redirect untuk non-AJAX.
     */
    protected function gagalUpload(string $pesan): ResponseInterface
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(422)->setJSON([
                'errors' => ['foto_ktp' => $pesan],
                'csrf'   => csrf_hash(),
            ]);
        }

        return redirect()->back()->withInput()->with('error', $pesan);
    }
}
