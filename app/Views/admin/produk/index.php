<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="premium-card p-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <form class="d-flex gap-2" method="get">
            <input type="text" class="form-control" name="q" value="<?= esc($q); ?>"
                placeholder="Cari kode / nama produk">
            <button class="btn btn-outline-gold rounded-pill px-4">Cari</button>
        </form>
        <button class="btn btn-gold rounded-pill px-4" type="button" data-bs-toggle="collapse" data-bs-target="#createProductForm">Tambah Produk</button>
    </div>

    <!-- Collapsible Tambah Produk Form -->
    <div class="collapse <?= (session()->getFlashdata('error') || session()->getFlashdata('errors')) ? 'show' : ''; ?> mb-4" id="createProductForm">
        <div class="premium-card p-4 border border-warning">
            <h5 class="fw-bold mb-3 text-gold">Tambah Produk Baru</h5>
            <form action="<?= base_url('/admin/produk'); ?>" method="post" enctype="multipart/form-data" class="row g-3">
                <?= csrf_field(); ?>
                <div class="col-md-4">
                    <label class="form-label">Kode Produk</label>
                    <input type="text" name="kode_produk" class="form-control" value="<?= esc(old('kode_produk')); ?>" placeholder="Contoh: EL-001" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Nama Produk</label>
                    <input type="text" name="nama_produk" class="form-control" value="<?= esc(old('nama_produk')); ?>" placeholder="Nama produk emas" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jenis Emas</label>
                    <input type="text" name="jenis_emas" class="form-control" value="<?= esc(old('jenis_emas', 'Perhiasan')); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kadar</label>
                    <input type="text" name="kadar" class="form-control" value="<?= esc(old('kadar', '22K')); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Berat (gram)</label>
                    <input type="number" step="0.01" name="berat_gram" class="form-control" value="<?= esc(old('berat_gram')); ?>" placeholder="0.00" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Harga Pokok</label>
                    <input type="number" step="0.01" name="harga_pokok" class="form-control" value="<?= esc(old('harga_pokok')); ?>" placeholder="0" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Stok</label>
                    <input type="number" name="stok" class="form-control" value="<?= esc(old('stok', '0')); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="aktif" <?= old('status', 'aktif') === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="nonaktif" <?= old('status') === 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Gambar Produk</label>
                    <input type="file" name="gambar_file" class="form-control" accept="image/jpeg,image/png,image/jpg">
                    <div class="form-text text-muted-mg">Format JPG/PNG/JPEG, maks. 3 MB.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="deskripsi" class="form-control" rows="3"><?= esc(old('deskripsi')); ?></textarea>
                </div>
                <div class="col-12 d-flex gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-toggle="collapse" data-bs-target="#createProductForm">Batal</button>
                    <button type="submit" class="btn btn-gold rounded-pill px-4">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Product Card Grid -->
    <?php if ($produk): ?>
        <div class="product-grid mb-4">
            <?php $catalogImages = ['product-ring.jpg', 'product-necklace.jpg', 'product-earrings.jpg', 'gold-bars.jpg']; ?>
            <?php foreach ($produk as $index => $row): ?>
                <?php
                    $imageName = $catalogImages[$index % count($catalogImages)];
                    $fallbackUrl = base_url('assets/images/mahengold/' . $imageName);
                    if (!empty($row['gambar_url'])) {
                        $isUrl = filter_var($row['gambar_url'], FILTER_VALIDATE_URL);
                        $imageUrl = $isUrl ? $row['gambar_url'] : base_url('/produk-gambar/' . $row['gambar_url']);
                    } else {
                        $imageUrl = $fallbackUrl;
                    }
                ?>
                <article class="product-card">
                    <div class="product-visual image-visual">
                        <img src="<?= esc($imageUrl); ?>" alt="<?= esc($row['nama_produk']); ?>" loading="lazy" onerror="this.onerror=null;this.src='<?= esc($fallbackUrl); ?>';">
                    </div>
                    <div class="product-content">
                        <div class="d-flex justify-content-between gap-3 align-items-start mb-3">
                            <div class="min-w-0">
                                <div class="product-code"><?= esc($row['kode_produk']); ?></div>
                                <h5 class="fw-bold mb-1 text-truncate" title="<?= esc($row['nama_produk']); ?>"><?= esc($row['nama_produk']); ?></h5>
                            </div>
                            <span class="badge text-bg-<?= esc($row['status'] === 'aktif' ? 'success' : 'secondary'); ?>"><?= esc($row['status']); ?></span>
                        </div>
                        <p class="text-muted-mg mb-3"><?= esc($row['jenis_emas']); ?> · <?= esc($row['kadar']); ?> · <?= esc(format_angka($row['berat_gram'], 2)); ?> gr</p>
                        <div class="mb-3 catalog-price-list">
                            <div class="price-row"><span>Harga Pokok</span><strong><?= esc(format_rupiah($row['harga_pokok'])); ?></strong></div>
                            <div class="price-row"><span>Stok</span><strong><?= esc($row['stok']); ?></strong></div>
                        </div>
                        <div class="product-actions mt-auto d-flex gap-2">
                            <a href="<?= base_url('/admin/produk/' . $row['id'] . '/edit'); ?>" class="btn btn-outline-gold flex-fill">Edit</a>
                            <button type="button" class="btn btn-outline-danger flex-fill js-hapus-produk" data-id="<?= esc($row['id']); ?>" data-nama="<?= esc(addslashes($row['nama_produk'])); ?>">Hapus</button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
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
    document.querySelectorAll('.js-hapus-produk').forEach(btn => {
        btn.addEventListener('click', () => {
            MahenDialog.confirm({
                title: 'Hapus Produk',
                message: 'Apakah Anda yakin ingin menghapus produk ' + btn.dataset.nama + '? Data yang dihapus tidak dapat dikembalikan.',
                confirmText: 'Ya, Hapus',
                confirmClass: 'btn-danger',
                onConfirm: async (helpers) => {
                    try {
                        const res = await MahenAjax.post('/admin/produk/' + btn.dataset.id + '/delete');
                        helpers.close();
                        MahenDialog.success({ title: 'Dihapus', message: res.message, onConfirm: () => window.location.href = res.redirect || '/admin/produk' });
                    } catch (err) { helpers.finish(); MahenDialog.error({ title: 'Gagal', message: err.message }); }
                }
            });
        });
    });
})();
</script>
<?= $this->endSection(); ?>