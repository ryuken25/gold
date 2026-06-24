<?php

namespace App\Controllers\Admin;

use App\Models\BuktiPembayaranModel;
use App\Models\JadwalAngsuranModel;
use App\Models\KreditModel;
use App\Models\NasabahModel;
use App\Models\PengajuanModel;
use App\Models\UserModel;
use App\Services\EmailNotificationService;
use App\Services\PaymentService;
use App\Services\WhatsAppTemplateService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;
use Throwable;

class PembayaranController extends BaseAdminController
{
    protected BuktiPembayaranModel $buktiModel;

    protected PaymentService $paymentService;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->buktiModel     = new BuktiPembayaranModel();
        $this->paymentService = new PaymentService();
    }

    public function index(): string
    {
        $db     = Database::connect();
        $status = (string) $this->request->getGet('status');
        $tipe   = (string) $this->request->getGet('tipe');
        $q      = trim((string) $this->request->getGet('q'));
        $dari   = (string) $this->request->getGet('dari');
        $sampai = (string) $this->request->getGet('sampai');

        $builder = $db->table('bukti_pembayaran bp')
            ->select('bp.*, u.nama as nama_user, pg.kode_pesanan, k.kode_kredit, kn.nama as nama_nasabah, j.angsuran_ke')
            ->join('users u', 'u.id = bp.user_id', 'left')
            ->join('pengajuan pg', 'pg.id = bp.pengajuan_id', 'left')
            ->join('kredit k', 'k.id = bp.kredit_id', 'left')
            ->join('nasabah kn', 'kn.id = k.nasabah_id', 'left')
            ->join('jadwal_angsuran j', 'j.id = bp.jadwal_angsuran_id', 'left');

        if (in_array($status, ['menunggu', 'terverifikasi', 'ditolak'], true)) {
            $builder->where('bp.status', $status);
        }
        if (in_array($tipe, ['cash', 'cicilan', 'dp'], true)) {
            $builder->where('bp.tipe', $tipe);
        }
        if ($q !== '') {
            $builder->groupStart()
                ->like('u.nama', $q)->orLike('kn.nama', $q)->orLike('bp.kode', $q)
                ->orLike('k.kode_kredit', $q)->orLike('pg.kode_pesanan', $q)
                ->groupEnd();
        }
        if ($dari !== '') {
            $builder->where('bp.created_at >=', $dari . ' 00:00:00');
        }
        if ($sampai !== '') {
            $builder->where('bp.created_at <=', $sampai . ' 23:59:59');
        }

        // Menunggu di atas, lalu terbaru.
        $builder->orderBy("FIELD(bp.status,'menunggu','terverifikasi','ditolak')", 'ASC', false)
            ->orderBy('bp.created_at', 'DESC');

        return $this->render('admin/pembayaran/index', [
            'pageTitle' => 'Verifikasi Pembayaran',
            'rows'      => $builder->get()->getResultArray(),
            'filter'    => compact('status', 'tipe', 'q', 'dari', 'sampai'),
        ]);
    }

    public function create(): string
    {
        $kreditId = (int) $this->request->getGet('kredit_id');
        $kredit = $kreditId ? $this->getKreditForPayment($kreditId) : null;

        return $this->render('admin/pembayaran/form', [
            'pageTitle' => 'Catat Pembayaran',
            'kredit'    => $kredit,
            'jadwal'    => $kredit ? $this->getJadwalForPayment($kreditId) : [],
        ]);
    }

    public function store()
    {
        $rules = [
            'kredit_id'          => 'required|integer',
            'jadwal_angsuran_id' => 'permit_empty|integer',
            'tanggal_bayar'      => 'required|valid_date',
            'nominal_bayar'      => 'required|integer|greater_than[0]',
            'metode_pembayaran'  => 'required|in_list[transfer,tunai,qris]',
            'keterangan'         => 'permit_empty|max_length[500]',
        ];

        if (!$this->validate($rules)) {
            return $this->respondFail('Validasi gagal: ' . implode(', ', $this->validator->getErrors()), 422, $this->validator->getErrors());
        }

        try {
            $result = $this->paymentService->record([
                'kredit_id'          => (int) $this->request->getPost('kredit_id'),
                'jadwal_angsuran_id' => (int) ($this->request->getPost('jadwal_angsuran_id') ?: 0),
                'tanggal_bayar'      => $this->request->getPost('tanggal_bayar'),
                'nominal_bayar'      => (int) $this->request->getPost('nominal_bayar'),
                'metode_pembayaran'  => $this->request->getPost('metode_pembayaran'),
                'keterangan'         => $this->request->getPost('keterangan'),
            ], (int) current_admin()['id']);

            $this->kirimNotifPembayaran($result['payment'], $result);

            $payment = $result['payment'] ?? [];
            $credit  = $result['credit'] ?? [];

            return $this->respondOk(
                'Pembayaran berhasil dicatat. Kode: ' . ($payment['kode_pembayaran'] ?? '-'),
                '/admin/kredit/' . ($credit['id'] ?? (int) $this->request->getPost('kredit_id'))
            );
        } catch (\Exception $e) {
            return $this->respondFail('Gagal mencatat pembayaran: ' . $e->getMessage(), 400);
        }
    }

    public function verifikasi(int $id)
    {
        $bukti = $this->buktiModel->find($id);
        if (!$bukti) {
            throw PageNotFoundException::forPageNotFound('Bukti pembayaran tidak ditemukan.');
        }
        if ($bukti['status'] !== 'menunggu') {
            return $this->respondFail('Bukti ini sudah diproses.', 409);
        }

        $db = Database::connect();
        $db->transStart();

        try {
            $result = null;
            if ($bukti['tipe'] === 'cicilan') {
                $result = $this->paymentService->record([
                    'kredit_id'          => $bukti['kredit_id'],
                    'jadwal_angsuran_id' => $bukti['jadwal_angsuran_id'],
                    'tanggal_bayar'      => date('Y-m-d'),
                    'nominal_bayar'      => $bukti['nominal'],
                    'metode_pembayaran'  => 'transfer',
                    'keterangan'         => 'Verifikasi bukti ' . $bukti['kode'],
                    'bukti_pembayaran_id'=> $bukti['id'],
                ], (int) current_admin()['id']);
            } elseif ($bukti['tipe'] === 'dp') {
                if ($bukti['pengajuan_id']) {
                    (new PengajuanModel())->update($bukti['pengajuan_id'], [
                        'pembayaran_status' => 'terverifikasi',
                    ]);
                    
                    // Update DP status on kredit table
                    $kModel = new \App\Models\KreditModel();
                    $kreditRecord = $kModel->where('pengajuan_id', $bukti['pengajuan_id'])->first();
                    if ($kreditRecord) {
                        $kModel->update($kreditRecord['id'], [
                            'dp_status' => 'terverifikasi',
                            'dp_verified_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            } else {
                if ($bukti['pengajuan_id']) {
                    (new PengajuanModel())->update($bukti['pengajuan_id'], [
                        'pembayaran_status' => 'terverifikasi',
                    ]);

                    // Trigger auto-completion if order status is already diterima
                    try {
                        $workflow = new \App\Services\PengajuanWorkflowService();
                        $workflow->autoCompleteIfEligible((int) $bukti['pengajuan_id']);
                    } catch (\Throwable $e) {
                        log_message('error', 'PembayaranController cash verifikasi auto-complete failed: ' . $e->getMessage());
                    }
                }
            }

            $this->buktiModel->update($id, [
                'status'            => 'terverifikasi',
                'diverifikasi_oleh' => current_admin()['id'] ?? null,
                'diverifikasi_pada' => date('Y-m-d H:i:s'),
            ]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database verifikasi gagal.');
            }
        } catch (Throwable $e) {
            $db->transRollback();
            return $this->respondFail('Gagal memverifikasi: ' . $e->getMessage(), 400);
        }

        // Kirim email notifikasi setelah pembayaran terverifikasi
        $buktiUpdated = $this->buktiModel->find($id);
        $this->kirimNotifPembayaran($buktiUpdated, $result);

        return $this->respondOk('Pembayaran terverifikasi & notifikasi dikirim.', '/admin/pembayaran');
    }

    public function tolak(int $id)
    {
        $bukti = $this->buktiModel->find($id);
        if (!$bukti) {
            throw PageNotFoundException::forPageNotFound('Bukti pembayaran tidak ditemukan.');
        }
        if ($bukti['status'] !== 'menunggu') {
            return $this->respondFail('Bukti ini sudah diproses.', 409);
        }
        if (!$this->validate([
            'catatan_admin' => 'required|min_length[5]|max_length[1000]',
        ])) {
            return $this->respondFail(
                'Alasan penolakan minimal 5 karakter.',
                422,
                $this->validator->getErrors()
            );
        }

        $catatan = trim((string) $this->request->getPost('catatan_admin'));

        $this->buktiModel->update($id, [
            'status'            => 'ditolak',
            'catatan_admin'     => $catatan,
            'diverifikasi_oleh' => current_admin()['id'] ?? null,
            'diverifikasi_pada' => date('Y-m-d H:i:s'),
        ]);

        if (in_array($bukti['tipe'], ['cash', 'dp'], true) && $bukti['pengajuan_id']) {
            (new PengajuanModel())->update($bukti['pengajuan_id'], ['pembayaran_status' => 'belum']);
        }
        // Tidak perlu kirim email jika ditolak, atau bisa tambahkan logic email_rejected
        return $this->respondOk('Bukti pembayaran ditolak.', '/admin/pembayaran');
    }

    public function bukti(int $id): ResponseInterface
    {
        $bukti = $this->buktiModel->find($id);
        if (!$bukti || empty($bukti['file_path'])) {
            throw PageNotFoundException::forPageNotFound('Bukti tidak ditemukan.');
        }

        $path = WRITEPATH . 'uploads/bukti/' . basename((string) $bukti['file_path']);
        if (!is_file($path)) {
            throw PageNotFoundException::forPageNotFound('File bukti tidak ada di server.');
        }

        return $this->response
            ->setHeader('Content-Type', mime_content_type($path) ?: 'application/octet-stream')
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($path) . '"')
            ->setBody((string) file_get_contents($path));
    }

    protected function getKreditForPayment(int $kreditId): ?array
    {
        $db = Database::connect();
        return $db->table('kredit k')
            ->select('k.*, n.nama as nama_nasabah, p.nama_produk')
            ->join('nasabah n', 'n.id = k.nasabah_id')
            ->join('produk_emas p', 'p.id = k.produk_emas_id')
            ->where('k.id', $kreditId)
            ->get()->getRowArray();
    }

    protected function getJadwalForPayment(int $kreditId): array
    {
        return (new JadwalAngsuranModel())
            ->where('kredit_id', $kreditId)
            ->where('status !=', 'dibayar')
            ->orderBy('angsuran_ke', 'ASC')
            ->findAll();
    }

    /**
     * Kirim email notifikasi setelah pembayaran terverifikasi.
     */
    protected function kirimNotifPembayaran(array $bukti, ?array $recordResult = null): void
    {
        $isBuktiTable = isset($bukti['tipe']); // bukti_pembayaran has 'tipe'
        
        $kreditModel = new KreditModel();
        $userModel = new UserModel();
        
        $userId = $isBuktiTable ? (int) ($bukti['user_id'] ?? 0) : 0;
        $kreditId = (int) ($bukti['kredit_id'] ?? 0);
        
        $kredit = null;
        if ($kreditId > 0) {
            $kredit = $kreditModel->find($kreditId);
            if ($kredit && !$userId) {
                $nasabah = (new \App\Models\NasabahModel())->find($kredit['nasabah_id']);
                if ($nasabah) {
                    $userId = (int) $nasabah['user_id'];
                }
            }
        }
        
        $user = $userId > 0 ? $userModel->find($userId) : null;
        
        $payload = [
            'user_id'    => $userId,
            'nama'       => $user['nama'] ?? 'Pelanggan',
            'kode'       => $isBuktiTable ? $bukti['kode'] : ($bukti['kode_pembayaran'] ?? '-'),
            'tipe'       => $isBuktiTable ? $bukti['tipe'] : 'cicilan',
            'nominal'    => $isBuktiTable ? $bukti['nominal'] : ($bukti['nominal_bayar'] ?? 0),
            'related_id' => (int) (($bukti['pengajuan_id'] ?? 0) ?: $kreditId),
        ];

        if ($payload['tipe'] === 'cicilan') {
            $jadwalModel = new JadwalAngsuranModel();
            
            $allocations = [];
            if ($recordResult && !empty($recordResult['allocations'])) {
                $allocations = $recordResult['allocations'];
            } elseif (!$isBuktiTable && !empty($bukti['id'])) {
                $allocations = (new \App\Models\PembayaranAlokasiModel())
                    ->where('pembayaran_angsuran_id', $bukti['id'])
                    ->findAll();
                foreach ($allocations as &$alloc) {
                    $sch = $jadwalModel->find($alloc['jadwal_angsuran_id']);
                    if ($sch) {
                        $alloc['angsuran_ke'] = $sch['angsuran_ke'];
                        $alloc['tanggal_jatuh_tempo'] = $sch['tanggal_jatuh_tempo'];
                        $alloc['nominal_tagihan'] = $sch['nominal_tagihan'];
                        $alloc['nominal_dibayar'] = $sch['nominal_dibayar'];
                        $alloc['status'] = $sch['status'];
                    }
                }
                unset($alloc);
            } elseif ($isBuktiTable && !empty($bukti['id'])) {
                $pembayaran = (new \App\Models\PembayaranAngsuranModel())
                    ->where('bukti_pembayaran_id', $bukti['id'])
                    ->first();
                if ($pembayaran) {
                    $allocations = (new \App\Models\PembayaranAlokasiModel())
                        ->where('pembayaran_angsuran_id', $pembayaran['id'])
                        ->findAll();
                    foreach ($allocations as &$alloc) {
                        $sch = $jadwalModel->find($alloc['jadwal_angsuran_id']);
                        if ($sch) {
                            $alloc['angsuran_ke'] = $sch['angsuran_ke'];
                            $alloc['tanggal_jatuh_tempo'] = $sch['tanggal_jatuh_tempo'];
                            $alloc['nominal_tagihan'] = $sch['nominal_tagihan'];
                            $alloc['nominal_dibayar'] = $sch['nominal_dibayar'];
                            $alloc['status'] = $sch['status'];
                        }
                    }
                    unset($alloc);
                }
            }

            $jadwalId = (int) ($bukti['jadwal_angsuran_id'] ?? 0);
            $jadwal = $jadwalId > 0 ? $jadwalModel->find($jadwalId) : null;
            
            if (!$jadwal && !empty($allocations)) {
                $jadwal = $jadwalModel->find($allocations[0]['jadwal_angsuran_id']);
            }

            $payload += [
                'kode_kredit'         => $kredit['kode_kredit'] ?? '-',
                'angsuran_ke'         => $jadwal['angsuran_ke'] ?? null,
                'periode_angsuran'    => $kredit['periode_angsuran'] ?? 'bulanan',
                'tanggal_jatuh_tempo' => $jadwal['tanggal_jatuh_tempo'] ?? null,
                'tanggal_bayar'       => $isBuktiTable ? date('Y-m-d') : ($bukti['tanggal_bayar'] ?? date('Y-m-d')),
                'diverifikasi_pada'   => $isBuktiTable ? ($bukti['diverifikasi_pada'] ?? date('Y-m-d H:i:s')) : ($bukti['created_at'] ?? date('Y-m-d H:i:s')),
                'total_terbayar'      => $kredit['total_terbayar'] ?? 0,
                'sisa_piutang'        => $kredit['sisa_piutang'] ?? 0,
                'status_kredit'       => $kredit['status'] ?? 'aktif',
                'allocations'         => $allocations,
            ];
        } elseif (($payload['tipe'] ?? '') === 'dp') {
            $pengajuan = $bukti['pengajuan_id'] ? (new PengajuanModel())->find($bukti['pengajuan_id']) : null;
            $kredit = $bukti['pengajuan_id'] ? ($kreditModel->where('pengajuan_id', $bukti['pengajuan_id'])->first()) : null;
            $produk = $pengajuan ? ((new \App\Models\ProdukEmasModel())->find($pengajuan['produk_emas_id'])) : null;
            
            $dpPayload = [
                'user_id'              => $userId,
                'nama'                 => $user['nama'] ?? 'Pelanggan',
                'nama_pelanggan'       => $user['nama'] ?? 'Pelanggan',
                'kode_kredit'          => $kredit['kode_kredit'] ?? '-',
                'kode_pesanan'         => $pengajuan['kode_pesanan'] ?? '-',
                'produk'               => $produk ? trim($produk['nama_produk'] . (!empty($produk['kode_produk']) ? ' (' . $produk['kode_produk'] . ')' : '')) : '-',
                'nominal_dp'           => $bukti['nominal'],
                'tanggal_bayar_dp'     => format_tanggal_id($bukti['created_at']),
                'bulan_bayar_dp'       => format_tanggal_id($bukti['created_at'], 'F Y'),
                'total_harga_kredit'   => $kredit['total_harga_kredit'] ?? 0,
                'sisa_piutang'         => $kredit['sisa_piutang'] ?? 0,
                'status_verifikasi_dp' => 'Terverifikasi',
                'kredit_id'            => $kredit['id'] ?? 0,
            ];

            try {
                (new EmailNotificationService())->kirimDpTerverifikasi($dpPayload);
            } catch (Throwable $e) {
                log_message('error', 'Email dp_terverifikasi gagal: ' . $e->getMessage());
            }
            return;
        } else {
            $pengajuan = $bukti['pengajuan_id'] ? (new PengajuanModel())->find($bukti['pengajuan_id']) : null;
            $payload  += ['kode_pesanan' => $pengajuan['kode_pesanan'] ?? '-'];
        }

        try {
            (new EmailNotificationService())->kirimPembayaranTerverifikasi($payload);
        } catch (Throwable $e) {
            log_message('error', 'Email pembayaran_terverifikasi gagal: ' . $e->getMessage());
        }
    }

    /**
     * @deprecated WhatsApp notification is now automated via email/other channels. This route is unrouted.
     */
    public function wa(int $id)
    {
        throw new \RuntimeException('Method/Route wa() is deprecated and unrouted.');
    }
}
