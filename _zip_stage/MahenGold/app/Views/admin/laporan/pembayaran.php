<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="premium-card p-4">
    <form method="get" class="row g-3 mb-4">
        <div class="col-md-4"><input type="date" class="form-control" name="start_date"
                value="<?= esc(service('request')->getGet('start_date')); ?>"></div>
        <div class="col-md-4"><input type="date" class="form-control" name="end_date"
                value="<?= esc(service('request')->getGet('end_date')); ?>"></div>
        <div class="col-md-4 d-flex gap-2"><button class="btn btn-outline-gold flex-fill">Filter</button><a
                href="<?= current_url() . '?' . http_build_query(array_merge(service('request')->getGet(), ['export' => 'csv'])); ?>"
                class="btn btn-gold flex-fill">Export CSV</a></div>
    </form>
    <div class="table-responsive">
        <table class="table table-modern align-middle">
            <thead>
                <tr>
                    <th>Kode Pembayaran</th>
                    <th>Kredit</th>
                    <th>Nasabah</th>
                    <th>Tanggal</th>
                    <th>Nominal</th>
                    <th>Metode</th>
                </tr>
            </thead>
            <tbody><?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= esc($row['kode_pembayaran']); ?></td>
                        <td><?= esc($row['kode_kredit']); ?></td>
                        <td><?= esc($row['nama_nasabah']); ?></td>
                        <td><?= esc(format_tanggal($row['tanggal_bayar'])); ?></td>
                        <td><?= esc(format_rupiah($row['nominal_bayar'])); ?></td>
                        <td><?= esc($row['metode_pembayaran']); ?></td>
                    </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection(); ?>