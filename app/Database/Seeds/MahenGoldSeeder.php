<?php

namespace App\Database\Seeds;

use App\Models\PengaturanSistemModel;
use App\Models\ProdukEmasModel;
use App\Models\UserModel;
use CodeIgniter\Database\Seeder;
use Config\Database;

class MahenGoldSeeder extends Seeder
{
    protected UserModel $userModel;
    protected ProdukEmasModel $produkModel;
    protected PengaturanSistemModel $pengaturanModel;

    public function run()
    {
        helper('mahen');
        $db = Database::connect();

        $this->userModel = new UserModel();
        $this->produkModel = new ProdukEmasModel();
        $this->pengaturanModel = new PengaturanSistemModel();

        // Disable FK checks to safely clean old demo data
        $db->query('SET FOREIGN_KEY_CHECKS = 0');

        // Delete all old demo users/customers to prevent duplicates/overlaps
        $this->userModel->like('email', 'demo.', 'after')->delete();
        $this->userModel->where('email', 'winayaarya@gmail.com')->delete();
        $this->userModel->whereIn('nama', [
            'Putu Demo Pelanggan', 'Made DP Pending', 'Ketut DP Ready', 'Wayan Cash Pending',
            'Nyoman Cash Ready', 'Komang Shipped', 'Made Rejected', 'Ketut Overdue', 'Wayan Lunas'
        ])->delete();

        // Re-enable FK checks
        $db->query('SET FOREIGN_KEY_CHECKS = 1');

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

        // ---- 2. Special Dev User ----
        $existingDev = $this->userModel->where('email', 'winayaarya@gmail.com')->first();
        if (!$existingDev) {
            $this->userModel->insert([
                'nama'          => 'I Wayan Zebec 1',
                'email'         => 'winayaarya@gmail.com',
                'username'      => null,
                'no_telepon'    => '6281200000001',
                'password_hash' => password_hash('123123123', PASSWORD_DEFAULT),
                'role'          => 'pelanggan',
                'is_active'     => 1,
            ]);
        }

        // ---- 3. Professional Dummy Customers ----
        $dummies = [
            ['nama' => 'I Made Winayagatar Arya Bhanu', 'email' => 'made.winayagatar@mahengold.test', 'no_telepon' => '6281200000002'],
            ['nama' => 'Ni Putu Kirana Maheswari', 'email' => 'kirana.maheswari@mahengold.test', 'no_telepon' => '6281200000003'],
            ['nama' => 'I Kadek Arya Pranata', 'email' => 'arya.pranata@mahengold.test', 'no_telepon' => '6281200000004'],
            ['nama' => 'Ni Made Sekar Lestari', 'email' => 'sekar.lestari@mahengold.test', 'no_telepon' => '6281200000005'],
            ['nama' => 'I Komang Aditya Mahendra', 'email' => 'aditya.mahendra@mahengold.test', 'no_telepon' => '6281200000006'],
            ['nama' => 'Ni Kadek Diah Paramitha', 'email' => 'diah.paramitha@mahengold.test', 'no_telepon' => '6281200000007'],
            ['nama' => 'I Wayan Surya Pradnyana', 'email' => 'surya.pradnyana@mahengold.test', 'no_telepon' => '6281200000008'],
            ['nama' => 'Ni Luh Ayu Saraswati', 'email' => 'ayu.saraswati@mahengold.test', 'no_telepon' => '6281200000009'],
            ['nama' => 'I Nyoman Bagus Pramana', 'email' => 'bagus.pramana@mahengold.test', 'no_telepon' => '6281200000010'],
            ['nama' => 'Ni Komang Citra Dewayani', 'email' => 'citra.dewayani@mahengold.test', 'no_telepon' => '6281200000011'],
            ['nama' => 'I Ketut Dharma Wijaya', 'email' => 'dharma.wijaya@mahengold.test', 'no_telepon' => '6281200000012'],
            ['nama' => 'Ni Putu Anjani Larasati', 'email' => 'anjani.larasati@mahengold.test', 'no_telepon' => '6281200000013'],
        ];

        foreach ($dummies as $plg) {
            $existing = $this->userModel->where('email', $plg['email'])->first();
            if (!$existing) {
                $this->userModel->insert([
                    'nama'          => $plg['nama'],
                    'email'         => $plg['email'],
                    'username'      => null,
                    'no_telepon'    => $plg['no_telepon'],
                    'password_hash' => password_hash('123123123', PASSWORD_DEFAULT),
                    'role'          => 'pelanggan',
                    'is_active'     => 1,
                ]);
            }
        }

        // ---- 4. System Configuration ----
        $this->pengaturanModel->truncate();
        $this->pengaturanModel->insert([
            'nama_toko'          => 'MahenGold',
            'nomor_whatsapp_toko'=> '6282146575233',
            'margin_default'     => 10.00,
            'dp_minimal'         => 200000,
            'logo_text'          => 'MG',
            'alamat_toko'        => 'Denpasar, Bali',
        ]);

        // ---- 5. Gold Products ----
        $products = [
            ['kode_produk' => 'MGD-001', 'nama_produk' => 'Cincin Emas 1 Gram',     'jenis_emas' => 'Perhiasan',  'kadar' => '22K', 'berat_gram' => 1.00,  'harga_pokok' => 1500000,  'stok' => 10],
            ['kode_produk' => 'MGD-002', 'nama_produk' => 'Anting Emas 0.8 Gram',   'jenis_emas' => 'Perhiasan',  'kadar' => '22K', 'berat_gram' => 0.80,  'harga_pokok' => 1250000,  'stok' => 10],
            ['kode_produk' => 'MGD-003', 'nama_produk' => 'Kalung Emas 2 Gram',     'jenis_emas' => 'Perhiasan',  'kadar' => '22K', 'berat_gram' => 2.00,  'harga_pokok' => 3200000,  'stok' => 10],
            ['kode_produk' => 'MGD-004', 'nama_produk' => 'Liontin Emas 1.5 Gram',  'jenis_emas' => 'Perhiasan',  'kadar' => '22K', 'berat_gram' => 1.50,  'harga_pokok' => 2300000,  'stok' => 10],
            ['kode_produk' => 'MGD-005', 'nama_produk' => 'Gelang Emas 3 Gram',     'jenis_emas' => 'Perhiasan',  'kadar' => '22K', 'berat_gram' => 3.00,  'harga_pokok' => 4800000,  'stok' => 10],
            ['kode_produk' => 'MGD-006', 'nama_produk' => 'Logam Mulia 5 Gram',     'jenis_emas' => 'Logam Mulia','kadar' => '24K', 'berat_gram' => 5.00,  'harga_pokok' => 7500000,  'stok' => 10],
            ['kode_produk' => 'MGD-007', 'nama_produk' => 'Cincin Berlian Mini',    'jenis_emas' => 'Perhiasan',  'kadar' => '18K', 'berat_gram' => 2.50,  'harga_pokok' => 5000000,  'stok' => 10],
            ['kode_produk' => 'MGD-008', 'nama_produk' => 'Anting Anak 0.5 Gram',  'jenis_emas' => 'Perhiasan',  'kadar' => '22K', 'berat_gram' => 0.50,  'harga_pokok' => 800000,   'stok' => 10],
            ['kode_produk' => 'MGD-009', 'nama_produk' => 'Kalung Premium 5 Gram', 'jenis_emas' => 'Perhiasan',  'kadar' => '24K', 'berat_gram' => 5.00,  'harga_pokok' => 8000000,  'stok' => 10],
            ['kode_produk' => 'MGD-010', 'nama_produk' => 'Logam Mulia 10 Gram',    'jenis_emas' => 'Logam Mulia','kadar' => '24K', 'berat_gram' => 10.00, 'harga_pokok' => 14500000, 'stok' => 10],
        ];

        foreach ($products as $produk) {
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

        // ---- 6. Generate demo KTP file ----
        $this->writeDemoImage(WRITEPATH . 'uploads/ktp/demo_ktp.png', 'KTP DEMO', 'I Wayan Zebec 1');
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
