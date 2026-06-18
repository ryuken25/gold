<?php

namespace App\Models;

use CodeIgniter\Model;

class WhatsAppLogModel extends Model
{
    protected $table = 'whatsapp_logs';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'tipe',
        'target',
        'tujuan_nomor',
        'nama_tujuan',
        'pesan',
        'wa_url',
        'status',
        'related_type',
        'related_id',
        'created_by',
    ];

    protected $useTimestamps = true;
}
