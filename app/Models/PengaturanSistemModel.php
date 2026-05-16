<?php

namespace App\Models;

use CodeIgniter\Model;

class PengaturanSistemModel extends Model
{
    protected $table = 'pengaturan_sistem';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'nama_toko',
        'nomor_whatsapp_toko',
        'margin_default',
        'logo_text',
        'alamat_toko',
    ];

    protected $useTimestamps = true;

    public function getPengaturan(): array
    {
        $data = $this->first();

        if ($data !== null) {
            return $data;
        }

        $id = $this->insert([
            'nama_toko' => 'MahenGold',
            'nomor_whatsapp_toko' => wa_number_normalize((string) env('WA_TARGET_NUMBER', '6282146575233')),
            'margin_default' => 10.00,
            'logo_text' => 'MG',
            'alamat_toko' => 'Denpasar, Bali',
        ], true);

        return $this->find($id) ?? [];
    }
}
