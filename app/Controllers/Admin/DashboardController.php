<?php

namespace App\Controllers\Admin;

use App\Models\JadwalAngsuranModel;
use App\Models\KreditModel;
use App\Models\NasabahModel;
use App\Models\PembayaranAngsuranModel;
use App\Models\WhatsAppLogModel;
use Config\Database;

class DashboardController extends BaseAdminController
{
    public function index(): string
    {
        $db = Database::connect();
        $kreditModel = new KreditModel();
        $pembayaranModel = new PembayaranAngsuranModel();
        $jadwalModel = new JadwalAngsuranModel();
        $waLogModel = new WhatsAppLogModel();

        $today = date('Y-m-d');

        $metrics = [
            'total_nilai_kredit' => (float) ($db->table('kredit')->selectSum('total_harga_kredit')->where('status !=', 'dibatalkan')->get()->getRow('total_harga_kredit') ?? 0),
            'total_pembayaran' => (float) ($db->table('pembayaran_angsuran')->selectSum('nominal_bayar')->get()->getRow('nominal_bayar') ?? 0),
            'total_sisa_piutang' => (float) ($db->table('kredit')->selectSum('sisa_piutang')->where('status', 'aktif')->get()->getRow('sisa_piutang') ?? 0),
            'kredit_aktif' => $kreditModel->where('status', 'aktif')->countAllResults(),
            'kredit_lunas' => $kreditModel->where('status', 'lunas')->countAllResults(),
            'jatuh_tempo_hari_ini' => $jadwalModel->where('tanggal_jatuh_tempo', $today)->whereNotIn('status', ['dibayar'])->countAllResults(),
            'angsuran_terlambat' => $jadwalModel->where('tanggal_jatuh_tempo <', $today)->whereNotIn('status', ['dibayar'])->countAllResults(),
        ];

        $recentCredits = $db->table('kredit k')
            ->select('k.*, n.nama as nama_nasabah, p.nama_produk')
            ->join('nasabah n', 'n.id = k.nasabah_id')
            ->join('produk_emas p', 'p.id = k.produk_emas_id')
            ->orderBy('k.created_at', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        $recentPayments = $db->table('pembayaran_angsuran pb')
            ->select('pb.*, k.kode_kredit, n.nama as nama_nasabah')
            ->join('kredit k', 'k.id = pb.kredit_id')
            ->join('nasabah n', 'n.id = k.nasabah_id')
            ->orderBy('pb.created_at', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        $upcomingDue = $db->table('jadwal_angsuran j')
            ->select('j.*, k.kode_kredit, k.sisa_piutang, n.nama as nama_nasabah')
            ->join('kredit k', 'k.id = j.kredit_id')
            ->join('nasabah n', 'n.id = k.nasabah_id')
            ->whereNotIn('j.status', ['dibayar'])
            ->orderBy('j.tanggal_jatuh_tempo', 'ASC')
            ->limit(7)
            ->get()->getResultArray();

        $topReceivables = $db->table('kredit k')
            ->select('n.nama, n.no_telepon, SUM(k.total_harga_kredit) as total_kredit, SUM(k.total_terbayar) as total_terbayar, SUM(k.sisa_piutang) as sisa_piutang')
            ->join('nasabah n', 'n.id = k.nasabah_id')
            ->groupBy('n.id')
            ->orderBy('sisa_piutang', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        return $this->render('admin/dashboard/index', [
            'pageTitle' => 'Dashboard Admin',
            'metrics' => $metrics,
            'recentCredits' => $recentCredits,
            'recentPayments' => $recentPayments,
            'upcomingDue' => $upcomingDue,
            'topReceivables' => $topReceivables,
            'recentLogs' => $waLogModel->orderBy('created_at', 'DESC')->findAll(5),
        ]);
    }
}
