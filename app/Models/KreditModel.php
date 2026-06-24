<?php

namespace App\Models;

use CodeIgniter\Model;

class KreditModel extends Model
{
    protected $table = 'kredit';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'kode_kredit',
        'pengajuan_id',
        'nasabah_id',
        'produk_emas_id',
        'tanggal_kredit',
        'harga_pokok_snapshot',
        'margin_persen',
        'margin_nominal',
        'total_harga_kredit',
        'uang_muka',
        'dp_verified_at',
        'dp_status',
        'sisa_pokok_kredit',
        'tenor_bulan',
        'periode_angsuran',
        'jumlah_periode',
        'nominal_angsuran',
        'total_terbayar',
        'sisa_piutang',
        'status',
        'catatan',
    ];

    protected $useTimestamps = true;
}
