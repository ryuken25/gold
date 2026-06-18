<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MahenGoldWorkflowSeeder extends CodeIgniter\Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        $today = new \DateTimeImmutable('today');

        // ============================================================
        // 1. USERS (idempotent by email)
        // ============================================================
        $adminId = $this->upsertUser($db, 'admin@mahengold.test', 'Admin MahenGold', 'admin123', 'admin');
        $demoUserId = $this->upsertUser($db, 'demo.pelanggan@mahengold.test', 'Nadiari Putri', 'demo1234', 'pelanggan');
        $demo2UserId = $this->upsertUser($db, 'demo.lain@mahengold.test', 'Kadek Nadi', 'demo1234', 'pelanggan');

        // ============================================================
        // 2. NASABAH (idempotent by user_id + nama)
        // ============================================================
        $nasabah1 = $this->upsertNasabah($db, $demoUserId, 'Nadiari Putri', '081234567890');
        $nasabah2 = $this->upsertNasabah($db, $demo2UserId, 'Kadek Nadi', '089876543210');

        // Get produk
        $produk = $db->table('produk_emas')->where('status', 'aktif')->first();
        if (!$produk) {
            throw new \RuntimeException('Tidak ada produk aktif. Jalankan MahenGoldSeeder dulu.');
        }
        $produkId = $produk['id'];

        // ============================================================
        // 3. SCENARIO PESANAN (idempotent by kode_pesanan)
        // ============================================================

        // 1. MG-DEMO-WAIT-VERIFY — kredit, baru, DP menunggu
        $this->upsertPengajuan($db, [
            'kode_pesanan'      => 'MG-DEMO-WAIT-VERIFY',
            'user_id'           => $demoUserId,
            'produk_emas_id'    => $produkId,
            'metode_pembayaran' => 'kredit',
            'nama'              => 'Nadiari Putri',
            'no_telepon'        => '081234567890',
            'alamat'            => 'Jl. Raya Ubud No. 123, Gianyar',
            'tenor_bulan'       => 12,
            'periode_angsuran'  => 'bulanan',
            'uang_muka'         => 200000,
            'status'            => 'baru',
            'pembayaran_status' => 'menunggu',
        ]);

        // 2. MG-DEMO-CREDIT-PAY-WAIT — kredit, disetujui, DP menunggu
        $this->upsertPengajuan($db, [
            'kode_pesanan'      => 'MG-DEMO-CREDIT-PAY-WAIT',
            'user_id'           => $demoUserId,
            'produk_emas_id'    => $produkId,
            'metode_pembayaran' => 'kredit',
            'nama'              => 'Nadiari Putri',
            'no_telepon'        => '081234567890',
            'alamat'            => 'Jl. Raya Ubud No. 123, Gianyar',
            'tenor_bulan'       => 12,
            'periode_angsuran'  => 'bulanan',
            'uang_muka'         => 200000,
            'status'            => 'disetujui',
            'pembayaran_status' => 'menunggu',
        ]);

        // 3. MG-DEMO-CREDIT-READY — kredit, disetujui, DP terverifikasi
        $this->upsertPengajuan($db, [
            'kode_pesanan'      => 'MG-DEMO-CREDIT-READY',
            'user_id'           => $demoUserId,
            'produk_emas_id'    => $produkId,
            'metode_pembayaran' => 'kredit',
            'nama'              => 'Nadiari Putri',
            'no_telepon'        => '081234567890',
            'alamat'            => 'Jl. Raya Ubud No. 123, Gianyar',
            'tenor_bulan'       => 12,
            'periode_angsuran'  => 'bulanan',
            'uang_muka'         => 200000,
            'status'            => 'disetujui',
            'pembayaran_status' => 'terverifikasi',
        ]);

        // 4. MG-DEMO-CASH-WAIT — cash, disetujui, pembayaran menunggu
        $this->upsertPengajuan($db, [
            'kode_pesanan'      => 'MG-DEMO-CASH-WAIT',
            'user_id'           => $demo2UserId,
            'produk_emas_id'    => $produkId,
            'metode_pembayaran' => 'cash',
            'nama'              => 'Kadek Nadi',
            'no_telepon'        => '089876543210',
            'alamat'            => 'Jl. Sunset Road No. 456, Denpasar',
            'status'            => 'disetujui',
            'pembayaran_status' => 'menunggu',
        ]);

        // 5. MG-DEMO-CASH-READY — cash, disetujui, pembayaran terverifikasi
        $this->upsertPengajuan($db, [
            'kode_pesanan'      => 'MG-DEMO-CASH-READY',
            'user_id'           => $demo2UserId,
            'produk_emas_id'    => $produkId,
            'metode_pembayaran' => 'cash',
            'nama'              => 'Kadek Nadi',
            'no_telepon'        => '089876543210',
            'alamat'            => 'Jl. Sunset Road No. 456, Denpasar',
            'status'            => 'disetujui',
            'pembayaran_status' => 'terverifikasi',
        ]);

        // 6. MG-DEMO-SHIPPED-RESI — dikirim via resi
        $this->upsertPengajuan($db, [
            'kode_pesanan'         => 'MG-DEMO-SHIPPED-RESI',
            'user_id'              => $demoUserId,
            'produk_emas_id'       => $produkId,
            'metode_pembayaran'    => 'cash',
            'nama'                 => 'Nadiari Putri',
            'no_telepon'           => '081234567890',
            'alamat'               => 'Jl. Raya Ubud No. 123, Gianyar',
            'status'               => 'dikirim',
            'pembayaran_status'    => 'terverifikasi',
            'metode_pengiriman'    => 'resi',
            'referensi_pengiriman' => 'JNE-1234567890',
            'dikirim_pada'         => $today->modify('-2 days')->format('Y-m-d H:i:s'),
            'dikirim_oleh'         => $adminId,
        ]);

        // 7. MG-DEMO-SHIPPED-PHONE — dikirim via no HP
        $this->upsertPengajuan($db, [
            'kode_pesanan'         => 'MG-DEMO-SHIPPED-PHONE',
            'user_id'              => $demo2UserId,
            'produk_emas_id'       => $produkId,
            'metode_pembayaran'    => 'cash',
            'nama'                 => 'Kadek Nadi',
            'no_telepon'           => '089876543210',
            'alamat'               => 'Jl. Sunset Road No. 456, Denpasar',
            'status'               => 'dikirim',
            'pembayaran_status'    => 'terverifikasi',
            'metode_pengiriman'    => 'no_hp',
            'referensi_pengiriman' => '6281234567890',
            'dikirim_pada'         => $today->modify('-1 day')->format('Y-m-d H:i:s'),
            'dikirim_oleh'         => $adminId,
        ]);

        // 8. MG-DEMO-DONE — selesai
        $this->upsertPengajuan($db, [
            'kode_pesanan'         => 'MG-DEMO-DONE',
            'user_id'              => $demoUserId,
            'produk_emas_id'       => $produkId,
            'metode_pembayaran'    => 'cash',
            'nama'                 => 'Nadiari Putri',
            'no_telepon'           => '081234567890',
            'alamat'               => 'Jl. Raya Ubud No. 123, Gianyar',
            'status'               => 'selesai',
            'pembayaran_status'    => 'terverifikasi',
            'metode_pengiriman'    => 'resi',
            'referensi_pengiriman' => 'JNE-0987654321',
            'diverifikasi_pada'    => $today->modify('-10 days')->format('Y-m-d H:i:s'),
            'diverifikasi_oleh'    => $adminId,
            'dikirim_pada'         => $today->modify('-7 days')->format('Y-m-d H:i:s'),
            'dikirim_oleh'         => $adminId,
            'selesai_pada'         => $today->modify('-3 days')->format('Y-m-d H:i:s'),
            'selesai_oleh'         => $adminId,
        ]);

        // 9. MG-DEMO-REJECTED — ditolak
        $this->upsertPengajuan($db, [
            'kode_pesanan'  => 'MG-DEMO-REJECTED',
            'user_id'       => $demo2UserId,
            'produk_emas_id'=> $produkId,
            'metode_pembayaran' => 'kredit',
            'nama'          => 'Kadek Nadi',
            'no_telepon'    => '089876543210',
            'alamat'        => 'Jl. Sunset Road No. 456, Denpasar',
            'tenor_bulan'   => 12,
            'periode_angsuran' => 'bulanan',
            'uang_muka'     => 200000,
            'status'        => 'ditolak',
            'catatan'       => 'Data KTP tidak sesuai dengan identitas nasabah.',
            'ditolak_pada'  => $today->modify('-5 days')->format('Y-m-d H:i:s'),
            'ditolak_oleh'  => $adminId,
        ]);

        // ============================================================
        // 4. KREDIT DEMO (idempotent by pengajuan_id)
        // ============================================================
        $this->createDemoKredit($db, $nasabah1, $produkId, $today, $adminId);

        log_message('info', 'MahenGoldWorkflowSeeder completed.');
    }

    protected function upsertUser($db, string $email, string $nama, string $password, string $role): int
    {
        $existing = $db->table('users')->where('email', $email)->get()->getRowArray();
        if ($existing) {
            return (int) $existing['id'];
        }

        $db->table('users')->insert([
            'email'         => $email,
            'nama'          => $nama,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role'          => $role,
        ]);
        return (int) $db->insertID();
    }

    protected function upsertNasabah($db, int $userId, string $nama, string $noTelepon): int
    {
        $existing = $db->table('nasabah')->where('user_id', $userId)->get()->getRowArray();
        if ($existing) {
            return (int) $existing['id'];
        }

        $db->table('nasabah')->insert([
            'kode_nasabah' => 'NSB-' . str_pad((string) ($db->table('nasabah')->countAllResults() + 1), 4, '0', STR_PAD_LEFT),
            'user_id'      => $userId,
            'nama'         => $nama,
            'no_telepon'   => $noTelepon,
            'alamat'       => 'Alamat demo',
        ]);
        return (int) $db->insertID();
    }

    protected function upsertPengajuan($db, array $data): int
    {
        $existing = $db->table('pengajuan')->where('kode_pesanan', $data['kode_pesanan'])->get()->getRowArray();
        if ($existing) {
            return (int) $existing['id'];
        }

        $db->table('pengajuan')->insert($data);
        return (int) $db->insertID();
    }

    protected function createDemoKredit($db, int $nasabahId, int $produkId, \DateTimeImmutable $today, int $adminId): void
    {
        $produk = $db->table('produk_emas')->where('id', $produkId)->get()->getRowArray();
        if (!$produk) return;

        $calculator = new \App\Services\CreditCalculatorService();
        $pengaturan = (new \App\Models\PengaturanSistemModel())->getPengaturan();
        $margin = (float) $pengaturan['margin_default'];
        $uangMuka = (int) $pengaturan['dp_minimal'];

        // 1. Kredit aktif lancar
        $this->createOneKredit($db, $nasabahId, $produk, $calculator, $margin, $uangMuka, $today, $adminId, 'MG-KR-ACTIVE', [
            'status' => 'aktif',
            'total_terbayar' => 500000,
        ]);

        // 2. Kredit H-3
        $h3 = $today->modify('+3 days');
        $this->createOneKredit($db, $nasabahId, $produk, $calculator, $margin, $uangMuka, $today, $adminId, 'MG-KR-H3', [
            'status' => 'aktif',
            'total_terbayar' => 0,
            'jadwal_pertama' => $h3->format('Y-m-d'),
        ]);

        // 3. Kredit terlambat 7 hari
        $terlambat = $today->modify('-7 days');
        $this->createOneKredit($db, $nasabahId, $produk, $calculator, $margin, $uangMuka, $today, $adminId, 'MG-KR-OVERDUE', [
            'status' => 'aktif',
            'total_terbayar' => 0,
            'jadwal_pertama' => $terlambat->modify('-1 month')->format('Y-m-d'),
        ]);

        // 4. Kredit lunas
        $kalkulasi = $calculator->calculate($produk['harga_pokok'], $margin, 12, 'bulanan', $uangMuka);
        $this->createOneKredit($db, $nasabahId, $produk, $calculator, $margin, $uangMuka, $today, $adminId, 'MG-KR-PAID', [
            'status' => 'lunas',
            'total_terbayar' => $kalkulasi['sisa_pokok'],
        ]);
    }

    protected function createOneKredit($db, int $nasabahId, array $produk, $calculator, float $margin, int $uangMuka, \DateTimeImmutable $today, int $adminId, string $kode, array $overrides): void
    {
        $existing = $db->table('kredit')->where('kode_kredit', $kode)->get()->getRowArray();
        if ($existing) return;

        $kalkulasi = $calculator->calculate($produk['harga_pokok'], $margin, 12, 'bulanan', $uangMuka);
        $jadwalPertama = ($overrides['jadwal_pertama'] ?? null) ?: $today->modify('+1 month')->format('Y-m-d');

        $totalTerbayar = $overrides['total_terbayar'] ?? 0;
        $sisaPiutang = max(0, $kalkulasi['sisa_pokok'] - $totalTerbayar);

        $db->table('kredit')->insert([
            'kode_kredit'          => $kode,
            'nasabah_id'           => $nasabahId,
            'produk_emas_id'       => $produk['id'],
            'tanggal_kredit'       => $today->format('Y-m-d'),
            'harga_pokok_snapshot' => $kalkulasi['harga_pokok'],
            'margin_persen'        => $kalkulasi['margin_persen'],
            'margin_nominal'       => $kalkulasi['margin_nominal'],
            'total_harga_kredit'   => $kalkulasi['total_harga_kredit'],
            'uang_muka'            => $uangMuka,
            'sisa_pokok_kredit'    => $kalkulasi['sisa_pokok'],
            'tenor_bulan'          => 12,
            'periode_angsuran'     => 'bulanan',
            'jumlah_periode'       => $kalkulasi['jumlah_periode'],
            'nominal_angsuran'     => $kalkulasi['nominal_angsuran'],
            'total_terbayar'       => $totalTerbayar,
            'sisa_piutang'         => $sisaPiutang,
            'status'               => $overrides['status'] ?? 'aktif',
        ]);

        $kreditId = (int) $db->insertID();

        // Generate jadwal
        $jadwal = $calculator->generateSchedule($jadwalPertama, $kalkulasi);
        foreach ($jadwal as &$j) {
            $j['kredit_id'] = $kreditId;
            // Mark first N schedules as paid if total_terbayar > 0
            if ($totalTerbayar > 0) {
                $paid = min($totalTerbayar, (float) $j['nominal_tagihan']);
                $j['nominal_dibayar'] = $paid;
                $j['status'] = $paid >= $j['nominal_tagihan'] ? 'dibayar' : 'sebagian';
                $totalTerbayar -= $paid;
            }
        }
        unset($j);
        $db->table('jadwal_angsuran')->insertBatch($jadwal);
    }
}
