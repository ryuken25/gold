<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="premium-card p-4">
    <form action="<?= base_url('/admin/pembayaran'); ?>" method="post" class="row g-3">
        <?= csrf_field(); ?>
        <div class="col-md-6"><label class="form-label">Kredit Aktif</label><select name="kredit_id"
                id="payment_kredit_id" class="form-select" required>
                <option value="">Pilih kredit</option><?php foreach ($credits as $row): ?>
                    <option value="<?= esc($row['id']); ?>" <?= (string) $selectedCredit === (string) $row['id'] || old('kredit_id') == $row['id'] ? 'selected' : ''; ?>><?= esc($row['kode_kredit']); ?> -
                        <?= esc($row['nama_nasabah']); ?> (sisa: <?= esc(format_rupiah($row['sisa_piutang'])); ?>)
                    </option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-6"><label class="form-label">Jadwal Angsuran (Opsional)</label><select
                name="jadwal_angsuran_id" id="jadwal_angsuran_id" class="form-select">
                <option value="">Pembayaran umum untuk kredit</option><?php foreach ($schedules as $row): ?>
                    <option value="<?= esc($row['id']); ?>" data-kredit-id="<?= esc($row['kredit_id']); ?>">
                        <?= esc($row['kode_kredit']); ?> - Angsuran ke <?= esc($row['angsuran_ke']); ?>
                        (<?= esc(format_tanggal($row['tanggal_jatuh_tempo'])); ?>)
                    </option><?php endforeach; ?>
            </select></div>
        <div class="col-md-4"><label class="form-label">Tanggal Bayar</label><input type="date" name="tanggal_bayar"
                class="form-control" value="<?= esc(old('tanggal_bayar', date('Y-m-d'))); ?>" required></div>
        <div class="col-md-4"><label class="form-label">Nominal Bayar</label><input type="number" step="0.01"
                name="nominal_bayar" class="form-control" value="<?= esc(old('nominal_bayar')); ?>" required></div>
        <div class="col-md-4"><label class="form-label">Metode</label><select name="metode_pembayaran"
                class="form-select">
                <option value="transfer">Transfer</option>
                <option value="cash">Cash</option>
                <option value="lainnya">Lainnya</option>
            </select></div>
        <div class="col-12"><label class="form-label">Keterangan</label><textarea name="keterangan" class="form-control"
                rows="4"><?= esc(old('keterangan')); ?></textarea></div>
        <div class="col-12 d-flex justify-content-end gap-2 mobile-stack"><a
                href="<?= base_url('/admin/pembayaran'); ?>"
                class="btn btn-outline-secondary rounded-pill px-4">Kembali</a><button
                class="btn btn-gold rounded-pill px-4">Simpan Pembayaran</button></div>
    </form>
</div>
<?= $this->endSection(); ?>