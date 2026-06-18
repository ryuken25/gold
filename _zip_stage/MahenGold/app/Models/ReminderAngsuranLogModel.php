<?php

namespace App\Models;

use CodeIgniter\Model;

class ReminderAngsuranLogModel extends Model
{
    protected $table = 'reminder_angsuran_logs';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'kredit_id',
        'jadwal_angsuran_id',
        'user_id',
        'nasabah_id',
        'jenis',
        'channel',
        'tujuan',
        'subjek',
        'pesan',
        'status',
        'error',
        'dikirim_oleh',
        'tanggal_referensi',
    ];

    protected $useTimestamps = true;
}
