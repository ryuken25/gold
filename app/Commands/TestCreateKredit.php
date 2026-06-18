<?php

namespace App\Commands;

use App\Services\CreditTransactionService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class TestCreateKredit extends BaseCommand
{
    protected $group = 'debug';
    protected $name = 'debug:test-kredit';
    protected $description = 'Test credit creation from pengajuan';

    public function run(array $params)
    {
        $db = Database::connect();

        // Find a kredit pengajuan without existing kredit
        $pengajuan = $db->table('pengajuan')
            ->where('metode_pembayaran', 'kredit')
            ->where('status', 'disetujui')
            ->get()->getRowArray();

        if (!$pengajuan) {
            // Try to find any kredit pengajuan
            $pengajuan = $db->table('pengajuan')
                ->where('metode_pembayaran', 'kredit')
                ->get()->getRowArray();
        }

        if (!$pengajuan) {
            CLI::write('No kredit pengajuan found', 'red');
            return;
        }

        CLI::write("Testing with pengajuan ID={$pengajuan['id']} status={$pengajuan['status']}", 'yellow');

        // Check existing kredit
        $existing = $db->table('kredit')->where('pengajuan_id', $pengajuan['id'])->get()->getRowArray();
        if ($existing) {
            CLI::write("  Existing kredit: ID={$existing['id']} kode={$existing['kode_kredit']}", 'yellow');
            CLI::write("  Will test idempotency (should return existing, not create new)", 'yellow');
        }

        // Check produk
        $produk = $db->table('produk_emas')->where('id', $pengajuan['produk_emas_id'])->get()->getRowArray();
        CLI::write("  Produk: stok={$produk['stok']} status={$produk['status']}", 'yellow');

        // Check nasabah
        $nasabah = $db->table('nasabah')->where('user_id', $pengajuan['user_id'])->get()->getRowArray();
        CLI::write("  Nasabah: " . ($nasabah ? "ID={$nasabah['id']}" : "NOT FOUND"), $nasabah ? 'green' : 'red');

        // Try to create
        CLI::newLine();
        CLI::write('Attempting createFromPengajuan...', 'cyan');

        try {
            $service = new CreditTransactionService();
            $pengaturan = (new \App\Models\PengaturanSistemModel())->getPengaturan();
            $result = $service->createFromPengajuan($pengajuan, (float) $pengaturan['margin_default']);
            CLI::write('SUCCESS!', 'green');
            CLI::write('  Kredit ID: ' . ($result['kredit']['id'] ?? '?'), 'green');
            CLI::write('  Kode: ' . ($result['kredit']['kode_kredit'] ?? '?'), 'green');
            CLI::write('  Reused: ' . ($result['reused'] ? 'yes' : 'no'), 'green');
        } catch (\Throwable $e) {
            CLI::write('FAILED: ' . $e->getMessage(), 'red');
            CLI::write('File: ' . $e->getFile() . ':' . $e->getLine(), 'gray');
        }
    }
}
