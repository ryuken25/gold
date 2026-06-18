<?php

namespace App\Models;

use CodeIgniter\Model;

class PembayaranAngsuranModel extends Model
{
    protected $table = 'pembayaran_angsuran';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'kode_pembayaran',
        'kredit_id',
        'jadwal_angsuran_id',
        'bukti_pembayaran_id',
        'tanggal_bayar',
        'nominal_bayar',
        'metode_pembayaran',
        'keterangan',
        'dicatat_oleh',
    ];

    protected $useTimestamps = true;
}
