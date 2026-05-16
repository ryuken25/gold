<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="premium-card p-4">
            <form action="<?= base_url('/admin/pengaturan'); ?>" method="post" class="row g-3">
                <?= csrf_field(); ?>
                <div class="col-md-6"><label class="form-label">Nama Toko</label><input type="text" name="nama_toko"
                        class="form-control" value="<?= esc(old('nama_toko', $setting['nama_toko'])); ?>" required>
                </div>
                <div class="col-md-6"><label class="form-label">Logo Text</label><input type="text" name="logo_text"
                        class="form-control" value="<?= esc(old('logo_text', $setting['logo_text'])); ?>" required>
                </div>
                <div class="col-md-6"><label class="form-label">Nomor WhatsApp Toko</label><input type="text"
                        name="nomor_whatsapp_toko" class="form-control"
                        value="<?= esc(old('nomor_whatsapp_toko', $setting['nomor_whatsapp_toko'])); ?>" required></div>
                <div class="col-md-6"><label class="form-label">Margin Default (%)</label><input type="number"
                        step="0.01" name="margin_default" class="form-control"
                        value="<?= esc(old('margin_default', $setting['margin_default'])); ?>" required></div>
                <div class="col-12"><label class="form-label">Alamat Toko</label><textarea name="alamat_toko"
                        class="form-control"
                        rows="4"><?= esc(old('alamat_toko', $setting['alamat_toko'])); ?></textarea></div>
                <div class="col-12 d-flex justify-content-end mobile-stack"><button
                        class="btn btn-gold rounded-pill px-4">Simpan
                        Pengaturan</button></div>
            </form>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="premium-card p-4 h-100">
            <h5 class="fw-bold mb-3">Mode WhatsApp</h5>
            <div class="mini-stats">
                <div><span>WA_MODE</span><strong><?= esc(env('WA_MODE', 'link')); ?></strong></div>
                <div>
                    <span>WA_TARGET_NUMBER</span><strong><?= esc(env('WA_TARGET_NUMBER', $setting['nomor_whatsapp_toko'])); ?></strong>
                </div>
            </div>
            <p class="text-muted small mb-0 mt-3">Mode default menggunakan template wa.me dan tidak mewajibkan token
                Cloud API.</p>
        </div>
    </div>
</div>
<?= $this->endSection(); ?>