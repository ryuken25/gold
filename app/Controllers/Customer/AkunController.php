<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\PengajuanModel;
use App\Models\PengaturanSistemModel;

class AkunController extends BaseController
{
    public function index(): string
    {
        $pelanggan  = current_pelanggan();
        $pengajuan  = (new PengajuanModel())
            ->where('user_id', $pelanggan['id'])
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return view('public/akun/index', [
            'pageTitle'  => 'Akun Saya - MahenGold',
            'pengaturan' => (new PengaturanSistemModel())->getPengaturan(),
            'pelanggan'  => $pelanggan,
            'pengajuan'  => $pengajuan,
        ]);
    }
}
