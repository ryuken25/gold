<?php

namespace App\Models;

use CodeIgniter\Model;

class PembayaranAlokasiModel extends Model
{
    protected $table = 'pembayaran_alokasi';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'pembayaran_angsuran_id',
        'jadwal_angsuran_id',
        'nominal_alokasi',
    ];

    protected $useTimestamps = true;
}
