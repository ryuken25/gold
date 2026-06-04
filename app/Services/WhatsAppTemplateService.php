<?php

namespace App\Services;

use App\Models\PengaturanSistemModel;
use App\Models\WhatsAppLogModel;

class WhatsAppTemplateService
{
    public function __construct(
        protected ?WhatsAppLogModel $logModel = null,
        protected ?PengaturanSistemModel $pengaturanModel = null,
    ) {
        $this->logModel ??= new WhatsAppLogModel();
        $this->pengaturanModel ??= new PengaturanSistemModel();
    }

    /**
     * Susun isi pesan pengajuan kredit (tanpa menyimpan log).
     */
    public function buildPengajuanMessage(array $payload): string
    {
        return trim(implode("\n", [
            'Halo Admin MahenGold, saya ingin mengajukan kredit emas.',
            '',
            'DATA PELANGGAN',
            'Nama: ' . ($payload['nama'] ?? '-'),
            'Alamat: ' . ($payload['alamat'] ?? '-'),
            'Nomor WhatsApp: mengikuti nomor pengirim chat ini',
            '',
            'DETAIL PRODUK',
            'Kode Produk: ' . ($payload['kode_produk'] ?? '-'),
            'Produk: ' . ($payload['nama_produk'] ?? '-'),
            'Jenis/Kadar: ' . ($payload['jenis_emas'] ?? '-') . ' / ' . ($payload['kadar'] ?? '-'),
            'Berat: ' . format_angka($payload['berat_gram'] ?? 0, 2) . ' gram',
            'Harga Pokok: ' . format_rupiah($payload['harga_pokok'] ?? 0),
            '',
            'SIMULASI KREDIT FLAT RATE',
            'Margin: ' . format_angka($payload['margin_persen'] ?? 0, 0) . '%',
            'Total Harga Kredit: ' . format_rupiah($payload['total_harga_kredit'] ?? 0),
            'Tenor: ' . ($payload['tenor_bulan'] ?? '-') . ' bulan',
            'Jenis Angsuran: ' . ucfirst((string) ($payload['periode_angsuran'] ?? '-')),
            'Jumlah Periode: ' . ($payload['jumlah_periode'] ?? '-'),
            'Estimasi Angsuran: ' . format_rupiah($payload['nominal_angsuran'] ?? 0) . ' / ' . ($payload['periode_label'] ?? 'bulan'),
            '',
            'Saya ingin bertanya lebih lanjut untuk proses pengajuan kredit emas ini.',
        ]));
    }

    /**
     * Susun isi pesan pembelian tunai (tanpa menyimpan log).
     */
    public function buildPembelianCashMessage(array $payload): string
    {
        return trim(implode("\n", [
            'Halo Admin MahenGold, saya ingin membeli emas secara tunai.',
            '',
            'DATA PEMBELI',
            'Nama: ' . ($payload['nama'] ?? '-'),
            'Alamat: ' . ($payload['alamat'] ?? '-'),
            'Nomor WhatsApp: mengikuti nomor pengirim chat ini',
            '',
            'DETAIL PRODUK',
            'Kode Produk: ' . ($payload['kode_produk'] ?? '-'),
            'Produk: ' . ($payload['nama_produk'] ?? '-'),
            'Jenis/Kadar: ' . ($payload['jenis_emas'] ?? '-') . ' / ' . ($payload['kadar'] ?? '-'),
            'Berat: ' . format_angka($payload['berat_gram'] ?? 0, 2) . ' gram',
            'Harga Pokok: ' . format_rupiah($payload['harga_pokok'] ?? 0),
            '',
            'Pembayaran: Tunai / Cash',
            '',
            'Saya ingin menyelesaikan pembelian emas ini. Mohon konfirmasi ketersediaan dan proses selanjutnya.',
        ]));
    }

    /**
     * Bangun preview pesan + URL WhatsApp TANPA menulis ke whatsapp_logs.
     * Dipakai untuk pratinjau realtime di form (dipanggil tiap ketik).
     */
    public function previewLink(array $payload, string $metode = 'kredit'): array
    {
        $message = $metode === 'cash'
            ? $this->buildPembelianCashMessage($payload)
            : $this->buildPengajuanMessage($payload);

        return [
            'message' => $message,
            'wa_url'  => $this->buildWaUrl($this->getStoreWaNumber(), $message),
        ];
    }

