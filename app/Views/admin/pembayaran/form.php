<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="premium-card p-4">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <span class="section-eyebrow d-block mb-1">Catat Pembayaran</span>
                    <h4 class="fw-black mb-0">Form Pembayaran Manual</h4>
                </div>
                <a href="<?= base_url('/admin/transaksi'); ?>" class="btn btn-sm btn-outline-gold rounded-pill">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>

            <?php if ($kredit): ?>
                <div class="alert border-0 mb-4" style="background:linear-gradient(135deg, #f0f9ff, #e0f2fe); border-left:4px solid #0ea5e9 !important;">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Kode Kredit</span>
                            <strong class="fs-5"><?= esc($kredit['kode_kredit']); ?></strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Nasabah</span>
                            <strong><?= esc($kredit['nama_nasabah']); ?></strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Produk</span>
                            <strong><?= esc($kredit['nama_produk']); ?></strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Sisa Piutang</span>
                            <strong class="text-danger fs-5"><?= esc(format_rupiah($kredit['sisa_piutang'])); ?></strong>
                        </div>
                    </div>
                </div>

                <form action="<?= base_url('/admin/pembayaran'); ?>" method="post" id="formPembayaran">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="kredit_id" value="<?= esc($kredit['id']); ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Pembayaran</label>
                            <input type="date" name="tanggal_bayar" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nominal Bayar</label>
                            <input type="number" name="nominal_bayar" class="form-control" min="1"
                                max="<?= esc($kredit['sisa_piutang']); ?>" placeholder="Masukkan nominal" required>
                            <div class="form-text">Maksimal: <?= esc(format_rupiah($kredit['sisa_piutang'])); ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Metode Pembayaran</label>
                            <select name="metode_pembayaran" class="form-select" required>
                                <option value="transfer">Transfer Bank</option>
                                <option value="tunai">Tunai</option>
                                <option value="qris">QRIS</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Alokasi Ke Jadwal</label>
                            <select name="jadwal_angsuran_id" class="form-select">
                                <option value="0">Otomatis (FIFO)</option>
                                <?php foreach ($jadwal as $j): ?>
                                    <?php
                                    $tagihan = (float) $j['nominal_tagihan'];
                                    $dibayar = (float) $j['nominal_dibayar'];
                                    $sisa = $tagihan - $dibayar;
                                    ?>
                                    <option value="<?= esc($j['id']); ?>">
                                        Angsuran ke-<?= esc($j['angsuran_ke']); ?>
                                        (Sisa: <?= esc(format_rupiah($sisa)); ?>)
                                        — Jatuh Tempo: <?= esc(format_tanggal($j['tanggal_jatuh_tempo'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Keterangan <span class="text-muted">(opsional)</span></label>
                            <input type="text" name="keterangan" class="form-control" maxlength="500"
                                placeholder="Catatan untuk pembayaran ini...">
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-gold rounded-pill px-4"
                            onclick="this.disabled=true; this.innerHTML='<span class=\'spinner-border spinner-border-sm me-1\'></span>Memproses...';">
                            <i class="bi bi-check-circle"></i> Simpan Pembayaran
                        </button>
                        <a href="<?= base_url('/admin/kredit/' . $kredit['id']); ?>" class="btn btn-outline-secondary rounded-pill">Batal</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    Kredit tidak ditemukan. Silakan pilih kredit dari halaman
                    <a href="<?= base_url('/admin/transaksi?tipe=kredit'); ?>">Transaksi</a>.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection(); ?>
