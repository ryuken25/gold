<?php

namespace App\Controllers;

use App\Models\BuktiPembayaranModel;
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
            $rules['uang_muka']        = 'required|in_list[200000,500000,1000000]';
            $rules['foto_ktp']         = 'uploaded[foto_ktp]|is_image[foto_ktp]|mime_in[foto_ktp,image/jpeg,image/jpg,image/png]|max_size[foto_ktp,3072]';
        }
        // Bukti pembayaran wajib untuk KEDUA metode (cash maupun kredit).
        $rules['bukti']         = 'uploaded[bukti]|max_size[bukti,3072]|ext_in[bukti,jpg,jpeg,png,pdf]|mime_in[bukti,image/jpeg,image/jpg,image/png,application/pdf]';
        $rules['nama_pengirim'] = 'permit_empty|max_length[150]';
        $rules['no_rekening']   = 'permit_empty|max_length[50]';
        $rules['bank_pengirim'] = 'permit_empty|max_length[50]';

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
            $uangMukaCek = (int) $this->request->getPost('uang_muka');

            if ($uangMukaCek >= $produk['harga_pokok']) {
                $msg = 'Uang muka (' . format_rupiah($uangMukaCek) . ') harus lebih kecil dari harga pokok produk (' . format_rupiah($produk['harga_pokok']) . ').';
                return $this->request->isAJAX()
                    ? $this->response->setStatusCode(422)->setJSON(['errors' => ['uang_muka' => $msg], 'csrf' => csrf_hash()])
                    : redirect()->back()->withInput()->with('error', $msg);
            }

            $cek = $this->calculator->calculate(
                $produk['harga_pokok'],
                (float) $pengaturan['margin_default'],
                (int) $this->request->getPost('tenor_bulan'),
                (string) $this->request->getPost('periode_angsuran'),
                $uangMukaCek
            );
            if ($uangMukaCek >= $cek['total_harga_kredit']) {
                $msg = 'Total harga kredit (' . format_rupiah($cek['total_harga_kredit'])
                    . ') terlalu kecil untuk DP ' . format_rupiah($uangMukaCek) . '.';
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

        // Bukti pembayaran (WAJIB untuk cash maupun kredit) — disimpan
        // sebelum insert pesanan supaya tidak ada order tanpa bukti.
        $buktiFile = null;
        $bukti = $this->request->getFile('bukti');
        if ($bukti && $bukti->isValid() && !$bukti->hasMoved()) {
            $buktiDir = WRITEPATH . 'uploads/bukti/';
            if (!is_dir($buktiDir) && !@mkdir($buktiDir, 0755, true) && !is_dir($buktiDir)) {
                return $this->gagalUpload('Gagal menyiapkan folder upload bukti. Hubungi admin.', 'bukti');
            }
            if (!is_file($buktiDir . 'index.html')) {
                @file_put_contents($buktiDir . 'index.html', '');
            }
            try {
                $buktiFile = $bukti->getRandomName();
                $bukti->move($buktiDir, $buktiFile);
            } catch (\Throwable $e) {
                $buktiFile = null;
            }
        }
        if ($buktiFile === null || !is_file(WRITEPATH . 'uploads/bukti/' . $buktiFile)) {
            return $this->gagalUpload('Bukti pembayaran gagal diunggah. Coba lagi dengan file JPG/PNG/PDF maksimal 3 MB.', 'bukti');
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
            'uang_muka'         => 0, // default 0 for cash
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
                (int) $this->request->getPost('uang_muka')
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

        // Catat aktivitas & simpan bukti pembayaran (tipe = dp untuk kredit, cash untuk lunas).
        (new PengajuanAktivitasModel())->log((int) $pengajuanId, 'dibuat', 'Pesanan dibuat oleh pelanggan', 'pelanggan');

        if ($buktiFile !== null) {
            $buktiModel = new BuktiPembayaranModel();
            $tipeBukti = $metode === 'kredit' ? 'dp' : 'cash';
            $nominalBukti = $metode === 'kredit'
                ? (int) ($pengajuanData['uang_muka'] ?? 0)
                : (int) $produk['harga_pokok'];
            $buktiId = $buktiModel->insert([
                'tipe'          => $tipeBukti,
                'pengajuan_id'  => (int) $pengajuanId,
                'user_id'       => current_pelanggan()['id'],
                'nominal'       => $nominalBukti,
                'nama_pengirim' => $this->request->getPost('nama_pengirim') ?: null,
                'no_rekening'   => $this->request->getPost('no_rekening') ?: null,
                'bank_pengirim' => $this->request->getPost('bank_pengirim') ?: null,
                'file_path'     => $buktiFile,
                'status'        => 'menunggu',
            ], true);
            if ($buktiId) {
                $buktiModel->update($buktiId, ['kode' => generate_kode('BKT', $buktiId)]);
                $pengajuanModel->update($pengajuanId, ['pembayaran_status' => 'menunggu']);
            }
        }

        try {
            (new EmailNotificationService())->kirimPesananDibuat($emailPayload);
        } catch (\Throwable $e) {
            log_message('error', 'Email pesanan_dibuat gagal: ' . $e->getMessage());
        }

        $pesanSukses = 'Pesanan ' . $kode . ' berhasil dibuat. Bukti pembayaran Anda menunggu verifikasi admin.';

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
    protected function gagalUpload(string $pesan, string $field = 'foto_ktp'): ResponseInterface
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(422)->setJSON([
                'errors' => [$field => $pesan],
                'csrf'   => csrf_hash(),
            ]);
        }

        return redirect()->back()->withInput()->with('error', $pesan);
    }

    public function servingProductImage(string $filename): ResponseInterface
    {
        $filename = basename($filename);
        $path = WRITEPATH . 'uploads/produk/' . $filename;
        if (!is_file($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Gambar produk tidak ditemukan.');
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setBody(file_get_contents($path));
    }
}
