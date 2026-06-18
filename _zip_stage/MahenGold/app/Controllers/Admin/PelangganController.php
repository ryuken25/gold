<?php

namespace App\Controllers\Admin;

use App\Models\UserModel;

class PelangganController extends BaseAdminController
{
    public function index(): string
    {
        $q = trim((string) $this->request->getGet('q'));

        $model   = new UserModel();
        $builder = $model
            ->select('users.*,
                (SELECT COUNT(*) FROM pengajuan WHERE pengajuan.user_id = users.id) as jumlah_pesanan,
                (SELECT COUNT(*) FROM nasabah WHERE nasabah.user_id = users.id AND nasabah.deleted_at IS NULL) as punya_nasabah')
            ->where('users.role', 'pelanggan')
            ->orderBy('users.created_at', 'DESC');

        if ($q !== '') {
            $builder->groupStart()
                ->like('users.nama', $q)->orLike('users.email', $q)->orLike('users.no_telepon', $q)
                ->groupEnd();
        }

        return $this->render('admin/pelanggan/index', [
            'pageTitle'  => 'Pelanggan',
            'pelanggan'  => $builder->paginate(10),
            'pager'      => $model->pager,
            'q'          => $q,
        ]);
    }
}
