<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="premium-card p-4">
        <form action="<?= $nasabah ? base_url('/admin/nasabah/' . $nasabah['id']) : base_url('/admin/nasabah'); ?>"
                method="post" class="row g-3">
                <?= csrf_field(); ?>
                <div class="col-md-6"><label class="form-label">Nama Nasabah</label><input type="text" name="nama"
                                class="form-control" value="<?= esc(old('nama', $nasabah['nama'] ?? '')); ?>" required>
                </div>
                <div class="col-md-6"><label class="form-label">Nomor Telepon</label><input type="text"
                                name="no_telepon" class="form-control"
                                value="<?= esc(old('no_telepon', $nasabah['no_telepon'] ?? '')); ?>" required>
                </div>
                <div class="col-12">
                        <label class="form-label">Tautkan ke Akun Pelanggan <span class="text-muted">(opsional)</span></label>
                        <?php $selectedUser = old('user_id', $nasabah['user_id'] ?? ''); ?>
                        <select name="user_id" class="form-select">
                                <option value="">— Tidak ditautkan —</option>
                                <?php foreach (($pelanggan ?? []) as $akun): ?>
                                        <option value="<?= esc($akun['id']); ?>" <?= (string) $selectedUser === (string) $akun['id'] ? 'selected' : ''; ?>>
                                                <?= esc($akun['nama']); ?> — <?= esc($akun['email']); ?>
                                        </option>
                                <?php endforeach; ?>
                        </select>
                        <div class="form-text">Tautkan agar pelanggan dapat melihat kredit &amp; jadwal angsurannya di akun.</div>
                </div>
                <div class="col-12"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control"
                                rows="3" required><?= esc(old('alamat', $nasabah['alamat'] ?? '')); ?></textarea></div>
                <div class="col-12"><label class="form-label">Catatan</label><textarea name="catatan"
                                class="form-control"
                                rows="4"><?= esc(old('catatan', $nasabah['catatan'] ?? '')); ?></textarea></div>
                <div class="col-12 d-flex gap-2 justify-content-end mobile-stack"><a
                                href="<?= base_url('/admin/nasabah'); ?>"
                                class="btn btn-outline-secondary rounded-pill px-4">Kembali</a><button
                                class="btn btn-gold rounded-pill px-4">Simpan</button></div>
        </form>
</div>
<?= $this->endSection(); ?>