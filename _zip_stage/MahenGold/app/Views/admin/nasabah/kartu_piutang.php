<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="premium-card p-4 h-100">
            <h4 class="fw-bold mb-3"><?= esc($nasabah['nama']); ?></h4>
            <p class="text-muted mb-2"><?= esc($nasabah['no_telepon']); ?></p>
            <p class="text-muted"><?= esc($nasabah['alamat']); ?></p>
            <div class="mini-stats">
                <div><span>Total Kredit</span><strong><?= esc(format_rupiah($summary['total_kredit'] ?? 0)); ?></strong>
                </div>
                <div><span>Total
                        Terbayar</span><strong><?= esc(format_rupiah($summary['total_terbayar'] ?? 0)); ?></strong>
                </div>
                <div><span>Sisa Piutang</span><strong><?= esc(format_rupiah($summary['sisa_piutang'] ?? 0)); ?></strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="premium-card p-4">
            <h5 class="fw-bold mb-3">Riwayat Kredit</h5>
            <?php if ($credits): ?>
                <div class="table-responsive">
                    <table class="table table-modern align-middle">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Produk</th>
                                <th>Total</th>
                                <th>Sisa</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody><?php foreach ($credits as $row): ?>
                                <tr>
                                    <td><a
                                            href="<?= base_url('/admin/kredit/' . $row['id']); ?>"><?= esc($row['kode_kredit']); ?></a>
                                    </td>
                                    <td><?= esc($row['nama_produk']); ?></td>
                                    <td><?= esc(format_rupiah($row['total_harga_kredit'])); ?></td>
                                    <td><?= esc(format_rupiah($row['sisa_piutang'])); ?></td>
                                    <td><span
                                            class="badge text-bg-<?= esc(status_badge_class($row['status'])); ?>"><?= esc($row['status']); ?></span>
                                    </td>
                                </tr><?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?= view('partials/empty_state', ['title' => 'Belum ada riwayat kredit']); ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection(); ?>