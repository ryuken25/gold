<?php

namespace App\Database\Seeds;

use App\Models\JadwalAngsuranModel;
use App\Models\KreditModel;
use App\Models\NasabahModel;
use App\Models\PembayaranAngsuranModel;
use App\Models\PengaturanSistemModel;
use App\Models\ProdukEmasModel;
use App\Models\UserModel;
use App\Services\CreditCalculatorService;
use CodeIgniter\Database\Seeder;
use DateInterval;
use DateTimeImmutable;

class MahenGoldSeeder extends Seeder
{
    protected UserModel $userModel;

    protected ProdukEmasModel $produkModel;

    protected NasabahModel $nasabahModel;

    protected KreditModel $kreditModel;

    protected JadwalAngsuranModel $jadwalModel;

    protected PembayaranAngsuranModel $pembayaranModel;

    protected PengaturanSistemModel $pengaturanModel;

    protected CreditCalculatorService $calculator;

    public function run()
    {
        helper('mahen');

        $this->userModel = new UserModel();
        $this->produkModel = new ProdukEmasModel();
        $this->nasabahModel = new NasabahModel();
        $this->kreditModel = new KreditModel();
        $this->jadwalModel = new JadwalAngsuranModel();
        $this->pembayaranModel = new PembayaranAngsuranModel();
        $this->pengaturanModel = new PengaturanSistemModel();
        $this->calculator = new CreditCalculatorService();

        // Idempotent: kalau admin sudah ada, data demo dianggap sudah ter-seed.
        // Aman dijalankan berkali-kali (tidak duplikat, tidak menghapus data).
        if ($this->userModel->where('email', 'admin@mahengold.test')->first()) {
            return;
        }

        $adminId = $this->userModel->insert([
            'nama' => 'Administrator MahenGold',
            'email' => 'admin@mahengold.test',
            'username' => 'admin',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'is_active' => 1,
        ], true);

        $this->pengaturanModel->insert([
            'nama_toko' => 'MahenGold',
            'nomor_whatsapp_toko' => '6282146575233',
            'margin_default' => 10.00,
            'logo_text' => 'MG',
            'alamat_toko' => 'Denpasar, Bali',
        ]);

        $produkIds = [];
        foreach ([
            ['kode_produk' => 'MGD-001', 'nama_produk' => 'Cincin Emas 1 Gram', 'jenis_emas' => 'Perhiasan', 'kadar' => '22K', 'berat_gram' => 1.00, 'harga_pokok' => 1500000, 'stok' => 5],
            ['kode_produk' => 'MGD-002', 'nama_produk' => 'Kalung Emas 2 Gram', 'jenis_emas' => 'Perhiasan', 'kadar' => '22K', 'berat_gram' => 2.00, 'harga_pokok' => 3200000, 'stok' => 3],
            ['kode_produk' => 'MGD-003', 'nama_produk' => 'Anting Emas 0.8 Gram', 'jenis_emas' => 'Perhiasan', 'kadar' => '22K', 'berat_gram' => 0.80, 'harga_pokok' => 1250000, 'stok' => 8],
        ] as $produk) {
            $produk['status'] = 'aktif';
            $produk['deskripsi'] = 'Produk emas premium MahenGold untuk kebutuhan investasi dan perhiasan.';
            $produk['gambar_url'] = null;
            $produkIds[] = $this->produkModel->insert($produk, true);
        }

        $nasabahIds = [];
        foreach ([
            ['nama' => 'Ayu Lestari', 'no_telepon' => '6281234567890', 'alamat' => 'Denpasar'],
            ['nama' => 'Kadek Surya', 'no_telepon' => '6289876543210', 'alamat' => 'Badung'],
            ['nama' => 'Ni Putu Sari', 'no_telepon' => '6281112223334', 'alamat' => 'Gianyar'],
        ] as $index => $nasabah) {
            $nasabah['kode_nasabah'] = generate_kode('NSB', $index + 1);
            $nasabah['catatan'] = 'Data nasabah dummy demo MahenGold.';
            $nasabahIds[] = $this->nasabahModel->insert($nasabah, true);
        }

        $today = new DateTimeImmutable('today');

        $this->seedCredit($adminId, $nasabahIds[0], $produkIds[0], $today->sub(new DateInterval('P70D'))->format('Y-m-d'), $today->sub(new DateInterval('P40D'))->format('Y-m-d'), 12, 'bulanan', 2, 0, 'aktif');
        $this->seedCredit($adminId, $nasabahIds[1], $produkIds[1], $today->sub(new DateInterval('P35D'))->format('Y-m-d'), $today->sub(new DateInterval('P21D'))->format('Y-m-d'), 10, 'mingguan', 5, 0, 'aktif');
        $this->seedCredit($adminId, $nasabahIds[2], $produkIds[2], $today->sub(new DateInterval('P210D'))->format('Y-m-d'), $today->sub(new DateInterval('P180D'))->format('Y-m-d'), 6, 'bulanan', 6, 0, 'lunas');
    }

