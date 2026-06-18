<?php

namespace App\Models;

use CodeIgniter\Model;

class JadwalAngsuranModel extends Model
{
    protected $table = 'jadwal_angsuran';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'kredit_id',
        'angsuran_ke',
        'tanggal_jatuh_tempo',
        'nominal_tagihan',
        'nominal_dibayar',
        'status',
        'tanggal_dibayar',
    ];

    protected $useTimestamps = true;
}
