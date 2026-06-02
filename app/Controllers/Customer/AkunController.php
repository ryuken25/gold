<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\JadwalAngsuranModel;
use App\Models\KreditModel;
use App\Models\NasabahModel;
use App\Models\PengajuanModel;
use App\Models\PengaturanSistemModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class AkunController extends BaseController
{
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
                ->whereIn('nasabah_id', $nasabahIds)
                ->where('status', 'aktif')
                ->orderBy('tanggal_kredit', 'DESC')
                ->findAll();

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
     * Detail satu kredit milik pelanggan + jadwal angsuran.
     */
    public function kreditDetail(int $id): string
    {
        $pelanggan  = current_pelanggan();
        $nasabahIds = $this->nasabahIds((int) $pelanggan['id']);

        $kredit = (new KreditModel())
            ->select('kredit.*, nasabah.nama AS nama_nasabah, produk_emas.nama_produk, produk_emas.kode_produk')
            ->join('nasabah', 'nasabah.id = kredit.nasabah_id', 'left')
            ->join('produk_emas', 'produk_emas.id = kredit.produk_emas_id', 'left')
            ->where('kredit.id', $id)
            ->first();

        if (!$kredit || !in_array((int) $kredit['nasabah_id'], $nasabahIds, true)) {
            throw PageNotFoundException::forPageNotFound('Kredit tidak ditemukan.');
        }

        $jadwal = (new JadwalAngsuranModel())
            ->where('kredit_id', $id)
            ->orderBy('angsuran_ke', 'ASC')
            ->findAll();

        return view('public/akun/kredit_detail', [
            'pageTitle'  => 'Detail Kredit - MahenGold',
            'pengaturan' => $this->pengaturan(),
            'pelanggan'  => $pelanggan,
            'kredit'     => $kredit,
            'jadwal'     => $jadwal,
            'activeTab'  => 'dashboard',
        ]);
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
