<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="premium-card p-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <form class="d-flex gap-2" method="get">
            <input type="text" class="form-control" name="q" value="<?= esc($q); ?>"
                placeholder="Cari kode / nama produk">
            <button class="btn btn-outline-gold rounded-pill px-4">Cari</button>
        </form>
        <a href="<?= base_url('/admin/produk/create'); ?>" class="btn btn-gold rounded-pill px-4">Tambah Produk</a>
    </div>
    <?php if ($produk): ?>
        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Produk</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produk as $row): ?>
                        <tr>
                            <td><?= esc($row['kode_produk']); ?></td>
                            <td><strong><?= esc($row['nama_produk']); ?></strong>
                                <div class="small text-muted"><?= esc($row['jenis_emas']); ?> · <?= esc($row['kadar']); ?></div>
                            </td>
                            <td><?= esc(format_rupiah($row['harga_pokok'])); ?></td>
                            <td><?= esc($row['stok']); ?></td>
                            <td><span
                                    class="badge text-bg-<?= esc(status_badge_class($row['status'])); ?>"><?= esc($row['status']); ?></span>
                            </td>
                            <td class="text-end">
                                <a href="<?= base_url('/admin/produk/' . $row['id'] . '/edit'); ?>"
                                    class="btn btn-sm btn-outline-gold rounded-pill">Edit</a>
                                <form action="<?= base_url('/admin/produk/' . $row['id'] . '/delete'); ?>" method="post"
                                    class="d-inline" onsubmit="return confirm('Hapus produk ini?');">
                                    <?= csrf_field(); ?>
                                    <button class="btn btn-sm btn-outline-danger rounded-pill">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3"><?= $pager->links(); ?></div>
    <?php else: ?>
        <?= view('partials/empty_state', ['title' => 'Belum ada produk emas']); ?>
    <?php endif; ?>
</div>
<?= $this->endSection(); ?>