    public function createPengajuanLink(array $payload): array
    {
        return $this->buildAndLog([
            'tipe' => 'pengajuan_kredit',
            'target' => 'admin',
            'tujuan_nomor' => $this->getStoreWaNumber(),
            'nama_tujuan' => 'Admin MahenGold',
            'pesan' => $this->buildPengajuanMessage($payload),
            'status' => 'dibuka',
            'related_type' => 'produk_emas',
            'related_id' => $payload['produk_id'] ?? null,
            'created_by' => null,
        ]);
    }

    public function createPembelianCashLink(array $payload): array
    {
        return $this->buildAndLog([
            'tipe'         => 'info_transaksi',
            'target'       => 'admin',
            'tujuan_nomor' => $this->getStoreWaNumber(),
            'nama_tujuan'  => 'Admin MahenGold',
            'pesan'        => $this->buildPembelianCashMessage($payload),
            'status'       => 'dibuka',
            'related_type' => 'produk_emas',
            'related_id'   => $payload['produk_id'] ?? null,
            'created_by'   => null,
        ]);
    }

    public function createInfoTransaksiLink(array $data): array
    {
        $message = trim(implode("\n", [
            'FAKTUR KREDIT EMAS MAHENGOLD',
            '',
            'Nomor Kredit:',
            $data['kode_kredit'],
            '',
            'Nasabah Yth:',
            $data['nama_nasabah'],
            '',
            'Tanggal Kredit:',
            format_tanggal($data['tanggal_kredit']),
            '',
            'DETAIL PRODUK',
            'Produk: ' . $data['nama_produk'],
            'Berat/Kadar: ' . format_angka($data['berat_gram'], 2) . ' gram / ' . $data['kadar'],
            'Harga Pokok: ' . format_rupiah($data['harga_pokok_snapshot']),
            '',
            'DETAIL KREDIT',
            'Margin Flat Rate: ' . format_angka($data['margin_persen'], 0) . '%',
            'Total Harga Kredit: ' . format_rupiah($data['total_harga_kredit']),
            'Tenor: ' . $data['tenor_bulan'] . ' bulan',
            'Periode: ' . ucfirst($data['periode_angsuran']),
            'Angsuran: ' . format_rupiah($data['nominal_angsuran']) . ' / ' . $data['periode_label'],
            'Jatuh Tempo Pertama: ' . format_tanggal($data['tanggal_jatuh_tempo_pertama']),
            '',
            'Pembayaran dilakukan di luar sistem sesuai arahan admin MahenGold.',
            'Terima kasih.',
        ]));

        return $this->buildAndLog([
            'tipe' => 'info_transaksi',
            'target' => 'pelanggan',
            'tujuan_nomor' => wa_number_normalize($data['no_telepon'] ?? ''),
            'nama_tujuan' => $data['nama_nasabah'],
            'pesan' => $message,
            'status' => 'dibuat',
            'related_type' => 'kredit',
            'related_id' => $data['id'],
            'created_by' => $data['created_by'] ?? null,
        ]);
    }

    public function createPengingatLink(array $data): array
    {
        $message = trim(implode("\n", [
            'PENGINGAT ANGSURAN MAHENGOLD',
            '',
            'Halo ' . $data['nama_nasabah'] . ',',
            '',
            'Angsuran kredit emas Anda akan/sudah jatuh tempo.',
            '',
            'Nomor Kredit: ' . $data['kode_kredit'],
            'Angsuran Ke: ' . $data['angsuran_ke'],
            'Jatuh Tempo: ' . format_tanggal($data['tanggal_jatuh_tempo']),
            'Nominal: ' . format_rupiah($data['nominal_tagihan']),
            'Sisa Piutang: ' . format_rupiah($data['sisa_piutang']),
            '',
            'Silakan lakukan pembayaran sesuai kesepakatan.',
            'Jika sudah melakukan pembayaran, mohon konfirmasi ke admin MahenGold.',
        ]));

        return $this->buildAndLog([
            'tipe' => 'pengingat_jatuh_tempo',
            'target' => 'pelanggan',
            'tujuan_nomor' => wa_number_normalize($data['no_telepon'] ?? ''),
            'nama_tujuan' => $data['nama_nasabah'],
            'pesan' => $message,
            'status' => 'dibuat',
            'related_type' => 'jadwal_angsuran',
            'related_id' => $data['jadwal_id'],
            'created_by' => $data['created_by'] ?? null,
        ]);
    }

