<?php

namespace App\Controllers\Admin;

use Config\Database;

class TransaksiController extends BaseAdminController
{
    /**
     * Halaman gabungan Transaksi (kredit + cash + pembayaran).
     * Chip filter: Semua | Cash | Kredit
     */
    public function index(): string
    {
        $db   = Database::connect();
        $tipe = (string) $this->request->getGet('tipe');
        $today = date('Y-m-d');

        // Ambil data gabungan
        $rows = [];

        // 1. Data kredit
        $kreditBuilder = $db->table('kredit k')
            ->select("k.*, n.nama as nama_nasabah, p.nama_produk, 'kredit' as tipe_transaksi")
            ->join('nasabah n', 'n.id = k.nasabah_id')
            ->join('produk_emas p', 'p.id = k.produk_emas_id');

        if ($tipe === 'cash') {
            // Skip kredit
        } else {
            $kreditList = $kreditBuilder->orderBy('k.created_at', 'DESC')->get()->getResultArray();
            // Tambah flag is_terlambat
            $jadwalModel = new \App\Models\JadwalAngsuranModel();
            foreach ($kreditList as &$k) {
                $k['is_terlambat'] = false;
                if ($k['status'] === 'aktif') {
                    $angsuran = $jadwalModel->where('kredit_id', $k['id'])
                        ->where('status !=', 'dibayar')
                        ->orderBy('tanggal_jatuh_tempo', 'ASC')->first();
                    if ($angsuran) {
                        $k['is_terlambat'] = strtotime($angsuran['tanggal_jatuh_tempo']) < strtotime('today');
                    }
                }
                // Cek bukti pending
                $k['bukti_pending'] = $db->table('bukti_pembayaran')
                    ->where('kredit_id', $k['id'])->where('status', 'menunggu')->countAllResults();
            }
            unset($k);
            $rows = array_merge($rows, $kreditList);
        }

        // 2. Data cash pengajuan
        if ($tipe !== 'kredit') {
            $cashBuilder = $db->table('pengajuan pg')
                ->select("pg.*, u.nama as nama_user, p.nama_produk, 'cash' as tipe_transaksi")
                ->join('produk_emas p', 'p.id = pg.produk_emas_id', 'left')
                ->join('users u', 'u.id = pg.user_id', 'left')
                ->where('pg.metode_pembayaran', 'cash')
                ->orderBy('pg.created_at', 'DESC');

            $cashList = $cashBuilder->get()->getResultArray();
            foreach ($cashList as &$c) {
                $c['total_pembayaran'] = $c['harga_pokok'] ?? 0;
                $c['total_terbayar']   = 0;
                $c['sisa_piutang']     = 0;
                $c['is_terlambat']     = false;
                $c['bukti_pending']    = $db->table('bukti_pembayaran')
                    ->where('pengajuan_id', $c['id'])->where('status', 'menunggu')->countAllResults();
            }
            unset($c);
            $rows = array_merge($rows, $cashList);
        }

        // Sort by created_at DESC
        usort($rows, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

        return $this->render('admin/transaksi/index', [
            'pageTitle' => 'Transaksi',
            'rows'      => $rows,
            'tipe'      => $tipe,
        ]);
    }

    /**
     * Backward compat: /admin/kredit redirect ke /admin/transaksi?tipe=kredit
     */
    public function redirectKredit()
    {
        return redirect()->to('/admin/transaksi?tipe=kredit');
    }
}
