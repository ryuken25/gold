<?php

namespace App\Models;

use CodeIgniter\Model;

class PengajuanAktivitasModel extends Model
{
    protected $table = 'pengajuan_aktivitas';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = true;

    protected $updatedField = '';

    protected $allowedFields = [
        'pengajuan_id',
        'aksi',
        'keterangan',
        'aktor',
    ];

    /**
     * Catat satu baris aktivitas pada timeline pengajuan.
     */
    public function log(int $pengajuanId, string $aksi, ?string $keterangan = null, ?string $aktor = null): void
    {
        $this->insert([
            'pengajuan_id' => $pengajuanId,
            'aksi'         => $aksi,
            'keterangan'   => $keterangan,
            'aktor'        => $aktor,
        ]);
    }
}
