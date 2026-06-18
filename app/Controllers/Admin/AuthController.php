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

    /**
     * Login admin kini disatukan ke /login (dibedakan berdasarkan role).
     */
    public function login()
    {
        if (is_admin_logged_in()) {
            return redirect()->to('/admin/dashboard');
        }

        return redirect()->to('/login');
    }

    public function logout()
    {
        session()->remove('admin_user');
        session()->remove('pelanggan_user');

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success'  => true,
                'message'  => 'Logout berhasil.',
                'redirect' => '/login',
                'csrf'     => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        session()->regenerate(true);
        session()->setFlashdata('success', 'Logout berhasil.');
        return redirect()->to('/login');
    }
}
