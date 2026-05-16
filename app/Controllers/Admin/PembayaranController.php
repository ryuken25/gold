<?php

namespace App\Controllers\Admin;

use App\Models\JadwalAngsuranModel;
use App\Models\PembayaranAngsuranModel;
use App\Services\PaymentService;
use App\Services\WhatsAppTemplateService;
use Config\Database;
use Throwable;

class PembayaranController extends BaseAdminController
{
    protected PaymentService $paymentService;

    protected WhatsAppTemplateService $whatsAppService;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->paymentService = new PaymentService();
        $this->whatsAppService = new WhatsAppTemplateService();
    }

    public function index(): string
    {
        $rows = Database::connect()->table('pembayaran_angsuran pb')
            ->select('pb.*, k.kode_kredit, n.nama as nama_nasabah')
            ->join('kredit k', 'k.id = pb.kredit_id')
            ->join('nasabah n', 'n.id = k.nasabah_id')
            ->orderBy('pb.created_at', 'DESC')
            ->get()->getResultArray();

        return $this->render('admin/pembayaran/index', [
            'pageTitle' => 'Pembayaran Angsuran',
            'payments' => $rows,
        ]);
    }

    public function create(): string
    {
        $db = Database::connect();
        $selectedCredit = (int) $this->request->getGet('kredit_id');
        return $this->render('admin/pembayaran/form', [
            'pageTitle' => 'Catat Pembayaran',
            'selectedCredit' => $selectedCredit,
            'credits' => $db->table('kredit k')->select('k.*, n.nama as nama_nasabah')->join('nasabah n', 'n.id = k.nasabah_id')->where('k.status', 'aktif')->orderBy('k.created_at', 'DESC')->get()->getResultArray(),
            'schedules' => $db->table('jadwal_angsuran j')->select('j.*, k.kode_kredit')->join('kredit k', 'k.id = j.kredit_id')->orderBy('j.tanggal_jatuh_tempo', 'ASC')->get()->getResultArray(),
        ]);
    }

    public function store()
    {
        $rules = [
            'kredit_id' => 'required|integer',
            'tanggal_bayar' => 'required|valid_date',
            'nominal_bayar' => 'required|decimal',
            'metode_pembayaran' => 'required|in_list[transfer,cash,lainnya]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        try {
            $result = $this->paymentService->record($this->request->getPost(), (int) current_admin()['id']);
            return redirect()->to('/admin/pembayaran')->with('success', 'Pembayaran berhasil dicatat. Klik tombol WA pada daftar untuk kirim notifikasi.');
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function waKonfirmasi(int $id)
    {
        $payment = (new PembayaranAngsuranModel())->find($id);
        if (!$payment) {
            return redirect()->to('/admin/pembayaran')->with('error', 'Pembayaran tidak ditemukan.');
        }

        $credit = Database::connect()->table('kredit k')
            ->select('k.*, n.nama as nama_nasabah, n.no_telepon, p.nama_produk')
            ->join('nasabah n', 'n.id = k.nasabah_id')
            ->join('produk_emas p', 'p.id = k.produk_emas_id')
            ->where('k.id', $payment['kredit_id'])
            ->get()->getRowArray();

        $result = $this->whatsAppService->createPembayaranDiterimaLink([
            'pembayaran_id' => $payment['id'],
            'kode_kredit' => $credit['kode_kredit'],
            'nama_nasabah' => $credit['nama_nasabah'],
            'no_telepon' => $credit['no_telepon'],
            'tanggal_bayar' => $payment['tanggal_bayar'],
            'nominal_bayar' => $payment['nominal_bayar'],
            'total_terbayar' => $credit['total_terbayar'],
            'sisa_piutang' => $credit['sisa_piutang'],
            'status_kredit' => $credit['status'],
            'created_by' => current_admin()['id'],
        ]);

        return redirect()->to($result['wa_url']);
    }
}