    public function createPembayaranDiterimaLink(array $data): array
    {
        $message = trim(implode("\n", [
            'KONFIRMASI PEMBAYARAN MAHENGOLD',
            '',
            'Halo ' . $data['nama_nasabah'] . ',',
            '',
            'Pembayaran angsuran Anda telah dicatat oleh admin.',
            '',
            'Nomor Kredit: ' . $data['kode_kredit'],
            'Tanggal Bayar: ' . format_tanggal($data['tanggal_bayar']),
            'Nominal Bayar: ' . format_rupiah($data['nominal_bayar']),
            'Total Terbayar: ' . format_rupiah($data['total_terbayar']),
            'Sisa Piutang: ' . format_rupiah($data['sisa_piutang']),
            'Status Kredit: ' . strtoupper($data['status_kredit']),
            '',
            'Terima kasih.',
        ]));

        return $this->buildAndLog([
            'tipe' => 'pembayaran_diterima',
            'target' => 'pelanggan',
            'tujuan_nomor' => wa_number_normalize($data['no_telepon'] ?? ''),
            'nama_tujuan' => $data['nama_nasabah'],
            'pesan' => $message,
            'status' => 'dibuat',
            'related_type' => 'pembayaran_angsuran',
            'related_id' => $data['pembayaran_id'],
            'created_by' => $data['created_by'] ?? null,
        ]);
    }

    public function createKreditLunasLink(array $data): array
    {
        $message = trim(implode("\n", [
            'KREDIT EMAS LUNAS',
            '',
            'Halo ' . $data['nama_nasabah'] . ',',
            '',
            'Kredit emas Anda telah dinyatakan LUNAS.',
            '',
            'Nomor Kredit: ' . $data['kode_kredit'],
            'Produk: ' . $data['nama_produk'],
            'Total Harga Kredit: ' . format_rupiah($data['total_harga_kredit']),
            'Total Terbayar: ' . format_rupiah($data['total_terbayar']),
            '',
            'Terima kasih sudah mempercayai MahenGold.',
        ]));

        return $this->buildAndLog([
            'tipe' => 'kredit_lunas',
            'target' => 'pelanggan',
            'tujuan_nomor' => wa_number_normalize($data['no_telepon'] ?? ''),
            'nama_tujuan' => $data['nama_nasabah'],
            'pesan' => $message,
            'status' => 'dibuat',
            'related_type' => 'kredit',
            'related_id' => $data['id'],
            'created_by' => $data['created_by'] ?? null,
        ]);
    }

    public function buildWaUrl(string $nomor, string $pesan): string
    {
        return 'https://wa.me/' . wa_number_normalize($nomor) . '?text=' . rawurlencode($pesan);
    }

    public function getStoreWaNumber(): string
    {
        // Prioritas: nomor WhatsApp toko yang diatur admin di Pengaturan,
        // lalu fallback ke env WA_TARGET_NUMBER, lalu default.
        $setting = wa_number_normalize((string) ($this->pengaturanModel->getPengaturan()['nomor_whatsapp_toko'] ?? ''));
        if ($setting !== '') {
            return $setting;
        }

        $target = wa_number_normalize((string) env('WA_TARGET_NUMBER', ''));

        return $target !== '' ? $target : '6282146575233';
    }

    protected function buildAndLog(array $data): array
    {
        $number = $data['tujuan_nomor'] ?: $this->getStoreWaNumber();
        $waUrl = $this->buildWaUrl($number, $data['pesan']);
        $data['wa_url'] = $waUrl;
        $logId = $this->logModel->insert($data, true);

        return [
            'id' => $logId,
            'message' => $data['pesan'],
            'wa_url' => $waUrl,
            'number' => $number,
        ];
    }
}
