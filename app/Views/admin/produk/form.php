<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="premium-card p-4">
    <form action="<?= $produk ? base_url('/admin/produk/' . $produk['id']) : base_url('/admin/produk'); ?>"
        method="post" enctype="multipart/form-data" class="row g-3">
        <?= csrf_field(); ?>
        <div class="col-md-4"><label class="form-label">Kode Produk</label><input type="text" name="kode_produk"
                class="form-control" value="<?= esc(old('kode_produk', $produk['kode_produk'] ?? '')); ?>" required>
        </div>
        <div class="col-md-8"><label class="form-label">Nama Produk</label><input type="text" name="nama_produk"
                class="form-control" value="<?= esc(old('nama_produk', $produk['nama_produk'] ?? '')); ?>" required>
        </div>
        <div class="col-md-4"><label class="form-label">Jenis Emas</label><input type="text" name="jenis_emas"
                class="form-control" value="<?= esc(old('jenis_emas', $produk['jenis_emas'] ?? 'Perhiasan')); ?>"
                required></div>
        <div class="col-md-4"><label class="form-label">Kadar</label><input type="text" name="kadar"
                class="form-control" value="<?= esc(old('kadar', $produk['kadar'] ?? '22K')); ?>" required></div>
        <div class="col-md-4"><label class="form-label">Berat (gram)</label><input type="number" step="0.01"
                name="berat_gram" class="form-control"
                value="<?= esc(old('berat_gram', $produk['berat_gram'] ?? '')); ?>" required></div>
        <div class="col-md-4"><label class="form-label">Harga Pokok</label><input type="number" step="0.01"
                name="harga_pokok" class="form-control"
                value="<?= esc(old('harga_pokok', $produk['harga_pokok'] ?? '')); ?>" required></div>
        <div class="col-md-4"><label class="form-label">Stok</label><input type="number" name="stok"
                class="form-control" value="<?= esc(old('stok', $produk['stok'] ?? '0')); ?>" required></div>
        <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select">
                <option value="aktif" <?= old('status', $produk['status'] ?? 'aktif') === 'aktif' ? 'selected' : ''; ?>
                    >Aktif</option>
                <option value="nonaktif" <?= old('status', $produk['status'] ?? '') === 'nonaktif' ? 'selected' : ''; ?>
                    >Nonaktif</option>
            </select></div>
        <div class="col-12">
            <label class="form-label">Gambar Produk</label>
            <input type="file" name="gambar_file" class="form-control" accept="image/jpeg,image/png,image/jpg">
            <div class="form-text">Format JPG/PNG/JPEG, maks. 3 MB. Kosongkan jika tidak ingin mengubah gambar.</div>
            <?php if (!empty($produk['gambar_url'])): ?>
                <div class="mt-2">
                    <span class="d-block small text-muted">Preview Gambar Saat Ini:</span>
                    <?php
                        $isUrl = filter_var($produk['gambar_url'], FILTER_VALIDATE_URL);
                        $imageUrl = $isUrl ? $produk['gambar_url'] : base_url('/produk-gambar/' . $produk['gambar_url']);
                    ?>
                    <img src="<?= esc($imageUrl); ?>" alt="Preview" class="img-thumbnail" style="max-height: 150px;">
                </div>
            <?php endif; ?>
        </div>
        <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control"
                rows="4"><?= esc(old('deskripsi', $produk['deskripsi'] ?? '')); ?></textarea></div>
        <div class="col-12 d-flex gap-2 justify-content-end mobile-stack"><a href="<?= base_url('/admin/produk'); ?>"
                class="btn btn-outline-secondary rounded-pill px-4">Kembali</a><button
                class="btn btn-gold rounded-pill px-4">Simpan</button></div>
    </form>
</div>
<?= $this->endSection(); ?>
