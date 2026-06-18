<?php

namespace App\Controllers\Admin;

use App\Models\JadwalAngsuranModel;
use App\Models\KreditModel;
use App\Models\NasabahModel;
use App\Models\PembayaranAngsuranModel;
use App\Models\ProdukEmasModel;
use App\Services\CreditTransactionService;
use Config\Database;
use Throwable;

class KreditController extends BaseAdminController
{
    protected KreditModel $kreditModel;

    protected CreditTransactionService $creditService;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->kreditModel = new KreditModel();
        $this->creditService = new CreditTransactionService();
    }

    public function index(): string
    {
        $db = Database::connect();
        $jadwalModel = new JadwalAngsuranModel();
        $today = date('Y-m-d');

        // UPDATED: Ambil semua data tanpa filter — warna otomatis di view
        $kreditList = $db->table('kredit k')
            ->select('k.*, n.nama as nama_nasabah, p.nama_produk')
            ->join('nasabah n', 'n.id = k.nasabah_id')
            ->join('produk_emas p', 'p.id = k.produk_emas_id')
            ->orderBy('k.created_at', 'DESC')
            ->get()->getResultArray();

        // UPDATED: Tambah flag is_terlambat untuk setiap kredit aktif
        foreach ($kreditList as &$k) {
            $k['is_terlambat'] = false;
            if ($k['status'] === 'aktif') {
                $angsuranBerikutnya = $jadwalModel
                    ->where('kredit_id', $k['id'])
                    ->where('status !=', 'dibayar')
                    ->orderBy('tanggal_jatuh_tempo', 'ASC')
                    ->first();
                if ($angsuranBerikutnya) {
                    $jatuhTempo = strtotime($angsuranBerikutnya['tanggal_jatuh_tempo']);
                    $k['is_terlambat'] = $jatuhTempo < strtotime('today');
                }
            }
        }
        unset($k);

        return $this->render('admin/kredit/index', [
            'pageTitle' => 'Transaksi Kredit & Piutang',
            'kredit'    => $kreditList,
            'status'    => '',
        ]);
    }

    public function create(): string
    {
        return $this->render('admin/kredit/form', [
            'pageTitle' => 'Buat Kredit',
            'nasabah' => (new NasabahModel())->orderBy('nama', 'ASC')->findAll(),
            'produk' => (new ProdukEmasModel())->aktif()->where('stok >', 0)->orderBy('nama_produk', 'ASC')->findAll(),
            'marginDefault' => $this->pengaturanModel->getPengaturan()['margin_default'],
        ]);
    }

    public function store()
    {
        $rules = [
            'nasabah_id' => 'required|integer',
            'produk_emas_id' => 'required|integer',
            'tanggal_kredit' => 'required|valid_date',
            'tanggal_jatuh_tempo_pertama' => 'required|valid_date',
            'tenor_bulan' => 'required|in_list[6,10,12]',
            'periode_angsuran' => 'required|in_list[bulanan,mingguan]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        try {
            $created = $this->creditService->create($this->request->getPost(), (int) current_admin()['id'], (float) $this->pengaturanModel->getPengaturan()['margin_default']);
            return redirect()->to('/admin/kredit/' . $created['kredit']['id'])->with('success', 'Transaksi kredit berhasil dibuat.');
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(int $id): string
    {
        $db = Database::connect();
        $kredit = $db->table('kredit k')
            ->select('k.*, n.nama as nama_nasabah, n.no_telepon, n.alamat, p.nama_produk, p.kadar, p.berat_gram')
            ->join('nasabah n', 'n.id = k.nasabah_id')
            ->join('produk_emas p', 'p.id = k.produk_emas_id')
            ->where('k.id', $id)
            ->get()->getRowArray();

        if (!$kredit) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Kredit tidak ditemukan.');
        }

        $jadwal = (new JadwalAngsuranModel())->where('kredit_id', $id)->orderBy('angsuran_ke', 'ASC')->findAll();
        $payments = (new PembayaranAngsuranModel())->where('kredit_id', $id)->orderBy('tanggal_bayar', 'DESC')->findAll();

        $bukti = $db->table('bukti_pembayaran')->where('kredit_id', $id)->get()->getResultArray();
        $buktiByJadwal = [];
        foreach ($bukti as $b) {
            if ($b['jadwal_angsuran_id']) {
                $buktiByJadwal[(int)$b['jadwal_angsuran_id']] = $b;
            }
        }

        return $this->render('admin/kredit/show', [
            'pageTitle' => 'Detail Kredit',
            'kredit' => $kredit,
            'jadwal' => $jadwal,
            'payments' => $payments,
            'buktiByJadwal' => $buktiByJadwal,
            'jadwalPertama' => $jadwal[0]['tanggal_jatuh_tempo'] ?? null,
        ]);
    }

    public function cancel(int $id)
    {
        try {
            $this->creditService->cancel($id);
            return $this->respondOk('Kredit berhasil dibatalkan.', '/admin/kredit/' . $id);
        } catch (\Exception $e) {
            return $this->respondFail($e->getMessage(), 400);
        }
    }

    public function reminder(int $kreditId, int $jadwalId)
    {
        try {
            $service = new \App\Services\CreditReminderService();
            $adminId = (int) current_admin()['id'];
            $res = $service->sendManualReminder($kreditId, $jadwalId, $adminId);

            if ($res['success']) {
                return $this->respondOk($res['message'], '/admin/kredit/' . $kreditId);
            } else {
                // Return success but warning since email sending failed but log saved
                return $this->respondOk($res['message'], '/admin/kredit/' . $kreditId, ['warning' => true]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gagal mengirim pengingat manual: ' . $e->getMessage());
            return $this->respondFail('Gagal mengirim pengingat: ' . $e->getMessage(), 400);
        }
    }
}
