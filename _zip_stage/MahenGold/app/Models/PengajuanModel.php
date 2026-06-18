<?php

namespace App\Models;

use CodeIgniter\Model;

class PengajuanModel extends Model
{
    protected $table = 'pengajuan';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'kode_pesanan',
        'user_id',
        'produk_emas_id',
        'metode_pembayaran',
        'metode_konfirmasi',
        'nama',
        'no_telepon',
        'alamat',
        'tenor_bulan',
        'periode_angsuran',
        'uang_muka',
        'foto_ktp',
        'status',
        'pembayaran_status',
        'catatan',
        // UPDATED: workflow fields
        'metode_pengiriman',
        'referensi_pengiriman',
        'diverifikasi_pada',
        'diverifikasi_oleh',
        'dikirim_pada',
        'dikirim_oleh',
        'selesai_pada',
        'selesai_oleh',
        'ditolak_pada',
        'ditolak_oleh',
    ];

    protected $useTimestamps = true;

    /**
     * Hasilkan kode pesanan human-readable: MG-YYYYMMDD-XXX
     * (XXX = counter harian 3 digit, dipastikan unik).
     */
    public function generateKodePesanan(): string
    {
        $prefix = 'MG-' . date('Ymd') . '-';
        $count  = $this->like('kode_pesanan', $prefix, 'after')->countAllResults();

        do {
            $count++;
            $kode = $prefix . str_pad((string) $count, 3, '0', STR_PAD_LEFT);
        } while ($this->where('kode_pesanan', $kode)->countAllResults() > 0);

        return $kode;
    }
}
