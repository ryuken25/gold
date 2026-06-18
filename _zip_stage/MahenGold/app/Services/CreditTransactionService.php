<?php

namespace App\Services;

use App\Models\JadwalAngsuranModel;
use App\Models\KreditModel;
use App\Models\NasabahModel;
use App\Models\ProdukEmasModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use RuntimeException;

class CreditTransactionService
{
    protected BaseConnection $db;

    public function __construct(
        protected ?KreditModel $kreditModel = null,
        protected ?JadwalAngsuranModel $jadwalModel = null,
        protected ?ProdukEmasModel $produkModel = null,
        protected ?NasabahModel $nasabahModel = null,
        protected ?CreditCalculatorService $calculator = null,
    ) {
        $this->kreditModel ??= new KreditModel();
        $this->jadwalModel ??= new JadwalAngsuranModel();
        $this->produkModel ??= new ProdukEmasModel();
        $this->nasabahModel ??= new NasabahModel();
        $this->calculator ??= new CreditCalculatorService();
        $this->db = Database::connect();
    }

    public function preview(array $input, float $marginDefault = 10.00): array
    {
        $produk = $this->produkModel->find((int) ($input['produk_emas_id'] ?? $input['produk_id'] ?? 0));

        if (!$produk) {
            throw new RuntimeException('Produk emas tidak ditemukan.');
        }

        return $this->calculator->calculate(
            $produk['harga_pokok'],
            $input['margin_persen'] ?? $marginDefault,
            (int) ($input['tenor_bulan'] ?? 12),
            (string) ($input['periode_angsuran'] ?? 'bulanan'),
            $input['uang_muka'] ?? 0
        );
    }

    public function create(array $input, int $adminId, float $marginDefault = 10.00): array
    {
        $produk = $this->produkModel->find((int) $input['produk_emas_id']);
        $nasabah = $this->nasabahModel->find((int) $input['nasabah_id']);

        if (!$produk || $produk['status'] !== 'aktif') {
            throw new RuntimeException('Produk emas tidak tersedia.');
        }

        if ((int) $produk['stok'] < 1) {
            throw new RuntimeException('Stok produk emas tidak mencukupi.');
        }

        if (!$nasabah) {
            throw new RuntimeException('Nasabah tidak ditemukan.');
        }

        $kalkulasi = $this->calculator->calculate(
            $produk['harga_pokok'],
            $input['margin_persen'] ?? $marginDefault,
            (int) $input['tenor_bulan'],
            (string) $input['periode_angsuran'],
            $input['uang_muka'] ?? 0
        );

        $this->db->transStart();

        $kreditId = $this->kreditModel->insert([
            'kode_kredit' => 'PENDING',
            'nasabah_id' => $nasabah['id'],
            'produk_emas_id' => $produk['id'],
            'tanggal_kredit' => $input['tanggal_kredit'],
            'harga_pokok_snapshot' => $kalkulasi['harga_pokok'],
            'margin_persen' => $kalkulasi['margin_persen'],
            'margin_nominal' => $kalkulasi['margin_nominal'],
            'total_harga_kredit' => $kalkulasi['total_harga_kredit'],
            'uang_muka' => $kalkulasi['uang_muka'],
            'sisa_pokok_kredit' => $kalkulasi['sisa_pokok'],
            'tenor_bulan' => $kalkulasi['tenor_bulan'],
            'periode_angsuran' => $kalkulasi['periode_angsuran'],
            'jumlah_periode' => $kalkulasi['jumlah_periode'],
            'nominal_angsuran' => $kalkulasi['nominal_angsuran'],
            'total_terbayar' => 0,
            'sisa_piutang' => $kalkulasi['sisa_pokok'],
            'status' => 'aktif',
            'catatan' => $input['catatan'] ?? null,
        ], true);

        $kodeKredit = generate_kode('KRD', $kreditId);
        $this->kreditModel->update($kreditId, ['kode_kredit' => $kodeKredit]);

        $jadwal = $this->calculator->generateSchedule($input['tanggal_jatuh_tempo_pertama'], $kalkulasi);
        foreach ($jadwal as &$item) {
            $item['kredit_id'] = $kreditId;
        }
        unset($item);
        $this->jadwalModel->insertBatch($jadwal);

        $this->produkModel->update($produk['id'], ['stok' => max(0, ((int) $produk['stok']) - 1)]);

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            throw new RuntimeException('Gagal menyimpan transaksi kredit.');
        }

