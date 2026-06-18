<?php

namespace App\Models;

use CodeIgniter\Model;

class PengaturanSistemModel extends Model
{
    protected $table = 'pengaturan_sistem';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'nama_toko',
        'nomor_whatsapp_toko',
        'margin_default',
        'logo_text',
        'alamat_toko',
    ];

    protected $useTimestamps = true;

    /**
     * Pengaturan sistem di-hardcode (tidak dapat diubah dari UI admin).
     * Mempertahankan semua key yang dipakai view & service.
     */
    public function getPengaturan(): array
    {
        if (!function_exists('wa_number_normalize')) {
            helper('mahen');
        }
        return [
            'id'                  => 1,
            'nama_toko'           => 'MahenGold',
            'nomor_whatsapp_toko' => wa_number_normalize((string) env('WA_TARGET_NUMBER', '6282146575233')),
            'margin_default'      => 10.00,
            'dp_minimal'          => 200000,
            'logo_text'           => 'MG',
            'alamat_toko'         => 'Jl. Emas Mulia No. 1, Denpasar, Bali',
        ];
    }
}
