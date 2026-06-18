<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\PengaturanSistemModel;
use App\Models\UserModel;

class AuthController extends BaseController
{
    protected UserModel $userModel;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->userModel = new UserModel();
    }

    public function login()
    {
        if (is_admin_logged_in()) {
            return redirect()->to('/admin/dashboard');
        }
        if (is_pelanggan_logged_in()) {
            return redirect()->to('/akun');
        }

        return view('public/auth/login', [
            'pageTitle'   => 'Masuk - MahenGold',
            'pengaturan'  => (new PengaturanSistemModel())->getPengaturan(),
            'redirect'    => $this->safeRedirect($this->request->getGet('redirect')),
        ]);
    }

    /**
     * Hanya izinkan redirect ke path internal (mulai dengan satu "/") untuk
     * mencegah open redirect ke domain eksternal.
     */
    protected function safeRedirect(?string $target): ?string
    {
        $target = trim((string) $target);

        if ($target === '' || !str_starts_with($target, '/') || str_starts_with($target, '//')) {
            return null;
        }

        return $target;
    }

    public function attempt()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Email dan password wajib diisi.');
        }

        $user = $this->userModel
            ->where('email', $this->request->getPost('email'))
            ->first();

        if (!$user || !password_verify((string) $this->request->getPost('password'), $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Email atau password salah.');
        }

        if (!(bool) $user['is_active']) {
            return redirect()->back()->withInput()->with('error', 'Akun Anda tidak aktif. Hubungi admin.');
        }

        // Login tunggal, arahkan sesuai role.
        if ($user['role'] === 'admin') {
            session()->remove('pelanggan_user');
            session()->set('admin_user', [
                'id'       => $user['id'],
                'nama'     => $user['nama'],
                'username' => $user['username'],
                'email'    => $user['email'],
                'role'     => $user['role'],
            ]);

            return redirect()->to('/admin/dashboard')->with('success', 'Selamat datang, ' . $user['nama'] . '!');
        }

        session()->remove('admin_user');
        session()->set('pelanggan_user', [
            'id'          => $user['id'],
            'nama'        => $user['nama'],
            'email'       => $user['email'],
            'no_telepon'  => $user['no_telepon'],
            'role'        => $user['role'],
        ]);

        $redirect = $this->safeRedirect($this->request->getPost('redirect'));

        return redirect()->to($redirect ?? '/akun')->with('success', 'Selamat datang, ' . $user['nama'] . '!');
    }

    public function register()
    {
        if (is_pelanggan_logged_in()) {
            return redirect()->to('/akun');
        }

        return view('public/auth/register', [
            'pageTitle'  => 'Daftar Akun - MahenGold',
            'pengaturan' => (new PengaturanSistemModel())->getPengaturan(),
        ]);
    }

    public function store()
    {
        $rules = [
            'nama'             => 'required|min_length[3]|max_length[150]',
            'email'            => 'required|valid_email|is_unique[users.email]',
            'no_telepon'       => 'permit_empty|max_length[20]',
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ];

        $messages = [
            'email'            => ['is_unique' => 'Email sudah terdaftar. Gunakan email lain atau masuk.'],
            'password_confirm' => ['matches' => 'Konfirmasi password tidak cocok.'],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userId = $this->userModel->insert([
            'nama'          => $this->request->getPost('nama'),
            'email'         => $this->request->getPost('email'),
            'username'      => null,
            'no_telepon'    => $this->request->getPost('no_telepon') ?: null,
            'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'role'          => 'pelanggan',
            'is_active'     => 1,
        ]);

        if (!$userId) {
            return redirect()->back()->withInput()->with('errors', ['Pendaftaran gagal, silakan coba lagi.']);
        }

        return redirect()->to('/login')->with('success', 'Akun berhasil dibuat. Silakan masuk.');
    }

    public function logout()
    {
        session()->remove('pelanggan_user');
        session()->remove('admin_user');
        session()->regenerate(true);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success'  => true,
                'message'  => 'Anda telah keluar.',
                'redirect' => '/login',
                'csrf'     => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        session()->setFlashdata('success', 'Anda telah keluar.');
        return redirect()->to('/login');
    }
}
