<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="premium-card p-4">
    <div class="d-flex justify-content-end mb-4"><a href="<?= base_url('/admin/pembayaran/create'); ?>"
            class="btn btn-gold rounded-pill px-4">Catat Pembayaran</a></div>
    <?php if ($payments): ?>
        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Kredit</th>
                        <th>Nasabah</th>
                        <th>Tanggal</th>
                        <th>Nominal</th>
                        <th>Metode</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody><?php foreach ($payments as $row): ?>
                        <tr>
                            <td><?= esc($row['kode_pembayaran']); ?></td>
                            <td><?= esc($row['kode_kredit']); ?></td>
                            <td><?= esc($row['nama_nasabah']); ?></td>
                            <td><?= esc(format_tanggal($row['tanggal_bayar'])); ?></td>
                            <td><?= esc(format_rupiah($row['nominal_bayar'])); ?></td>
                            <td><?= esc($row['metode_pembayaran']); ?></td>
                            <td class="text-end"><a
                                    href="<?= base_url('/admin/pembayaran/' . $row['id'] . '/wa-konfirmasi'); ?>"
                                    class="btn btn-sm btn-whatsapp rounded-pill">Kirim WA</a></td>
                        </tr><?php endforeach; ?>
                </tbody>
            </table>
        </div><?php else: ?><?= view('partials/empty_state', ['title' => 'Belum ada pembayaran']); ?><?php endif; ?>
</div>
<?= $this->endSection(); ?>