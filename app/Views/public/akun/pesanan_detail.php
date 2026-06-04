<?= $this->extend('layouts/public'); ?>

<?= $this->section('content'); ?>
<section class="section-padding bg-cream-soft akun-section">
    <div class="container-mg">
        <div class="breadcrumb-mg mb-3">
            <a href="<?= base_url('/akun/pesanan'); ?>">Pesanan</a><span>/</span><span><?= esc($pengajuan['kode_pesanan'] ?? 'Detail'); ?></span>
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-lg-3">
                <?= $this->include('public/akun/_nav'); ?>
            </div>

            <div class="col-lg-9">
                <div class="feature-card p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <span class="section-eyebrow d-block mb-1"><?= esc($pengajuan['kode_pesanan'] ?? '-'); ?></span>
                            <h4 class="fw-black mb-0"><?= esc($pengajuan['nama_produk'] ?? 'Produk'); ?></h4>
                        </div>
                        <span class="badge bg-<?= status_badge_class($pengajuan['status']); ?>">
                            <?= esc(ucfirst($pengajuan['status'])); ?>
                        </span>
                    </div>

                    <div class="row g-2 small">
                        <div class="col-sm-6"><span class="text-muted-mg">Metode</span><br><strong><?= esc(ucfirst($pengajuan['metode_pembayaran'])); ?></strong></div>
                        <div class="col-sm-6"><span class="text-muted-mg">Harga</span><br><strong><?= esc(format_rupiah($pengajuan['harga_pokok'] ?? 0)); ?></strong></div>
                        <div class="col-sm-6"><span class="text-muted-mg">Jenis / Kadar</span><br><strong><?= esc($pengajuan['jenis_emas'] ?? '-'); ?> / <?= esc($pengajuan['kadar'] ?? '-'); ?></strong></div>
                        <div class="col-sm-6"><span class="text-muted-mg">Tanggal</span><br><strong><?= esc(format_tanggal($pengajuan['created_at'], 'd M Y')); ?></strong></div>
                        <div class="col-12"><span class="text-muted-mg">Alamat</span><br><strong><?= nl2br(esc($pengajuan['alamat'])); ?></strong></div>
                    </div>
                </div>

                <div class="feature-card p-4">
                    <h5 class="fw-bold mb-3">Pembayaran</h5>

                    <?php if (in_array($pengajuan['status'], ['baru', 'diproses'], true)): ?>
                        <div class="alert alert-info mb-0">Pesanan Anda sedang menunggu verifikasi admin. Anda akan
                            menerima email setelah disetujui.</div>

                    <?php elseif (in_array($pengajuan['status'], ['ditolak', 'dibatalkan'], true)): ?>
                        <div class="alert alert-danger mb-0">Pesanan <?= esc($pengajuan['status']); ?>.
                            <?= $pengajuan['catatan'] ? esc($pengajuan['catatan']) : ''; ?></div>

                    <?php elseif ($pengajuan['metode_pembayaran'] === 'kredit'): ?>
                        <p class="text-muted-mg">Pesanan kredit Anda sudah disetujui. Jadwal angsuran &amp; upload bukti per
                            angsuran ada di halaman detail kredit.</p>
                        <?php if ($kredit): ?>
                            <a href="<?= base_url('/akun/kredit/' . $kredit['id']); ?>" class="btn btn-gold">Lihat Jadwal &amp; Bayar Angsuran</a>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0">Data kredit sedang disiapkan admin.</div>
                        <?php endif; ?>

                    <?php else: /* cash disetujui/selesai */ ?>
                        <?php if ($pengajuan['status'] === 'selesai' || ($bukti && $bukti['status'] === 'terverifikasi')): ?>
                            <div class="alert alert-success mb-0">Pembayaran terverifikasi. Pesanan selesai. Terima kasih!</div>
                            <?php if ($bukti): ?>
                                <a href="<?= base_url('/akun/bukti/' . $bukti['id']); ?>" target="_blank" rel="noopener" class="small">Lihat bukti</a>
                            <?php endif; ?>
                        <?php elseif ($bukti && $bukti['status'] === 'menunggu'): ?>
                            <div class="alert alert-warning">Bukti pembayaran Anda sedang menunggu verifikasi admin.</div>
                            <a href="<?= base_url('/akun/bukti/' . $bukti['id']); ?>" target="_blank" rel="noopener" class="small">Lihat bukti</a>
                        <?php else: ?>
                            <?php if ($bukti && $bukti['status'] === 'ditolak'): ?>
                                <div class="alert alert-danger">Bukti ditolak<?= $bukti['catatan_admin'] ? ': ' . esc($bukti['catatan_admin']) : ''; ?>. Silakan unggah ulang.</div>
                            <?php endif; ?>
                            <p class="text-muted-mg">Total yang harus dibayar:
                                <strong><?= esc(format_rupiah($pengajuan['harga_pokok'] ?? 0)); ?></strong>. Unggah bukti
                                transfer/pembayaran Anda (JPG/PNG/PDF, maks 3 MB).</p>
                            <form action="<?= base_url('/akun/pesanan/' . $pengajuan['id'] . '/bukti'); ?>" method="post"
                                enctype="multipart/form-data" class="d-flex gap-2 align-items-center flex-wrap">
                                <?= csrf_field(); ?>
                                <input type="file" name="bukti" class="form-control" accept="image/jpeg,image/png,application/pdf"
                                    required style="max-width:280px;">
                                <button class="btn btn-gold">Upload Bukti</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection(); ?>
