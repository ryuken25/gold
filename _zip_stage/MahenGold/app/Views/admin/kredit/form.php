<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="premium-card p-4">
            <form action="<?= base_url('/admin/kredit'); ?>" method="post" id="kreditForm" class="row g-3">
                <?= csrf_field(); ?>
                <div class="col-md-6"><label class="form-label">Nasabah</label><select name="nasabah_id"
                        class="form-select" required>
                        <option value="">Pilih nasabah</option><?php foreach ($nasabah as $item): ?>
                            <option value="<?= esc($item['id']); ?>" <?= old('nasabah_id') == $item['id'] ? 'selected' : ''; ?>><?= esc($item['nama']); ?> - <?= esc($item['no_telepon']); ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="col-md-6"><label class="form-label">Produk Emas</label><select name="produk_emas_id"
                        id="produk_emas_id" class="form-select" required>
                        <option value="">Pilih produk</option><?php foreach ($produk as $item): ?>
                            <option value="<?= esc($item['id']); ?>" <?= old('produk_emas_id') == $item['id'] ? 'selected' : ''; ?>><?= esc($item['kode_produk']); ?> - <?= esc($item['nama_produk']); ?> (stok:
                                <?= esc($item['stok']); ?>)
                            </option><?php endforeach; ?>
                    </select></div>
                <div class="col-md-6"><label class="form-label">Tanggal Kredit</label><input type="date"
                        name="tanggal_kredit" class="form-control"
                        value="<?= esc(old('tanggal_kredit', date('Y-m-d'))); ?>" required></div>
                <div class="col-md-6"><label class="form-label">Jatuh Tempo Pertama</label><input type="date"
                        name="tanggal_jatuh_tempo_pertama" class="form-control"
                        value="<?= esc(old('tanggal_jatuh_tempo_pertama', date('Y-m-d', strtotime('+1 month')))); ?>"
                        required></div>
                <div class="col-md-4"><label class="form-label">Tenor</label><select name="tenor_bulan" id="tenor_bulan"
                        class="form-select">
                        <option value="6">6 bulan</option>
                        <option value="10">10 bulan</option>
                        <option value="12" selected>12 bulan</option>
                    </select></div>
                <div class="col-md-4"><label class="form-label">Periode Angsuran</label><select name="periode_angsuran"
                        id="periode_angsuran" class="form-select">
                        <option value="bulanan">Bulanan</option>
                        <option value="mingguan">Mingguan</option>
                    </select></div>
                <div class="col-md-4"><label class="form-label">Margin Default</label><input type="text"
                        class="form-control" value="<?= esc(format_angka($marginDefault, 0)); ?>%" readonly></div>
                <div class="col-12"><label class="form-label">Catatan</label><textarea name="catatan"
                        class="form-control" rows="4"><?= esc(old('catatan')); ?></textarea></div>
                <div class="col-12 d-flex justify-content-end gap-2 mobile-stack"><a
                        href="<?= base_url('/admin/kredit'); ?>"
                        class="btn btn-outline-secondary rounded-pill px-4">Kembali</a><button
                        class="btn btn-gold rounded-pill px-4">Simpan Kredit</button></div>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="premium-card p-4 sticky-lg-top preview-box">
            <h5 class="fw-bold mb-3">Preview Flat Rate</h5>
            <div id="creditPreview" class="small text-muted">Pilih produk untuk melihat estimasi kredit realtime.</div>
        </div>
    </div>
</div>
<?= $this->endSection(); ?>

<?= $this->section('scripts'); ?>
<script>
    window.MahenGoldAdminPreview = {
        endpoint: '<?= base_url('/api/kredit/preview'); ?>',
        formId: 'kreditForm'
    };
</script>
<?= $this->endSection(); ?>