        return [
            'kredit' => $this->kreditModel->find($kreditId),
            'produk' => $produk,
            'nasabah' => $nasabah,
            'jadwal_pertama' => $jadwal[0]['tanggal_jatuh_tempo'] ?? null,
            'created_by' => $adminId,
        ];
    }

    /**
     * Buat kredit + jadwal angsuran otomatis dari pengajuan yang disetujui.
     * Idempotent: tidak membuat ganda bila pengajuan sudah punya kredit.
     * Auto-create nasabah dari data user/pengajuan bila belum ada.
     */
    public function createFromPengajuan(array $pengajuan, float $marginDefault = 10.00): array
    {
        if (($pengajuan['metode_pembayaran'] ?? '') !== 'kredit') {
            return [];
        }

        $existing = $this->kreditModel->where('pengajuan_id', $pengajuan['id'])->first();
        if ($existing) {
            return [
                'kredit' => $existing,
                'jadwal' => $this->jadwalModel->where('kredit_id', $existing['id'])->orderBy('angsuran_ke', 'ASC')->findAll(),
                'reused' => true,
            ];
        }

        $produk = $this->produkModel->find((int) $pengajuan['produk_emas_id']);
        if (!$produk) {
            throw new RuntimeException('Produk emas tidak ditemukan.');
        }

        // Pastikan nasabah untuk user ini ada (auto-create bila belum).
        $nasabah = $this->nasabahModel->where('user_id', $pengajuan['user_id'])->first();
        if (!$nasabah) {
            $nasabahId = $this->nasabahModel->insert([
                'kode_nasabah' => 'PENDING',
                'user_id'      => $pengajuan['user_id'],
                'nama'         => $pengajuan['nama'],
                'no_telepon'   => wa_number_normalize((string) ($pengajuan['no_telepon'] ?? '')),
                'alamat'       => $pengajuan['alamat'] ?? '-',
                'catatan'      => 'Auto dari pesanan ' . ($pengajuan['kode_pesanan'] ?? ''),
            ], true);
            $this->nasabahModel->update($nasabahId, ['kode_nasabah' => generate_kode('NSB', $nasabahId)]);
            $nasabah = $this->nasabahModel->find($nasabahId);
        }

        $tenor    = (int) ($pengajuan['tenor_bulan'] ?: 12);
        $periode  = (string) ($pengajuan['periode_angsuran'] ?: 'bulanan');
        $uangMuka = (int) ($pengajuan['uang_muka'] ?? 0);
        $kalkulasi = $this->calculator->calculate($produk['harga_pokok'], $marginDefault, $tenor, $periode, $uangMuka);

        $tanggalKredit     = date('Y-m-d');
        $jatuhTempoPertama = $periode === 'mingguan'
            ? date('Y-m-d', strtotime('+7 day'))
            : date('Y-m-d', strtotime('+1 month'));

        $this->db->transStart();

        $kreditId = $this->kreditModel->insert([
            'kode_kredit'          => 'PENDING',
            'pengajuan_id'         => $pengajuan['id'],
            'nasabah_id'           => $nasabah['id'],
            'produk_emas_id'       => $produk['id'],
            'tanggal_kredit'       => $tanggalKredit,
            'harga_pokok_snapshot' => $kalkulasi['harga_pokok'],
            'margin_persen'        => $kalkulasi['margin_persen'],
            'margin_nominal'       => $kalkulasi['margin_nominal'],
            'total_harga_kredit'   => $kalkulasi['total_harga_kredit'],
            'uang_muka'            => $kalkulasi['uang_muka'],
            'sisa_pokok_kredit'    => $kalkulasi['sisa_pokok'],
            'tenor_bulan'          => $kalkulasi['tenor_bulan'],
            'periode_angsuran'     => $kalkulasi['periode_angsuran'],
            'jumlah_periode'       => $kalkulasi['jumlah_periode'],
            'nominal_angsuran'     => $kalkulasi['nominal_angsuran'],
            'total_terbayar'       => 0,
            'sisa_piutang'         => $kalkulasi['sisa_pokok'],
            'status'               => 'aktif',
            'catatan'              => 'Auto dari pesanan ' . ($pengajuan['kode_pesanan'] ?? ''),
        ], true);

        $this->kreditModel->update($kreditId, ['kode_kredit' => generate_kode('KRD', $kreditId)]);

        $jadwal = $this->calculator->generateSchedule($jatuhTempoPertama, $kalkulasi);
        foreach ($jadwal as &$item) {
            $item['kredit_id'] = $kreditId;
        }
        unset($item);
        $this->jadwalModel->insertBatch($jadwal);

        // Kurangi stok; toko fisik -> tetap lanjut walau stok 0 (log warning).
        $stok = (int) $produk['stok'];
        if ($stok < 1) {
            log_message('warning', 'Stok produk ' . $produk['id'] . ' habis saat auto-create kredit dari pengajuan ' . $pengajuan['id']);
        } else {
            $this->produkModel->update($produk['id'], ['stok' => $stok - 1]);
        }

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            $error = $this->db->error();
            $msg = is_array($error) ? ($error['message'] ?? 'unknown') : (string) $error;
            log_message('error', 'Transaction failed createFromPengajuan: ' . $msg);
            throw new RuntimeException('Gagal membuat kredit dari pesanan: ' . $msg);
        }

        return [
            'kredit'  => $this->kreditModel->find($kreditId),
            'nasabah' => $nasabah,
            'jadwal'  => $jadwal,
            'reused'  => false,
        ];
    }

    public function cancel(int $id): void
    {
        $kredit = $this->kreditModel->find($id);
        if (!$kredit || $kredit['status'] !== 'aktif') {
            throw new RuntimeException('Kredit tidak dapat dibatalkan.');
        }

        $produk = $this->produkModel->find($kredit['produk_emas_id']);

        $this->db->transStart();
        $this->kreditModel->update($id, ['status' => 'dibatalkan']);
        if ($produk) {
            $this->produkModel->update($produk['id'], ['stok' => ((int) $produk['stok']) + 1]);
        }
        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            throw new RuntimeException('Gagal membatalkan kredit.');
        }
    }
}
