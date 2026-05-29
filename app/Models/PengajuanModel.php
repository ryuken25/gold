<?php

namespace App\Models;

use CodeIgniter\Model;

class PengajuanModel extends Model
{
    protected $table = 'pengajuan';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'user_id',
        'produk_emas_id',
        'metode_pembayaran',
        'nama',
        'no_telepon',
        'alamat',
        'tenor_bulan',
        'periode_angsuran',
        'foto_ktp',
        'status',
        'catatan',
    ];

    protected $useTimestamps = true;
}
