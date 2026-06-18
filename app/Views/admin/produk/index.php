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
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill js-hapus-produk"
                                    data-id="<?= esc($row['id']); ?>" data-nama="<?= esc(addslashes($row['nama_produk'])); ?>">Hapus</button>
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

<?= $this->section('scripts'); ?>
<script>
(function() {
    const CSRF_NAME = '<?= csrf_token() ?>';
    const CSRF_HASH = '<?= csrf_hash() ?>';

    function ajaxPost(url) {
        const fd = new FormData();
        fd.append(CSRF_NAME, CSRF_HASH);
        fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    MahenDialog.success({ title: 'Berhasil', message: data.message, onConfirm: () => { window.location.href = data.redirect || '/admin/produk'; } });
                } else {
                    MahenDialog.error({ title: 'Gagal', message: data.message || 'Terjadi kesalahan.' });
                }
            })
            .catch(() => MahenDialog.error({ title: 'Kesalahan', message: 'Gagal menghubungi server.' }));
    }

    document.querySelectorAll('.js-hapus-produk').forEach(btn => {
        btn.addEventListener('click', () => {
            MahenDialog.confirm({
                title: 'Hapus Produk',
                message: 'Apakah Anda yakin ingin menghapus produk ' + btn.dataset.nama + '? Data yang dihapus tidak dapat dikembalikan.',
                confirmText: 'Ya, Hapus',
                confirmClass: 'btn-danger',
                onConfirm: () => ajaxPost('/admin/produk/' + btn.dataset.id + '/delete')
            });
        });
    });
})();
</script>
<?= $this->endSection(); ?>