    protected function seedCredit(
        int $adminId,
        int $nasabahId,
        int $produkId,
        string $tanggalKredit,
        string $tanggalJatuhTempoPertama,
        int $tenorBulan,
        string $periode,
        int $angsuranTerbayar,
        int $nominalTambahan,
        string $forcedStatus = 'aktif'
    ): void {
        $produk = $this->produkModel->find($produkId);
        $kalkulasi = $this->calculator->calculate($produk['harga_pokok'], 10, $tenorBulan, $periode);

        $kreditId = $this->kreditModel->insert([
            'kode_kredit' => generate_kode('KRD', $this->kreditModel->countAllResults() + 1),
            'nasabah_id' => $nasabahId,
            'produk_emas_id' => $produkId,
            'tanggal_kredit' => $tanggalKredit,
            'harga_pokok_snapshot' => $kalkulasi['harga_pokok'],
            'margin_persen' => $kalkulasi['margin_persen'],
            'margin_nominal' => $kalkulasi['margin_nominal'],
            'total_harga_kredit' => $kalkulasi['total_harga_kredit'],
            'tenor_bulan' => $tenorBulan,
            'periode_angsuran' => $periode,
            'jumlah_periode' => $kalkulasi['jumlah_periode'],
            'nominal_angsuran' => $kalkulasi['nominal_angsuran'],
            'total_terbayar' => 0,
            'sisa_piutang' => $kalkulasi['total_harga_kredit'],
            'status' => 'aktif',
            'catatan' => 'Data transaksi kredit dummy untuk demo.',
        ], true);

        $jadwal = $this->calculator->generateSchedule($tanggalJatuhTempoPertama, $kalkulasi);
        foreach ($jadwal as &$item) {
            $item['kredit_id'] = $kreditId;
        }
        unset($item);
        $this->jadwalModel->insertBatch($jadwal);

        $schedules = $this->jadwalModel->where('kredit_id', $kreditId)->orderBy('angsuran_ke', 'ASC')->findAll();

        $totalTerbayar = 0;
        foreach ($schedules as $index => $schedule) {
            if ($index + 1 > $angsuranTerbayar) {
                break;
            }

            $nominalBayar = (int) round((float) $schedule['nominal_tagihan']);
            $tanggalBayar = new DateTimeImmutable($schedule['tanggal_jatuh_tempo']);
            $this->pembayaranModel->insert([
                'kode_pembayaran' => generate_kode('BYR', $this->pembayaranModel->countAllResults() + 1),
                'kredit_id' => $kreditId,
                'jadwal_angsuran_id' => $schedule['id'],
                'tanggal_bayar' => $tanggalBayar->format('Y-m-d'),
                'nominal_bayar' => $nominalBayar,
                'metode_pembayaran' => 'transfer',
                'keterangan' => 'Pembayaran dummy demo.',
                'dicatat_oleh' => $adminId,
            ]);

            $this->jadwalModel->update($schedule['id'], [
                'nominal_dibayar' => $nominalBayar,
                'status' => 'dibayar',
                'tanggal_dibayar' => $tanggalBayar->format('Y-m-d'),
            ]);
            $totalTerbayar += $nominalBayar;
        }

        if ($nominalTambahan > 0 && isset($schedules[$angsuranTerbayar])) {
            $schedule = $schedules[$angsuranTerbayar];
            $this->pembayaranModel->insert([
                'kode_pembayaran' => generate_kode('BYR', $this->pembayaranModel->countAllResults() + 1),
                'kredit_id' => $kreditId,
                'jadwal_angsuran_id' => $schedule['id'],
                'tanggal_bayar' => date('Y-m-d'),
                'nominal_bayar' => $nominalTambahan,
                'metode_pembayaran' => 'cash',
                'keterangan' => 'Pembayaran parsial dummy.',
                'dicatat_oleh' => $adminId,
            ]);

            $status = $nominalTambahan >= $schedule['nominal_tagihan'] ? 'dibayar' : 'sebagian';
            $this->jadwalModel->update($schedule['id'], [
                'nominal_dibayar' => $nominalTambahan,
                'status' => $status,
                'tanggal_dibayar' => date('Y-m-d'),
            ]);
            $totalTerbayar += $nominalTambahan;
        }

        $status = $forcedStatus;
        $sisaPiutang = max(0, $kalkulasi['total_harga_kredit'] - $totalTerbayar);
        if ($forcedStatus !== 'lunas' && $sisaPiutang <= 0) {
            $status = 'lunas';
        }

        if ($forcedStatus === 'lunas') {
            $status = 'lunas';
            $totalTerbayar = $kalkulasi['total_harga_kredit'];
            $sisaPiutang = 0;
            foreach ($schedules as $schedule) {
                $this->jadwalModel->update($schedule['id'], [
                    'nominal_dibayar' => $schedule['nominal_tagihan'],
                    'status' => 'dibayar',
                    'tanggal_dibayar' => $schedule['tanggal_jatuh_tempo'],
                ]);
            }
        }

        $this->kreditModel->update($kreditId, [
            'total_terbayar' => $totalTerbayar,
            'sisa_piutang' => $sisaPiutang,
            'status' => $status,
        ]);

        $this->produkModel->update($produkId, ['stok' => max(0, ((int) $produk['stok']) - 1)]);
    }
}
