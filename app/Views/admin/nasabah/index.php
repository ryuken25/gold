<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="premium-card p-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <form class="d-flex gap-2" method="get">
            <input type="text" class="form-control" name="q" value="<?= esc($q); ?>" placeholder="Cari nasabah">
            <button class="btn btn-outline-gold rounded-pill px-4">Cari</button>
        </form>
        <a href="<?= base_url('/admin/nasabah/create'); ?>" class="btn btn-gold rounded-pill px-4">Tambah Nasabah</a>
    </div>
    <?php if ($nasabah): ?>
        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Telepon</th>
                        <th>Alamat</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nasabah as $row): ?>
                        <tr>
                            <td><?= esc($row['kode_nasabah']); ?></td>
                            <td><?= esc($row['nama']); ?></td>
                            <td><?= esc($row['no_telepon']); ?></td>
                            <td><?= esc($row['alamat']); ?></td>
                            <td class="text-end">
                                <a href="<?= base_url('/admin/nasabah/' . $row['id'] . '/kartu-piutang'); ?>"
                                    class="btn btn-sm btn-outline-gold rounded-pill">Kartu Piutang</a>
                                <a href="<?= base_url('/admin/nasabah/' . $row['id'] . '/edit'); ?>"
                                    class="btn btn-sm btn-outline-gold rounded-pill">Edit</a>
                                <form action="<?= base_url('/admin/nasabah/' . $row['id'] . '/delete'); ?>" method="post"
                                    class="d-inline" onsubmit="return confirm('Hapus nasabah ini?');">
                                    <?= csrf_field(); ?><button
                                        class="btn btn-sm btn-outline-danger rounded-pill">Hapus</button></form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3"><?= $pager->links(); ?></div>
    <?php else: ?>
        <?= view('partials/empty_state', ['title' => 'Belum ada nasabah']); ?>
    <?php endif; ?>
</div>
<?= $this->endSection(); ?>