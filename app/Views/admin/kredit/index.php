<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="premium-card p-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <form method="get" class="d-flex gap-2">
            <select name="status" class="form-select">
                <option value="">Semua Status</option>
                <?php
                $opsi = [
                    'aktif'       => 'Aktif',
                    'lunas'       => 'Lunas',
                    'dibatalkan'  => 'Dibatalkan',
                    'jatuh_tempo' => 'Jatuh Tempo Hari Ini',
                    'terlambat'   => 'Terlambat',
                ];
                foreach ($opsi as $val => $label): ?>
                    <option value="<?= esc($val); ?>" <?= $status === $val ? 'selected' : ''; ?>><?= esc($label); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-gold rounded-pill px-4">Filter</button>
        </form>
        <a href="<?= base_url('/admin/kredit/create'); ?>" class="btn btn-gold rounded-pill px-4">Buat Kredit</a>
    </div>
    <?php if ($kredit): ?>
        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nasabah</th>
                        <th>Produk</th>
                        <th>Total Kredit</th>
                        <th>Sisa</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody><?php foreach ($kredit as $row): ?>
                        <tr>
                            <td><?= esc($row['kode_kredit']); ?></td>
                            <td><?= esc($row['nama_nasabah']); ?></td>
                            <td><?= esc($row['nama_produk']); ?></td>
                            <td><?= esc(format_rupiah($row['total_harga_kredit'])); ?></td>
                            <td><?= esc(format_rupiah($row['sisa_piutang'])); ?></td>
                            <td><span
                                    class="badge text-bg-<?= esc(status_badge_class($row['status'])); ?>"><?= esc($row['status']); ?></span>
                            </td>
                            <td class="text-end"><a href="<?= base_url('/admin/kredit/' . $row['id']); ?>"
                                    class="btn btn-sm btn-outline-gold rounded-pill">Detail</a></td>
                        </tr><?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <?= view('partials/empty_state', ['title' => 'Belum ada transaksi kredit']); ?>
    <?php endif; ?>
</div>
<?= $this->endSection(); ?>