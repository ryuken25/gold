<?php

namespace App\Database\Seeds;

use App\Models\PengaturanSistemModel;
use App\Models\ProdukEmasModel;
use App\Models\UserModel;
use CodeIgniter\Database\Seeder;

class MahenGoldSeeder extends Seeder
{
    protected UserModel $userModel;

    protected ProdukEmasModel $produkModel;

    protected PengaturanSistemModel $pengaturanModel;

    public function run()
    {
        helper('mahen');

        $this->userModel = new UserModel();
        $this->produkModel = new ProdukEmasModel();
        $this->pengaturanModel = new PengaturanSistemModel();

        // ---- 1. Admin users (upsert by email) ----
        foreach ([
            ['email' => 'admin@mahengold.test',       'nama' => 'Administrator MahenGold',  'username' => 'admin',        'role' => 'admin'],
            ['email' => 'staff.verifikasi@mahengold.test', 'nama' => 'Staff Verifikasi',   'username' => 'staff_verif',  'role' => 'admin'],
            ['email' => 'staff.finance@mahengold.test',    'nama' => 'Staff Finance',      'username' => 'staff_finance','role' => 'admin'],
        ] as $admin) {
            $existing = $this->userModel->where('email', $admin['email'])->first();
            if (!$existing) {
                $this->userModel->insert([
                    'nama'          => $admin['nama'],
                    'email'         => $admin['email'],
                    'username'      => $admin['username'],
                    'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                    'role'          => $admin['role'],
                    'is_active'     => 1,
                ]);
            }
        }

        // ---- 2. Pelanggan users (upsert by email) ----
        foreach ([
            ['email' => 'demo.pelanggan@mahengold.test',   'nama' => 'Putu Demo Pelanggan',  'no_telepon' => '6281200000001'],
            ['email' => 'demo.dp.pending@mahengold.test',  'nama' => 'Made DP Pending',       'no_telepon' => '6281200000002'],
            ['email' => 'demo.dp.ready@mahengold.test',    'nama' => 'Ketut DP Ready',        'no_telepon' => '6281200000003'],
            ['email' => 'demo.cash.pending@mahengold.test','nama' => 'Wayan Cash Pending',    'no_telepon' => '6281200000004'],
            ['email' => 'demo.cash.ready@mahengold.test',  'nama' => 'Nyoman Cash Ready',     'no_telepon' => '6281200000005'],
            ['email' => 'demo.shipped@mahengold.test',     'nama' => 'Komang Shipped',        'no_telepon' => '6281200000006'],
            ['email' => 'demo.done@mahengold.test',        'nama' => 'Putu Done',             'no_telepon' => '6281200000007'],
            ['email' => 'demo.rejected@mahengold.test',    'nama' => 'Made Rejected',         'no_telepon' => '6281200000008'],
            ['email' => 'demo.overdue@mahengold.test',     'nama' => 'Ketut Overdue',         'no_telepon' => '6281200000009'],
            ['email' => 'demo.lunas@mahengold.test',       'nama' => 'Wayan Lunas',           'no_telepon' => '6281200000010'],
            ['email' => 'demo.other@mahengold.test',       'nama' => 'I Wayan Other',         'no_telepon' => '6281200000011'],
        ] as $plg) {
            $existing = $this->userModel->where('email', $plg['email'])->first();
            if (!$existing) {
                $this->userModel->insert([
                    'nama'          => $plg['nama'],
                    'email'         => $plg['email'],
                    'username'      => null,
                    'no_telepon'    => $plg['no_telepon'],
                    'password_hash' => password_hash('demo1234', PASSWORD_DEFAULT),
                    'role'          => 'pelanggan',
                    'is_active'     => 1,
                ]);
            }
        }

        // ---- 3. Pengaturan sistem (insert if empty) ----
        if ($this->pengaturanModel->countAllResults() === 0) {
            $this->pengaturanModel->insert([
                'nama_toko'          => 'MahenGold',
                'nomor_whatsapp_toko'=> '6282146575233',
                'margin_default'     => 10.00,
                'dp_minimal'         => 200000,
                'logo_text'          => 'MG',
                'alamat_toko'        => 'Denpasar, Bali',
            ]);
        }

        // ---- 4. Produk demo — upsert by kode_produk ----
        foreach ([
            ['kode_produk' => 'MGD-001', 'nama_produk' => 'Cincin Emas 1 Gram',     'jenis_emas' => 'Perhiasan',  'kadar' => '22K', 'berat_gram' => 1.00,  'harga_pokok' => 1500000,  'stok' => 10],
            ['kode_produk' => 'MGD-002', 'nama_produk' => 'Anting Emas 0.8 Gram',   'jenis_emas' => 'Perhiasan',  'kadar' => '22K', 'berat_gram' => 0.80,  'harga_pokok' => 1250000,  'stok' => 10],
            ['kode_produk' => 'MGD-003', 'nama_produk' => 'Kalung Emas 2 Gram',     'jenis_emas' => 'Perhiasan',  'kadar' => '22K', 'berat_gram' => 2.00,  'harga_pokok' => 3200000,  'stok' => 10],
            ['kode_produk' => 'MGD-004', 'nama_produk' => 'Liontin Emas 1.5 Gram',  'jenis_emas' => 'Perhiasan',  'kadar' => '22K', 'berat_gram' => 1.50,  'harga_pokok' => 2300000,  'stok' => 10],
            ['kode_produk' => 'MGD-005', 'nama_produk' => 'Gelang Emas 3 Gram',     'jenis_emas' => 'Perhiasan',  'kadar' => '22K', 'berat_gram' => 3.00,  'harga_pokok' => 4800000,  'stok' => 10],
            ['kode_produk' => 'MGD-006', 'nama_produk' => 'Logam Mulia 5 Gram',     'jenis_emas' => 'Logam Mulia','kadar' => '24K', 'berat_gram' => 5.00,  'harga_pokok' => 7500000,  'stok' => 10],
            ['kode_produk' => 'MGD-007', 'nama_produk' => 'Cincin Berlian Mini',    'jenis_emas' => 'Perhiasan',  'kadar' => '18K', 'berat_gram' => 2.50,  'harga_pokok' => 5000000,  'stok' => 10],
            ['kode_produk' => 'MGD-008', 'nama_produk' => 'Anting Anak 0.5 Gram',  'jenis_emas' => 'Perhiasan',  'kadar' => '22K', 'berat_gram' => 0.50,  'harga_pokok' => 800000,   'stok' => 10],
            ['kode_produk' => 'MGD-009', 'nama_produk' => 'Kalung Premium 5 Gram', 'jenis_emas' => 'Perhiasan',  'kadar' => '24K', 'berat_gram' => 5.00,  'harga_pokok' => 8000000,  'stok' => 10],
            ['kode_produk' => 'MGD-010', 'nama_produk' => 'Paket Investasi Emas 10 Gram', 'jenis_emas' => 'Logam Mulia', 'kadar' => '24K', 'berat_gram' => 10.00, 'harga_pokok' => 15000000, 'stok' => 10],
            ['kode_produk' => 'MGD-011', 'nama_produk' => 'Logam Mulia 10 Gram',          'jenis_emas' => 'Logam Mulia', 'kadar' => '24K', 'berat_gram' => 10.00, 'harga_pokok' => 14500000, 'stok' => 10],
            ['kode_produk' => 'MGD-012', 'nama_produk' => 'Gelang Premium 7 Gram',         'jenis_emas' => 'Perhiasan',   'kadar' => '24K', 'berat_gram' => 7.00,  'harga_pokok' => 11000000, 'stok' => 10],
        ] as $produk) {
            $existing = $this->produkModel->withDeleted()->where('kode_produk', $produk['kode_produk'])->first();
            if ($existing) {
                $updateData = $produk + ['status' => 'aktif', 'deskripsi' => 'Produk emas premium MahenGold.'];
                $this->produkModel->update($existing['id'], $updateData);
                if (!empty($existing['deleted_at'])) {
                    $this->produkModel->builder()->where('id', $existing['id'])->update(['deleted_at' => null]);
                }
                continue;
            }
            $produk['status'] = 'aktif';
            $produk['deskripsi'] = 'Produk emas premium MahenGold.';
            $produk['gambar_url'] = null;
            $this->produkModel->insert($produk);
        }

        // ---- 5. Generate dummy image files ----
        $this->writeDemoImage(WRITEPATH . 'uploads/ktp/demo_ktp.png', 'KTP DEMO', 'Demo Pelanggan');
    }

    protected function writeDemoImage(string $path, string $judul, string $sub): void
    {
        if (is_file($path)) {
            return;
        }
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (!is_file($dir . '/index.html')) {
            @file_put_contents($dir . '/index.html', '');
        }

        if (function_exists('imagecreatetruecolor')) {
            $img  = imagecreatetruecolor(640, 400);
            $bg   = imagecolorallocate($img, 28, 26, 23);
            $gold = imagecolorallocate($img, 201, 162, 75);
            $soft = imagecolorallocate($img, 156, 147, 133);
            imagefilledrectangle($img, 0, 0, 640, 400, $bg);
            imagefilledrectangle($img, 0, 0, 640, 64, imagecolorallocate($img, 18, 17, 15));
            imagestring($img, 5, 24, 24, 'MahenGold', $gold);
            imagestring($img, 4, 24, 150, $judul, $gold);
            imagestring($img, 3, 24, 185, 'Ref: ' . $sub, $soft);
            imagestring($img, 2, 24, 360, 'Gambar contoh data demo.', $soft);
            imagepng($img, $path);
            imagedestroy($img);
            return;
        }

        @file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
    }
}
