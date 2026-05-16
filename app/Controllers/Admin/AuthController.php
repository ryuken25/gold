<?php

namespace App\Controllers\Admin;

use App\Models\PengaturanSistemModel;
use App\Models\UserModel;

class AuthController extends BaseAdminController
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

        return view('admin/auth/login', [
            'pageTitle' => 'Login Admin MahenGold',
            'pengaturan' => (new PengaturanSistemModel())->getPengaturan(),
        ]);
    }

    public function attempt()
    {
        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Username dan password wajib diisi.');
        }

        $user = $this->userModel->where('username', $this->request->getPost('username'))->where('role', 'admin')->first();
        if (!$user || !password_verify((string) $this->request->getPost('password'), $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Username atau password salah.');
        }

        if (!(bool) $user['is_active']) {
            return redirect()->back()->withInput()->with('error', 'Akun admin tidak aktif.');
        }

        session()->set('admin_user', [
            'id' => $user['id'],
            'nama' => $user['nama'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
        ]);

        return redirect()->to('/admin/dashboard')->with('success', 'Login berhasil.');
    }

    public function logout()
    {
        session()->remove('admin_user');
        session()->setFlashdata('success', 'Logout berhasil.');

        return redirect()->to('/admin/login');
    }
}
