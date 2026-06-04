<?php

namespace App\Controllers\Admin;

use App\Models\JadwalAngsuranModel;
use App\Models\KreditModel;
use App\Models\NasabahModel;
use App\Models\PembayaranAngsuranModel;
use App\Models\ProdukEmasModel;
use App\Services\CreditTransactionService;
use App\Services\WhatsAppTemplateService;
use Config\Database;
use Throwable;

class KreditController extends BaseAdminController
{
    protected KreditModel $kreditModel;

    protected CreditTransactionService $creditService;

    protected WhatsAppTemplateService $whatsAppService;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->kreditModel = new KreditModel();
        $this->creditService = new CreditTransactionService();
        $this->whatsAppService = new WhatsAppTemplateService();
    }

    public function index(): string
    {
        $db = Database::connect();
        $status = (string) $this->request->getGet('status');
        $today = date('Y-m-d');

        $builder = $db->table('kredit k')
            ->select('k.*, n.nama as nama_nasabah, p.nama_produk')
            ->join('nasabah n', 'n.id = k.nasabah_id')
            ->join('produk_emas p', 'p.id = k.produk_emas_id')
            ->orderBy('k.created_at', 'DESC');

        if (in_array($status, ['aktif', 'lunas', 'dibatalkan'], true)) {
            $builder->where('k.status', $status);
        } elseif ($status === 'jatuh_tempo') {
            $builder->join('jadwal_angsuran j', 'j.kredit_id = k.id')
                ->where('j.tanggal_jatuh_tempo', $today)
                ->whereNotIn('j.status', ['dibayar'])
                ->groupBy('k.id');
        } elseif ($status === 'terlambat') {
            $builder->join('jadwal_angsuran j', 'j.kredit_id = k.id')
                ->where('j.tanggal_jatuh_tempo <', $today)
                ->whereNotIn('j.status', ['dibayar'])
                ->groupBy('k.id');
        }

        return $this->render('admin/kredit/index', [
            'pageTitle' => 'Transaksi Kredit & Piutang',
            'kredit' => $builder->get()->getResultArray(),
            'status' => $status,
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

        return $this->render('admin/kredit/show', [
            'pageTitle' => 'Detail Kredit',
            'kredit' => $kredit,
            'jadwal' => $jadwal,
            'payments' => $payments,
            'jadwalPertama' => $jadwal[0]['tanggal_jatuh_tempo'] ?? null,
        ]);
    }

    public function cancel(int $id)
    {
        try {
            $this->creditService->cancel($id);
            return redirect()->to('/admin/kredit/' . $id)->with('success', 'Kredit berhasil dibatalkan.');
        } catch (Throwable $e) {
            return redirect()->to('/admin/kredit/' . $id)->with('error', $e->getMessage());
        }
    }

    public function waInfo(int $id)
    {
        $payload = $this->waPayload($id);
        $result = $this->whatsAppService->createInfoTransaksiLink($payload);

        return redirect()->to($result['wa_url']);
    }

    public function waPengingat(int $id, int $jadwalId)
    {
        $payload = $this->waPayload($id);
        $jadwal = (new JadwalAngsuranModel())->find($jadwalId);
        if (!$jadwal) {
            return redirect()->to('/admin/kredit/' . $id)->with('error', 'Jadwal angsuran tidak ditemukan.');
        }

        $result = $this->whatsAppService->createPengingatLink(array_merge($payload, [
            'angsuran_ke' => $jadwal['angsuran_ke'],
            'tanggal_jatuh_tempo' => $jadwal['tanggal_jatuh_tempo'],
            'nominal_tagihan' => $jadwal['nominal_tagihan'],
            'jadwal_id' => $jadwal['id'],
            'created_by' => current_admin()['id'],
        ]));

        return redirect()->to($result['wa_url']);
    }

    public function waLunas(int $id)
    {
        $payload = $this->waPayload($id);
        $result = $this->whatsAppService->createKreditLunasLink(array_merge($payload, [
            'created_by' => current_admin()['id'],
        ]));

        return redirect()->to($result['wa_url']);
    }

    protected function waPayload(int $id): array
    {
        $kredit = Database::connect()->table('kredit k')
            ->select('k.*, n.nama as nama_nasabah, n.no_telepon, p.nama_produk, p.kadar, p.berat_gram')
            ->join('nasabah n', 'n.id = k.nasabah_id')
            ->join('produk_emas p', 'p.id = k.produk_emas_id')
            ->where('k.id', $id)
            ->get()->getRowArray();

        $jadwal = (new JadwalAngsuranModel())->where('kredit_id', $id)->orderBy('angsuran_ke', 'ASC')->first();

        return array_merge($kredit ?? [], [
            'periode_label' => periode_label($kredit['periode_angsuran'] ?? 'bulanan'),
            'tanggal_jatuh_tempo_pertama' => $jadwal['tanggal_jatuh_tempo'] ?? null,
            'created_by' => current_admin()['id'],
        ]);
    }
}
