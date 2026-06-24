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

    public function notaDp(int $kreditId)
    {
        return $this->renderNotaDp($kreditId, false);
    }

    public function printDp(int $kreditId)
    {
        return $this->renderNotaDp($kreditId, true);
    }

    protected function renderNotaDp(int $kreditId, bool $print)
    {
        $kredit = $this->kreditModel
            ->select('kredit.*, nasabah.nama AS nama_nasabah, produk_emas.nama_produk, produk_emas.kode_produk')
            ->join('nasabah', 'nasabah.id = kredit.nasabah_id', 'left')
            ->join('produk_emas', 'produk_emas.id = kredit.produk_emas_id', 'left')
            ->where('kredit.id', $kreditId)
            ->first();

        if (!$kredit) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Kredit tidak ditemukan.');
        }

        $pengajuan = (new \App\Models\PengajuanModel())->find($kredit['pengajuan_id']);
        if (!$pengajuan || ($pengajuan['pembayaran_status'] ?? 'belum') !== 'terverifikasi') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Uang muka belum diverifikasi.');
        }

        $bukti = (new \App\Models\BuktiPembayaranModel())
            ->where('pengajuan_id', $kredit['pengajuan_id'])
            ->where('tipe', 'dp')
            ->where('status', 'terverifikasi')
            ->orderBy('id', 'DESC')
            ->first();

        $tanggal_bayar = !empty($bukti['created_at']) ? format_tanggal_id($bukti['created_at']) : format_tanggal_id($kredit['dp_verified_at'] ?: date('Y-m-d H:i:s'));
        $bulan_bayar = !empty($bukti['created_at']) ? format_tanggal_id($bukti['created_at'], 'F Y') : format_tanggal_id($kredit['dp_verified_at'] ?: date('Y-m-d H:i:s'), 'F Y');

        return view('public/akun/nota', [
            'pageTitle'     => 'Nota DP ' . ($kredit['kode_kredit'] ?? ''),
            'pengaturan'    => (new \App\Models\PengaturanSistemModel())->getPengaturan(),
            'tipe'          => 'dp',
            'kredit'        => $kredit,
            'pengajuan'     => $pengajuan,
            'bukti'         => $bukti,
            'tanggal_bayar' => $tanggal_bayar,
            'bulan_bayar'   => $bulan_bayar,
            'print'         => $print,
            'backUrl'       => base_url('/admin/kredit/' . $kreditId),
        ]);
    }

    public function notaAngsuran(int $kreditId, int $jadwalId)
    {
        return $this->renderNotaAngsuran($kreditId, $jadwalId, false);
    }

    public function printAngsuran(int $kreditId, int $jadwalId)
    {
        return $this->renderNotaAngsuran($kreditId, $jadwalId, true);
    }

    protected function renderNotaAngsuran(int $kreditId, int $jadwalId, bool $print)
    {
        $kredit = $this->kreditModel
            ->select('kredit.*, nasabah.nama AS nama_nasabah, produk_emas.nama_produk, produk_emas.kode_produk')
            ->join('nasabah', 'nasabah.id = kredit.nasabah_id', 'left')
            ->join('produk_emas', 'produk_emas.id = kredit.produk_emas_id', 'left')
            ->where('kredit.id', $kreditId)
            ->first();

        if (!$kredit) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Kredit tidak ditemukan.');
        }

        $jadwal = (new JadwalAngsuranModel())
            ->where('id', $jadwalId)
            ->where('kredit_id', $kreditId)
            ->where('status', 'dibayar')
            ->first();

        if (!$jadwal) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Angsuran belum dibayar atau tidak ditemukan.');
        }

        $db = \Config\Database::connect();
        $alloc = $db->table('pembayaran_alokasi pa')
            ->select('pa.*, py.kode_pembayaran, py.metode_pembayaran, py.tanggal_bayar')
            ->join('pembayaran_angsuran py', 'py.id = pa.pembayaran_angsuran_id')
            ->where('pa.jadwal_angsuran_id', $jadwalId)
            ->get()->getRowArray();

        $bukti = (new \App\Models\BuktiPembayaranModel())
            ->where('jadwal_angsuran_id', $jadwalId)
            ->where('status', 'terverifikasi')
            ->first();

        $kode_pembayaran   = $alloc['kode_pembayaran'] ?? 'BYR-MOCK-' . $jadwalId;
        $metode_pembayaran = $alloc['metode_pembayaran'] ?? 'transfer';
        $tanggal_bayar     = !empty($alloc['tanggal_bayar']) ? format_tanggal_id($alloc['tanggal_bayar']) : format_tanggal_id($jadwal['tanggal_dibayar'] ?: date('Y-m-d'));

        return view('public/akun/nota', [
            'pageTitle'          => 'Nota Angsuran ' . $kode_pembayaran,
            'pengaturan'         => (new \App\Models\PengaturanSistemModel())->getPengaturan(),
            'tipe'               => 'cicilan',
            'kredit'             => $kredit,
            'jadwal'             => $jadwal,
            'bukti'              => $bukti,
            'kode_pembayaran'    => $kode_pembayaran,
            'nominal_bayar'      => $jadwal['nominal_dibayar'],
            'nominal_tagihan'    => $jadwal['nominal_tagihan'],
            'tanggal_jatuh_tempo'=> format_tanggal_id($jadwal['tanggal_jatuh_tempo']),
            'tanggal_bayar'      => $tanggal_bayar,
            'metode_pembayaran'  => $metode_pembayaran,
            'angsuran_ke'        => $jadwal['angsuran_ke'],
            'print'              => $print,
            'backUrl'            => base_url('/admin/kredit/' . $kreditId),
        ]);
    }
}
