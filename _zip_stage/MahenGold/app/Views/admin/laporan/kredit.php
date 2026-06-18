<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="premium-card p-4">
    <form method="get" class="row g-3 mb-4">
        <div class="col-md-3"><input type="date" class="form-control" name="start_date"
                value="<?= esc(service('request')->getGet('start_date')); ?>"></div>
        <div class="col-md-3"><input type="date" class="form-control" name="end_date"
                value="<?= esc(service('request')->getGet('end_date')); ?>"></div>
        <div class="col-md-3"><select name="status" class="form-select">
                <option value="">Semua Status</option><?php foreach (['aktif', 'lunas', 'dibatalkan'] as $status): ?>
                    <option value="<?= esc($status); ?>" <?= service('request')->getGet('status') === $status ? 'selected' : ''; ?>><?= esc(ucfirst($status)); ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-3 d-flex gap-2"><button class="btn btn-outline-gold flex-fill">Filter</button><a
                href="<?= current_url() . '?' . http_build_query(array_merge(service('request')->getGet(), ['export' => 'csv'])); ?>"
                class="btn btn-gold flex-fill">Export CSV</a></div>
    </form>
    <div class="table-responsive">
        <table class="table table-modern align-middle">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nasabah</th>
                    <th>Produk</th>
                    <th>Tanggal</th>
                    <th>Total</th>
                    <th>Sisa</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody><?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= esc($row['kode_kredit']); ?></td>
                        <td><?= esc($row['nama_nasabah']); ?></td>
                        <td><?= esc($row['nama_produk']); ?></td>
                        <td><?= esc(format_tanggal($row['tanggal_kredit'])); ?></td>
                        <td><?= esc(format_rupiah($row['total_harga_kredit'])); ?></td>
                        <td><?= esc(format_rupiah($row['sisa_piutang'])); ?></td>
                        <td><?= esc($row['status']); ?></td>
                    </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection(); ?>