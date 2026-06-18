<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\BuktiPembayaranModel;
use App\Models\JadwalAngsuranModel;
use App\Models\KreditModel;
use App\Models\NasabahModel;
use App\Models\PengajuanModel;
use App\Models\PengaturanSistemModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class AkunController extends BaseController
{
    protected function buktiModel(): BuktiPembayaranModel
    {
        return new BuktiPembayaranModel();
    }

    /**
     * Simpan file bukti ke writable/uploads/bukti/. Kembalikan nama file atau null.
     */
    protected function simpanBukti($file): ?string
    {
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return null;
        }
        $dir = WRITEPATH . 'uploads/bukti/';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }
        if (!is_file($dir . 'index.html')) {
            @file_put_contents($dir . 'index.html', '');
        }
        try {
            $nama = $file->getRandomName();
            $file->move($dir, $nama);
            return is_file($dir . $nama) ? $nama : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function aturanBukti(): array
    {
        return ['bukti' => 'uploaded[bukti]|max_size[bukti,3072]|ext_in[bukti,jpg,jpeg,png,pdf]|mime_in[bukti,image/jpeg,image/jpg,image/png,application/pdf]'];
    }
    protected function pengaturan(): array
    {
        return (new PengaturanSistemModel())->getPengaturan();
    }

    /**
     * ID nasabah yang tertaut ke akun pelanggan yang sedang login.
     *
     * @return array<int, int>
     */
    protected function nasabahIds(int $userId): array
    {
        $rows = (new NasabahModel())
            ->where('user_id', $userId)
            ->findColumn('id');

        return array_map('intval', $rows ?? []);
    }

    /**
     * Dashboard ringkasan akun pelanggan.
     */
    public function index(): string
    {
        $pelanggan  = current_pelanggan();
        $userId     = (int) $pelanggan['id'];
        $nasabahIds = $this->nasabahIds($userId);

        $jumlahPesanan = (new PengajuanModel())->where('user_id', $userId)->countAllResults();

        $kreditAktif = [];
        $nextAngsuran = null;

        if ($nasabahIds !== []) {
            $kreditAktif = (new KreditModel())
                ->select('kredit.*, pengajuan.pembayaran_status as dp_status')
                ->join('pengajuan', 'pengajuan.id = kredit.pengajuan_id', 'left')
                ->whereIn('kredit.nasabah_id', $nasabahIds)
                ->where('kredit.status', 'aktif')
                ->orderBy('kredit.tanggal_kredit', 'DESC')
                ->findAll();

            $kreditAktif = array_filter($kreditAktif, function($k) {
                $uangMuka = (int) ($k['uang_muka'] ?? 0);
                $dpStatus = $k['dp_status'] ?? 'belum';
                if ($uangMuka > 0 && $dpStatus !== 'terverifikasi') {
                    return false;
                }
                return true;
            });
            $kreditAktif = array_values($kreditAktif);

            $kreditIds = array_map(static fn ($k) => (int) $k['id'], $kreditAktif);

            if ($kreditIds !== []) {
                $jadwal = (new JadwalAngsuranModel())
                    ->whereIn('kredit_id', $kreditIds)
                    ->where('status !=', 'dibayar')
                    ->orderBy('tanggal_jatuh_tempo', 'ASC')
                    ->first();

                if ($jadwal) {
                    $kredit = null;
                    foreach ($kreditAktif as $k) {
                        if ((int) $k['id'] === (int) $jadwal['kredit_id']) {
                            $kredit = $k;
                            break;
                        }
                    }
                    $nextAngsuran = [
                        'jadwal' => $jadwal,
                        'kredit' => $kredit,
                    ];
                }
            }
        }

        return view('public/akun/index', [
            'pageTitle'      => 'Akun Saya - MahenGold',
            'pengaturan'     => $this->pengaturan(),
            'pelanggan'      => $pelanggan,
            'jumlahPesanan'  => $jumlahPesanan,
            'kreditAktif'    => $kreditAktif,
            'nextAngsuran'   => $nextAngsuran,
            'akunTertaut'    => $nasabahIds !== [],
            'activeTab'      => 'dashboard',
        ]);
    }

    /**
     * Riwayat pesanan / pengajuan pelanggan.
     */
    public function pesanan(): string
    {
        $userId = (int) current_pelanggan()['id'];

        $pengajuan = (new PengajuanModel())
            ->select('pengajuan.*, produk_emas.nama_produk, produk_emas.kode_produk')
            ->join('produk_emas', 'produk_emas.id = pengajuan.produk_emas_id', 'left')
            ->where('pengajuan.user_id', $userId)
            ->orderBy('pengajuan.created_at', 'DESC')
            ->findAll();

        return view('public/akun/pesanan', [
            'pageTitle'  => 'Pesanan Saya - MahenGold',
            'pengaturan' => $this->pengaturan(),
            'pelanggan'  => current_pelanggan(),
            'pengajuan'  => $pengajuan,
            'activeTab'  => 'pesanan',
        ]);
    }

    /**
     * Daftar kredit milik pelanggan yang sedang login.
     */
    public function kredit(): string
    {
        $userId = (int) current_pelanggan()['id'];
        $nasabahIds = $this->nasabahIds($userId);

        $kreditList = [];
        if ($nasabahIds !== []) {
            $kreditList = (new KreditModel())
                ->select('kredit.*, produk_emas.nama_produk, produk_emas.kode_produk, pengajuan.pembayaran_status as dp_status')
                ->join('produk_emas', 'produk_emas.id = kredit.produk_emas_id', 'left')
                ->join('pengajuan', 'pengajuan.id = kredit.pengajuan_id', 'left')
                ->whereIn('kredit.nasabah_id', $nasabahIds)
                ->orderBy('kredit.created_at', 'DESC')
                ->findAll();

            $kreditList = array_filter($kreditList, function($k) {
                $uangMuka = (int) ($k['uang_muka'] ?? 0);
                $dpStatus = $k['dp_status'] ?? 'belum';
                if ($uangMuka > 0 && $dpStatus !== 'terverifikasi') {
                    return false;
                }
                return true;
            });
            $kreditList = array_values($kreditList);
        }

        return view('public/akun/kredit', [
            'pageTitle'  => 'Kredit Saya - MahenGold',
            'pengaturan' => $this->pengaturan(),
            'pelanggan'  => current_pelanggan(),
            'kredit'     => $kreditList,
            'activeTab'  => 'kredit',
        ]);
    }

    /**
     * Sajikan foto KTP milik pelanggan sendiri (scoped ke user_id).
     */
    public function ktp(int $id)
    {
        $userId = (int) current_pelanggan()['id'];

        $pengajuan = (new PengajuanModel())
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

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

    /**
     * Detail satu kredit milik pelanggan + jadwal angsuran.
     */
    public function kreditDetail(int $id): string
    {
        $pelanggan  = current_pelanggan();
        $nasabahIds = $this->nasabahIds((int) $pelanggan['id']);

        $kredit = (new KreditModel())
            ->select('kredit.*, nasabah.nama AS nama_nasabah, produk_emas.nama_produk, produk_emas.kode_produk, pengajuan.pembayaran_status as dp_status')
            ->join('nasabah', 'nasabah.id = kredit.nasabah_id', 'left')
            ->join('produk_emas', 'produk_emas.id = kredit.produk_emas_id', 'left')
            ->join('pengajuan', 'pengajuan.id = kredit.pengajuan_id', 'left')
            ->where('kredit.id', $id)
            ->first();

        if (!$kredit || !in_array((int) $kredit['nasabah_id'], $nasabahIds, true)) {
            throw PageNotFoundException::forPageNotFound('Kredit tidak ditemukan.');
        }

        $jadwal = (new JadwalAngsuranModel())
            ->where('kredit_id', $id)
            ->orderBy('angsuran_ke', 'ASC')
            ->findAll();

        $buktiByJadwal = [];
        foreach ($this->buktiModel()->where('kredit_id', $id)->orderBy('id', 'ASC')->findAll() as $b) {
            if (!empty($b['jadwal_angsuran_id'])) {
                $buktiByJadwal[(int) $b['jadwal_angsuran_id']] = $b;
            }
        }

        return view('public/akun/kredit_detail', [
            'pageTitle'     => 'Detail Kredit - MahenGold',
            'pengaturan'    => $this->pengaturan(),
            'pelanggan'     => $pelanggan,
            'kredit'        => $kredit,
            'jadwal'        => $jadwal,
            'buktiByJadwal' => $buktiByJadwal,
            'activeTab'     => 'dashboard',
        ]);
    }

    /**
     * Upload bukti pembayaran satu angsuran cicilan.
     */
    public function uploadBuktiAngsuran(int $kreditId, int $jadwalId)
    {
        $userId     = (int) current_pelanggan()['id'];
        $nasabahIds = $this->nasabahIds($userId);

        $kredit = (new KreditModel())->find($kreditId);
        if (!$kredit || !in_array((int) $kredit['nasabah_id'], $nasabahIds, true)) {
            throw PageNotFoundException::forPageNotFound('Kredit tidak ditemukan.');
        }

        $jadwal = (new JadwalAngsuranModel())->where('id', $jadwalId)->where('kredit_id', $kreditId)->first();
        if (!$jadwal) {
            throw PageNotFoundException::forPageNotFound('Jadwal angsuran tidak ditemukan.');
        }

        $kembali = redirect()->to('/akun/kredit/' . $kreditId);

        if ($jadwal['status'] === 'dibayar') {
            return $kembali->with('error', 'Angsuran ini sudah lunas.');
        }
        if ($this->buktiModel()->where('jadwal_angsuran_id', $jadwalId)->whereIn('status', ['menunggu', 'terverifikasi'])->countAllResults() > 0) {
            return $kembali->with('error', 'Bukti untuk angsuran ini sudah ada / sedang diproses.');
        }
        if (!$this->validate($this->aturanBukti())) {
            return $kembali->with('error', implode(' ', $this->validator->getErrors()));
        }

        $nama = $this->simpanBukti($this->request->getFile('bukti'));
        if (!$nama) {
            return $kembali->with('error', 'Gagal mengunggah bukti. Gunakan JPG/PNG/PDF maks 3 MB.');
        }

        $bm  = $this->buktiModel();
        $bid = $bm->insert([
            'tipe'               => 'cicilan',
            'kredit_id'          => $kreditId,
            'jadwal_angsuran_id' => $jadwalId,
            'user_id'            => $userId,
            'nominal'            => $jadwal['nominal_tagihan'],
            'file_path'          => $nama,
            'status'             => 'menunggu',
        ], true);
        $bm->update($bid, ['kode' => generate_kode('BKT', $bid)]);

        return $kembali->with('success', 'Bukti pembayaran angsuran ke-' . $jadwal['angsuran_ke'] . ' terkirim, menunggu verifikasi admin.');
    }

    /**
     * Upload bukti pembayaran cash (sekali per pengajuan).
     */
    public function uploadBuktiCash(int $pengajuanId)
    {
        $userId = (int) current_pelanggan()['id'];

        $pengajuan = (new PengajuanModel())
            ->select('pengajuan.*, produk_emas.harga_pokok')
            ->join('produk_emas', 'produk_emas.id = pengajuan.produk_emas_id', 'left')
            ->where('pengajuan.id', $pengajuanId)
            ->where('pengajuan.user_id', $userId)
            ->first();

        if (!$pengajuan) {
            throw PageNotFoundException::forPageNotFound('Pesanan tidak ditemukan.');
        }

        $kembali = redirect()->to('/akun/pesanan/' . $pengajuanId);

        if ($pengajuan['metode_pembayaran'] !== 'cash' || $pengajuan['status'] !== 'disetujui') {
            return $kembali->with('error', 'Pesanan belum bisa dibayar.');
        }
        if ($this->buktiModel()->where('pengajuan_id', $pengajuanId)->whereIn('status', ['menunggu', 'terverifikasi'])->countAllResults() > 0) {
            return $kembali->with('error', 'Bukti pembayaran sudah ada / sedang diproses.');
        }
        // Bukti wajib; info rekening pengirim opsional (bantu admin mencocokkan transfer).
        $rules = array_merge($this->aturanBukti(), [
            'nama_pengirim' => 'permit_empty|max_length[150]',
            'no_rekening'   => 'permit_empty|max_length[50]',
            'bank_pengirim' => 'permit_empty|max_length[50]',
        ]);
        if (!$this->validate($rules)) {
            return $kembali->with('error', implode(' ', $this->validator->getErrors()));
        }

        $nama = $this->simpanBukti($this->request->getFile('bukti'));
        if (!$nama) {
            return $kembali->with('error', 'Gagal mengunggah bukti. Gunakan JPG/PNG/PDF maks 3 MB.');
        }

        $bm  = $this->buktiModel();
        $bid = $bm->insert([
            'tipe'          => 'cash',
            'pengajuan_id'  => $pengajuanId,
            'user_id'       => $userId,
            'nominal'       => $pengajuan['harga_pokok'] ?? 0,
            'nama_pengirim' => $this->request->getPost('nama_pengirim') ?: null,
            'no_rekening'   => $this->request->getPost('no_rekening') ?: null,
            'bank_pengirim' => $this->request->getPost('bank_pengirim') ?: null,
            'file_path'     => $nama,
            'status'        => 'menunggu',
        ], true);
        $bm->update($bid, ['kode' => generate_kode('BKT', $bid)]);

        (new PengajuanModel())->update($pengajuanId, ['pembayaran_status' => 'menunggu']);

        return redirect()->to('/akun/pesanan')->with('success', 'Bukti pembayaran terkirim, menunggu verifikasi admin.');
    }

    /**
     * Upload bukti pembayaran Uang Muka (DP) untuk pesanan kredit (sekali per pengajuan).
     */
    public function uploadBuktiDP(int $pengajuanId)
    {
        $userId = (int) current_pelanggan()['id'];

        $pengajuan = (new PengajuanModel())
            ->where('id', $pengajuanId)
            ->where('user_id', $userId)
            ->first();

        if (!$pengajuan) {
            throw PageNotFoundException::forPageNotFound('Pesanan tidak ditemukan.');
        }

        $kembali = redirect()->to('/akun/pesanan/' . $pengajuanId);

        if ($pengajuan['metode_pembayaran'] !== 'kredit' || $pengajuan['status'] !== 'disetujui') {
            return $kembali->with('error', 'Pesanan belum bisa membayar DP.');
        }

        $dp = (int) round((float) ($pengajuan['uang_muka'] ?? 0));
        if ($dp <= 0) {
            return $kembali->with('error', 'Pesanan ini tidak memerlukan uang muka.');
        }
        if ($this->buktiModel()->where('pengajuan_id', $pengajuanId)->where('tipe', 'dp')->whereIn('status', ['menunggu', 'terverifikasi'])->countAllResults() > 0) {
            return $kembali->with('error', 'Bukti DP sudah ada / sedang diproses.');
        }

        // Bukti wajib; info rekening pengirim opsional (bantu admin mencocokkan transfer).
        $rules = array_merge($this->aturanBukti(), [
            'nama_pengirim' => 'permit_empty|max_length[150]',
            'no_rekening'   => 'permit_empty|max_length[50]',
            'bank_pengirim' => 'permit_empty|max_length[50]',
        ]);
        if (!$this->validate($rules)) {
            return $kembali->with('error', implode(' ', $this->validator->getErrors()));
        }

        $nama = $this->simpanBukti($this->request->getFile('bukti'));
        if (!$nama) {
            return $kembali->with('error', 'Gagal mengunggah bukti. Gunakan JPG/PNG/PDF maks 3 MB.');
        }

        $bm  = $this->buktiModel();
        $bid = $bm->insert([
            'tipe'          => 'dp',
            'pengajuan_id'  => $pengajuanId,
            'user_id'       => $userId,
            'nominal'       => $dp,
            'nama_pengirim' => $this->request->getPost('nama_pengirim') ?: null,
            'no_rekening'   => $this->request->getPost('no_rekening') ?: null,
            'bank_pengirim' => $this->request->getPost('bank_pengirim') ?: null,
            'file_path'     => $nama,
            'status'        => 'menunggu',
        ], true);
        $bm->update($bid, ['kode' => generate_kode('BKT', $bid)]);

        (new PengajuanModel())->update($pengajuanId, ['pembayaran_status' => 'menunggu']);

        return $kembali->with('success', 'Bukti pembayaran DP terkirim, menunggu verifikasi admin.');
    }

    /**
     * Detail satu pesanan pelanggan + status/aksi pembayaran.
     */
    public function pesananDetail(int $id): string
    {
        $userId = (int) current_pelanggan()['id'];

        $pengajuan = (new PengajuanModel())
            ->select('pengajuan.*, produk_emas.nama_produk, produk_emas.kode_produk, produk_emas.harga_pokok, produk_emas.jenis_emas, produk_emas.kadar, produk_emas.berat_gram')
            ->join('produk_emas', 'produk_emas.id = pengajuan.produk_emas_id', 'left')
            ->where('pengajuan.id', $id)
            ->where('pengajuan.user_id', $userId)
            ->first();

        if (!$pengajuan) {
            throw PageNotFoundException::forPageNotFound('Pesanan tidak ditemukan.');
        }

        $bukti   = $this->buktiModel()->where('pengajuan_id', $id)->where('tipe !=', 'dp')->orderBy('id', 'DESC')->first();
        $buktiDp = $this->buktiModel()->where('pengajuan_id', $id)->where('tipe', 'dp')->orderBy('id', 'DESC')->first();
        $kredit  = $pengajuan['metode_pembayaran'] === 'kredit'
            ? (new KreditModel())->where('pengajuan_id', $id)->first()
            : null;

        return view('public/akun/pesanan_detail', [
            'pageTitle'  => 'Detail Pesanan - MahenGold',
            'pengaturan' => $this->pengaturan(),
            'pelanggan'  => current_pelanggan(),
            'pengajuan'  => $pengajuan,
            'bukti'      => $bukti,
            'buktiDp'    => $buktiDp,
            'kredit'     => $kredit,
            'activeTab'  => 'pesanan',
        ]);
    }

    /**
     * Sajikan file bukti pembayaran milik pelanggan sendiri.
     */
    public function bukti(int $id)
    {
        $userId = (int) current_pelanggan()['id'];

        $bukti = $this->buktiModel()->where('id', $id)->where('user_id', $userId)->first();
        if (!$bukti || empty($bukti['file_path'])) {
            throw PageNotFoundException::forPageNotFound('Bukti tidak ditemukan.');
        }

        $path = WRITEPATH . 'uploads/bukti/' . basename((string) $bukti['file_path']);
        if (!is_file($path)) {
            throw PageNotFoundException::forPageNotFound('File bukti tidak ada di server.');
        }

        return $this->response
            ->setHeader('Content-Type', mime_content_type($path) ?: 'application/octet-stream')
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($path) . '"')
            ->setBody((string) file_get_contents($path));
    }

    /**
     * Form profil + ganti password.
     */
    public function profil(): string
    {
        return view('public/akun/profil', [
            'pageTitle'  => 'Profil Saya - MahenGold',
            'pengaturan' => $this->pengaturan(),
            'pelanggan'  => current_pelanggan(),
            'activeTab'  => 'profil',
        ]);
    }

    public function updateProfil()
    {
        $pelanggan = current_pelanggan();
        $userId    = (int) $pelanggan['id'];

        $rules = [
            'nama'       => 'required|min_length[3]|max_length[150]',
            'no_telepon' => 'permit_empty|max_length[20]',
            'email'      => "required|valid_email|is_unique[users.email,id,{$userId}]",
        ];
        $messages = [
            'email' => ['is_unique' => 'Email sudah digunakan akun lain.'],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userModel = new UserModel();
        $userModel->update($userId, [
            'nama'       => $this->request->getPost('nama'),
            'email'      => $this->request->getPost('email'),
            'no_telepon' => $this->request->getPost('no_telepon') ?: null,
        ]);

        $fresh = $userModel->find($userId);
        session()->set('pelanggan_user', [
            'id'         => $fresh['id'],
            'nama'       => $fresh['nama'],
            'email'      => $fresh['email'],
            'no_telepon' => $fresh['no_telepon'],
            'role'       => $fresh['role'],
        ]);

        return redirect()->to('/akun/profil')->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword()
    {
        $pelanggan = current_pelanggan();
        $userId    = (int) $pelanggan['id'];

        $rules = [
            'password_lama'      => 'required',
            'password_baru'      => 'required|min_length[8]',
            'password_konfirmasi' => 'required|matches[password_baru]',
        ];
        $messages = [
            'password_konfirmasi' => ['matches' => 'Konfirmasi password tidak cocok.'],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->to('/akun/profil')->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $userModel = new UserModel();
        $user      = $userModel->find($userId);

        if (!$user || !password_verify((string) $this->request->getPost('password_lama'), $user['password_hash'])) {
            return redirect()->to('/akun/profil')->with('error', 'Password lama salah.');
        }

        $userModel->update($userId, [
            'password_hash' => password_hash((string) $this->request->getPost('password_baru'), PASSWORD_DEFAULT),
        ]);

        return redirect()->to('/akun/profil')->with('success', 'Password berhasil diganti.');
    }
}
