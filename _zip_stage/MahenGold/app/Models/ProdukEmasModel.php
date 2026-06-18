<?php

namespace App\Models;

use CodeIgniter\Model;

class ProdukEmasModel extends Model
{
    protected $table = 'produk_emas';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'kode_produk',
        'nama_produk',
        'jenis_emas',
        'kadar',
        'berat_gram',
        'harga_pokok',
        'stok',
        'deskripsi',
        'gambar_url',
        'status',
    ];

    protected $useTimestamps = true;

    protected $useSoftDeletes = true;

    public function aktif()
    {
        return $this->where('status', 'aktif');
    }

    public function byKode(string $kode): ?array
    {
        return $this->where('kode_produk', $kode)->where('status', 'aktif')->first();
    }
}
