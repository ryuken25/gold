<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="premium-card p-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <div>
            <h5 class="fw-bold mb-1">Transaksi Kredit & Piutang</h5>
            <p class="text-muted small mb-0">Semua data ditampilkan. Warna baris menunjukkan kondisi otomatis.</p>
        </div>
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
                <tbody>
                    <?php foreach ($kredit as $row): ?>
                        <?php
                        // UPDATED: Warna baris otomatis sesuai kondisi
                        $rowClass = '';
                        $badgeText = '';
                        $badgeClass = '';
                        if ($row['status'] === 'lunas') {
                            $rowClass = 'row-lunas';
                            $badgeText = 'Lunas';
                            $badgeClass = 'bg-success';
                        } elseif ($row['status'] === 'aktif' && !empty($row['is_terlambat'])) {
                            $rowClass = 'row-overdue';
                            $badgeText = 'Terlambat';
                            $badgeClass = 'bg-danger';
                        } elseif ($row['status'] === 'aktif') {
                            $badgeText = 'Aktif';
                            $badgeClass = 'bg-primary';
                        } else {
                            $rowClass = 'row-h3';
                            $badgeText = ucfirst($row['status']);
                            $badgeClass = 'bg-warning text-dark';
                        }
                        ?>
                        <tr class="<?= esc($rowClass); ?>">
                            <td><?= esc($row['kode_kredit']); ?></td>
                            <td><?= esc($row['nama_nasabah']); ?></td>
                            <td><?= esc($row['nama_produk']); ?></td>
                            <td><?= esc(format_rupiah($row['total_harga_kredit'])); ?></td>
                            <td><?= esc(format_rupiah($row['sisa_piutang'])); ?></td>
                            <td><span class="badge <?= esc($badgeClass); ?>"><?= esc($badgeText); ?></span></td>
                            <td class="text-end"><a href="<?= base_url('/admin/kredit/' . $row['id']); ?>"
                                    class="btn btn-sm btn-outline-gold rounded-pill">Detail</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Legend with gradient colors -->
        <div class="mt-3 d-flex flex-wrap gap-3 small">
            <span class="legend-lunas px-3 py-1 rounded">Lunas</span>
            <span class="legend-aktif px-3 py-1 rounded">Aktif — Lancar</span>
            <span class="legend-terlambat px-3 py-1 rounded">Aktif — Terlambat</span>
        </div>
    <?php else: ?>
        <?= view('partials/empty_state', ['title' => 'Belum ada transaksi kredit']); ?>
    <?php endif; ?>
</div>
<?= $this->endSection(); ?>