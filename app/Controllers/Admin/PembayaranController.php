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
use App\Services\WhatsAppGatewayService;
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
        if (in_array($tipe, ['cash', 'cicilan'], true)) {
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

    public function verifikasi(int $id)
    {
        $bukti = $this->buktiModel->find($id);
        if (!$bukti) {
            throw PageNotFoundException::forPageNotFound('Bukti pembayaran tidak ditemukan.');
        }
        if ($bukti['status'] !== 'menunggu') {
            return redirect()->to('/admin/pembayaran')->with('error', 'Bukti ini sudah diproses.');
        }

        try {
            if ($bukti['tipe'] === 'cicilan') {
                // Nominal sudah fix (= tagihan angsuran). Auto-catat pembayaran.
                $this->paymentService->record([
                    'kredit_id'          => $bukti['kredit_id'],
                    'jadwal_angsuran_id' => $bukti['jadwal_angsuran_id'],
                    'tanggal_bayar'      => date('Y-m-d'),
                    'nominal_bayar'      => $bukti['nominal'],
                    'metode_pembayaran'  => 'transfer',
                    'keterangan'         => 'Verifikasi bukti ' . $bukti['kode'],
                ], (int) current_admin()['id']);
            } else {
                // Cash: tandai pengajuan selesai.
                if ($bukti['pengajuan_id']) {
                    (new PengajuanModel())->update($bukti['pengajuan_id'], [
                        'pembayaran_status' => 'terverifikasi',
                        'status'            => 'selesai',
                    ]);
                }
            }

            $this->buktiModel->update($id, [
                'status'            => 'terverifikasi',
                'diverifikasi_oleh' => current_admin()['id'] ?? null,
                'diverifikasi_pada' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            return redirect()->to('/admin/pembayaran')->with('error', 'Gagal memverifikasi: ' . $e->getMessage());
        }

        $this->kirimNotifPembayaran($bukti);

        return redirect()->to('/admin/pembayaran')->with('success', 'Pembayaran terverifikasi & notifikasi dikirim.');
    }

    public function tolak(int $id)
    {
        $bukti = $this->buktiModel->find($id);
        if (!$bukti) {
            throw PageNotFoundException::forPageNotFound('Bukti pembayaran tidak ditemukan.');
        }
        if ($bukti['status'] !== 'menunggu') {
            return redirect()->to('/admin/pembayaran')->with('error', 'Bukti ini sudah diproses.');
        }
        if (!$this->validate(['catatan_admin' => 'required|max_length[1000]'])) {
            return redirect()->to('/admin/pembayaran')->with('error', 'Alasan penolakan wajib diisi.');
        }

        $this->buktiModel->update($id, [
            'status'            => 'ditolak',
            'catatan_admin'     => $this->request->getPost('catatan_admin'),
            'diverifikasi_oleh' => current_admin()['id'] ?? null,
            'diverifikasi_pada' => date('Y-m-d H:i:s'),
        ]);

        if ($bukti['tipe'] === 'cash' && $bukti['pengajuan_id']) {
            (new PengajuanModel())->update($bukti['pengajuan_id'], ['pembayaran_status' => 'belum']);
        }

        return redirect()->to('/admin/pembayaran')->with('success', 'Bukti pembayaran ditolak.');
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

    /**
     * Kirim email + WA backup setelah pembayaran terverifikasi.
     */
    protected function kirimNotifPembayaran(array $bukti): void
    {
        $user  = (new UserModel())->find($bukti['user_id']);
        $nomor = $user['no_telepon'] ?? '';

        $payload = [
            'user_id'    => (int) $bukti['user_id'],
            'nama'       => $user['nama'] ?? 'Pelanggan',
            'kode'       => $bukti['kode'],
            'tipe'       => $bukti['tipe'],
            'nominal'    => $bukti['nominal'],
            'related_id' => (int) ($bukti['pengajuan_id'] ?: $bukti['kredit_id']),
        ];

        if ($bukti['tipe'] === 'cicilan') {
            $kredit = (new KreditModel())->find($bukti['kredit_id']);
            $jadwal = $bukti['jadwal_angsuran_id'] ? (new JadwalAngsuranModel())->find($bukti['jadwal_angsuran_id']) : null;
            $payload += [
                'kode_kredit'    => $kredit['kode_kredit'] ?? '-',
                'angsuran_ke'    => $jadwal['angsuran_ke'] ?? null,
                'total_terbayar' => $kredit['total_terbayar'] ?? 0,
                'sisa_piutang'   => $kredit['sisa_piutang'] ?? 0,
                'status_kredit'  => $kredit['status'] ?? 'aktif',
            ];
            if ($nomor === '' && $kredit) {
                $nasabah = (new NasabahModel())->find($kredit['nasabah_id']);
                $nomor   = $nasabah['no_telepon'] ?? '';
            }
            $ringkas = 'Pembayaran angsuran ' . ($kredit['kode_kredit'] ?? '') . ' terverifikasi. Sisa piutang: '
                . format_rupiah($kredit['sisa_piutang'] ?? 0) . '.'
                . (($kredit['status'] ?? '') === 'lunas' ? ' Kredit LUNAS, terima kasih!' : '');
        } else {
            $pengajuan = $bukti['pengajuan_id'] ? (new PengajuanModel())->find($bukti['pengajuan_id']) : null;
            $payload  += ['kode_pesanan' => $pengajuan['kode_pesanan'] ?? '-'];
            if ($nomor === '' && $pengajuan) {
                $nomor = $pengajuan['no_telepon'] ?? '';
            }
            $ringkas = 'Pembayaran pesanan ' . ($pengajuan['kode_pesanan'] ?? '') . ' terverifikasi. Pesanan selesai. Terima kasih!';
        }

        try {
            (new EmailNotificationService())->kirimPembayaranTerverifikasi($payload);
        } catch (Throwable $e) {
            log_message('error', 'Email pembayaran_terverifikasi gagal: ' . $e->getMessage());
        }
        try {
            (new WhatsAppGatewayService())->send((string) $nomor, $ringkas);
        } catch (Throwable $e) {
            log_message('error', 'WA backup pembayaran gagal: ' . $e->getMessage());
        }
    }
}
