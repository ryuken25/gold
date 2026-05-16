<?php

namespace App\Controllers\Admin;

use Config\Database;

class PiutangController extends BaseAdminController
{
    public function index(): string
    {
        $filter = (string) $this->request->getGet('filter');
        $today = date('Y-m-d');

        $builder = Database::connect()->table('kredit k')
            ->select('k.*, n.nama as nama_nasabah, n.no_telepon, p.nama_produk')
            ->join('nasabah n', 'n.id = k.nasabah_id')
            ->join('produk_emas p', 'p.id = k.produk_emas_id')
            ->orderBy('k.created_at', 'DESC');

        if ($filter === 'aktif' || $filter === 'lunas') {
            $builder->where('k.status', $filter);
        } elseif ($filter === 'jatuh_tempo') {
            $builder->join('jadwal_angsuran j', 'j.kredit_id = k.id')
                ->where('j.tanggal_jatuh_tempo', $today)
                ->whereNotIn('j.status', ['dibayar'])
                ->groupBy('k.id');
        } elseif ($filter === 'terlambat') {
            $builder->join('jadwal_angsuran j', 'j.kredit_id = k.id')
                ->where('j.tanggal_jatuh_tempo <', $today)
                ->whereNotIn('j.status', ['dibayar'])
                ->groupBy('k.id');
        }

        return $this->render('admin/piutang/index', [
            'pageTitle' => 'Monitoring Piutang',
            'filter' => $filter,
            'rows' => $builder->get()->getResultArray(),
        ]);
    }
}
