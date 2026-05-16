<?php

namespace App\Models;

use CodeIgniter\Model;

class NasabahModel extends Model
{
    protected $table = 'nasabah';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'kode_nasabah',
        'nama',
        'no_telepon',
        'alamat',
        'catatan',
    ];

    protected $useTimestamps = true;

    protected $useSoftDeletes = true;
}
