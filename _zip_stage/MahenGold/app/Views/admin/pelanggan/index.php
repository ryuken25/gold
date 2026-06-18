<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="premium-card p-4">
    <form class="d-flex gap-2 mb-4" method="get">
        <input type="text" class="form-control" name="q" value="<?= esc($q); ?>"
            placeholder="Cari nama / email / telepon">
        <button class="btn btn-outline-gold rounded-pill px-4">Cari</button>
    </form>

    <?php if ($pelanggan): ?>
        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th class="text-center">Pesanan</th>
                        <th class="text-center">Punya Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pelanggan as $row): ?>
                        <tr>
                            <td class="fw-semibold"><?= esc($row['nama']); ?></td>
                            <td><?= esc($row['email']); ?></td>
                            <td><?= esc($row['no_telepon'] ?: '-'); ?></td>
                            <td class="text-center"><?= esc($row['jumlah_pesanan']); ?></td>
                            <td class="text-center">
                                <?php if ((int) $row['punya_nasabah'] > 0): ?>
                                    <span class="badge text-bg-success">Ya</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Belum</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3"><?= $pager->links(); ?></div>
    <?php else: ?>
        <?= view('partials/empty_state', ['title' => 'Belum ada pelanggan terdaftar']); ?>
    <?php endif; ?>
</div>
<?= $this->endSection(); ?>
