<?php

namespace App\Models;

use CodeIgniter\Model;

class EmailLogModel extends Model
{
    protected $table = 'email_logs';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = true;

    protected $updatedField = '';

    protected $allowedFields = [
        'tipe',
        'tujuan_email',
        'nama_tujuan',
        'subjek',
        'body',
        'status',
        'error',
        'related_type',
        'related_id',
    ];
}
