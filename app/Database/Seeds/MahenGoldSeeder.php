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
        $db->table('users')->truncate();
        $db->table('nasabah')->truncate();
        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        // ---- 1. Admin users (upsert by email) ----
        foreach ([
            ['email' => 'admin@mahengold.test',       'nama' => 'Administrator MahenGold',  'username' => 'admin',        'role' => 'admin'],
            ['email' => 'staff.verifikasi@mahengold.test', 'nama' => 'Staff Verifikasi',   'username' => 'staff_verif',  'role' => 'admin'],
            ['email' => 'staff.finance@mahengold.test',    'nama' => 'Staff Finance',      'username' => 'staff_finance','role' => 'admin'],
        ] as $admin) {
            $this->userModel->insert([
                'nama'          => $admin['nama'],
                'email'         => $admin['email'],
                'username'      => $admin['username'],
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'role'          => $admin['role'],
                'is_active'     => 1,
            ]);
        }

        // ---- 2. Special Dev User (Single Dummy Customer) ----
        $this->userModel->insert([
            'nama'          => 'I Kadek Nadi Artana',
            'email'         => 'kadeknadi98@gmail.com',
            'username'      => null,
            'no_telepon'    => '6281234567890',
            'password_hash' => password_hash('123123123', PASSWORD_DEFAULT),
            'role'          => 'pelanggan',
            'is_active'     => 1,
        ]);

        // ---- 3. System Configuration ----
        $this->pengaturanModel->truncate();
        $this->pengaturanModel->insert([
            'nama_toko'          => 'MahenGold',
            'nomor_whatsapp_toko'=> '6282146575233',
            'margin_default'     => 10.00,
            'dp_minimal'         => 200000,
            'logo_text'          => 'MG',
            'alamat_toko'        => 'Denpasar, Bali',
        ]);

        // ---- 4. Gold Products ----
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

        // ---- 5. Generate demo KTP file ----
        $this->writeDemoImage(WRITEPATH . 'uploads/ktp/demo_ktp.png', 'KTP DEMO', 'kadeknadi98@gmail.com');
    }

    protected function writeDemoImage(string $path, string $judul, string $sub): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (!is_file($dir . '/index.html')) {
            @file_put_contents($dir . '/index.html', '');
        }

        if (function_exists('imagecreatetruecolor')) {
            $img  = imagecreatetruecolor(640, 400);
            
            if (strpos($path, 'ktp') !== false) {
                $bg   = imagecolorallocate($img, 45, 62, 80);
                $gold = imagecolorallocate($img, 201, 162, 75);
                $white = imagecolorallocate($img, 255, 255, 255);
                $red = imagecolorallocate($img, 220, 53, 69);
                
                imagefilledrectangle($img, 0, 0, 640, 400, $bg);
                imagefilledrectangle($img, 0, 0, 640, 64, imagecolorallocate($img, 28, 38, 49));
                imagestring($img, 5, 24, 24, 'DUMMY / BUKAN DOKUMEN RESMI', $red);
                
                imagestring($img, 5, 24, 90, 'KARTU IDENTITAS DUMMY', $gold);
                imagestring($img, 4, 24, 140, 'NIK  : DEMO-000001', $white);
                imagestring($img, 4, 24, 180, 'Nama : I Kadek Nadi Artana', $white);
                imagestring($img, 4, 24, 220, 'Asal : Denpasar, Bali', $white);
                
                imagestring($img, 5, 100, 300, 'DUMMY - BUKAN DOKUMEN RESMI', $red);
                imagestring($img, 2, 24, 370, 'Hanya digunakan untuk keperluan pengujian sistem.', $white);
            } else {
                $bg   = imagecolorallocate($img, 244, 246, 249);
                $dark = imagecolorallocate($img, 33, 37, 41);
                $gold = imagecolorallocate($img, 185, 130, 19);
                $green = imagecolorallocate($img, 40, 167, 69);
                $red = imagecolorallocate($img, 220, 53, 69);
                
                imagefilledrectangle($img, 0, 0, 640, 400, $bg);
                imagerectangle($img, 10, 10, 630, 390, $gold);
                imagefilledrectangle($img, 11, 11, 629, 64, imagecolorallocate($img, 233, 236, 239));
                imagestring($img, 5, 24, 24, 'BUKTI PEMBAYARAN DUMMY', $green);
                imagestring($img, 4, 450, 24, 'STATUS: SUCCESS', $green);
                
                imagestring($img, 5, 150, 150, 'DUMMY - TIDAK SAH', $red);
                imagestring($img, 4, 50, 200, 'Referensi : ' . $sub, $dark);
                imagestring($img, 4, 50, 240, 'Nominal   : ' . $judul, $dark);
                imagestring($img, 4, 50, 280, 'Tanggal   : ' . date('Y-m-d'), $dark);
                
                imagestring($img, 2, 50, 350, 'Dokumen ini dibuat otomatis sebagai data simulasi seeder.', $dark);
            }
            
            imagepng($img, $path);
            imagedestroy($img);
            return;
        }

        @file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
    }
}
