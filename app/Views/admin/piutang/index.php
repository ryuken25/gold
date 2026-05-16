<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="premium-card p-4">
    <div class="d-flex gap-2 flex-wrap mb-4">
        <?php foreach (['' => 'Semua', 'aktif' => 'Aktif', 'lunas' => 'Lunas', 'jatuh_tempo' => 'Jatuh Tempo', 'terlambat' => 'Terlambat'] as $key => $label): ?>
            <a href="<?= base_url('/admin/piutang' . ($key !== '' ? '?filter=' . $key : '')); ?>"
                class="btn <?= $filter === $key ? 'btn-gold' : 'btn-outline-gold'; ?> rounded-pill px-4"><?= esc($label); ?></a>
        <?php endforeach; ?>
    </div>
    <?php if ($rows): ?>
        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Nasabah</th>
                        <th>Kredit</th>
                        <th>Produk</th>
                        <th>Total</th>
                        <th>Terbayar</th>
                        <th>Sisa</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody><?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= esc($row['nama_nasabah']); ?>
                                <div class="small text-muted"><?= esc($row['no_telepon']); ?></div>
                            </td>
                            <td><a href="<?= base_url('/admin/kredit/' . $row['id']); ?>"><?= esc($row['kode_kredit']); ?></a>
                            </td>
                            <td><?= esc($row['nama_produk']); ?></td>
                            <td><?= esc(format_rupiah($row['total_harga_kredit'])); ?></td>
                            <td><?= esc(format_rupiah($row['total_terbayar'])); ?></td>
                            <td><?= esc(format_rupiah($row['sisa_piutang'])); ?></td>
                            <td><span
                                    class="badge text-bg-<?= esc(status_badge_class($row['status'])); ?>"><?= esc($row['status']); ?></span>
                            </td>
                        </tr><?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>    <?= view('partials/empty_state', ['title' => 'Tidak ada data piutang untuk filter ini']); ?><?php endif; ?>
</div>
<?= $this->endSection(); ?>