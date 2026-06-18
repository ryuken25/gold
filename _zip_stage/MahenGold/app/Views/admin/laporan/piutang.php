<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="premium-card p-4">
    <div class="d-flex justify-content-end mb-4"><a href="<?= current_url() . '?' . http_build_query(array_merge(service('request')->getGet(), ['export' => 'csv'])); ?>" class="btn btn-gold rounded-pill px-4">Export CSV</a></div>
    <div class="table-responsive"><table class="table table-modern align-middle"><thead><tr><th>Kode</th><th>Nasabah</th><th>Total Kredit</th><th>Total Terbayar</th><th>Sisa Piutang</th><th>Status</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= esc($row['kode_kredit']); ?></td><td><?= esc($row['nama_nasabah']); ?></td><td><?= esc(format_rupiah($row['total_harga_kredit'])); ?></td><td><?= esc(format_rupiah($row['total_terbayar'])); ?></td><td><?= esc(format_rupiah($row['sisa_piutang'])); ?></td><td><?= esc($row['status']); ?></td></tr><?php endforeach; ?></tbody></table></div>
</div>
<?= $this->endSection(); ?>
