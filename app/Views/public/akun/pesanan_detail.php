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
                        <span class="badge bg-<?= esc(pesanan_badge_class($pengajuan['status'], $pengajuan['metode_pembayaran'], (int)($pengajuan['uang_muka'] ?? 0), $pengajuan['pembayaran_status'] ?? 'belum')); ?>">
                            <?= esc(pesanan_status_label($pengajuan['status'], $pengajuan['metode_pembayaran'], (int)($pengajuan['uang_muka'] ?? 0), $pengajuan['pembayaran_status'] ?? 'belum')); ?>
                        </span>
                    </div>

                    <?php // UPDATED: Order Stepper ala Shopee ?>
                    <?php $currentStep = pesanan_status_step($pengajuan['status']); $steps = pesanan_status_steps(); ?>
                    <?php if ($currentStep > 0): ?>
                    <div class="order-stepper mb-4">
                        <?php foreach ($steps as $idx => $step): ?>
                            <?php 
                            $stepNum = $idx + 1; 
                            $isCompleted = $stepNum <= $currentStep; 
                            $isCurrent = $stepNum === $currentStep; 
                            ?>
                            <div class="stepper-item <?= $isCompleted ? 'completed' : '' ?> <?= $isCurrent ? 'active' : '' ?>">
                                <div class="stepper-circle">
                                    <i class="bi <?= esc($step['icon']); ?>"></i>
                                </div>
                                <div class="stepper-label"><?= esc($step['label']); ?></div>
                                <?php if ($idx < count($steps) - 1): ?>
                                    <?php $lineCompleted = ($stepNum + 1) <= $currentStep; ?>
                                    <div class="stepper-line <?= $lineCompleted ? 'completed' : ''; ?>"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="row g-2 small">
                        <div class="col-sm-6"><span class="text-muted-mg">Metode</span><br><strong><?= esc(ucfirst($pengajuan['metode_pembayaran'])); ?></strong></div>
                        <div class="col-sm-6"><span class="text-muted-mg">Harga</span><br><strong><?= esc(format_rupiah($pengajuan['harga_pokok'] ?? 0)); ?></strong></div>
                        <div class="col-sm-6"><span class="text-muted-mg">Jenis / Kadar</span><br><strong><?= esc($pengajuan['jenis_emas'] ?? '-'); ?> / <?= esc($pengajuan['kadar'] ?? '-'); ?></strong></div>
                        <div class="col-sm-6"><span class="text-muted-mg">Tanggal</span><br><strong><?= esc(format_tanggal($pengajuan['created_at'], 'd M Y')); ?></strong></div>
                        <?php if ($pengajuan['metode_pembayaran'] === 'kredit'): ?>
                            <div class="col-sm-6"><span class="text-muted-mg">Tenor</span><br><strong><?= esc($pengajuan['tenor_bulan']); ?> bulan (<?= esc($pengajuan['periode_angsuran']); ?>)</strong></div>
                            <div class="col-sm-6"><span class="text-muted-mg">Uang Muka (DP)</span><br><strong><?= esc(format_rupiah($pengajuan['uang_muka'] ?? 0)); ?></strong></div>
                        <?php endif; ?>
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
                        <?php $dp = (int) round((float) ($pengajuan['uang_muka'] ?? 0)); ?>

                        <?php // ---- Bayar Uang Muka (DP) dulu ---- ?>
                        <?php if ($dp > 0): ?>
                            <div class="mb-4">
                                <h6 class="fw-bold mb-2">1. Uang Muka (DP) — <?= esc(format_rupiah($dp)); ?></h6>
                                <?php if (($pengajuan['pembayaran_status'] ?? '') === 'terverifikasi' || ($buktiDp && $buktiDp['status'] === 'terverifikasi')): ?>
                                    <div class="alert alert-success mb-1">Uang Muka (DP) terverifikasi. Terima kasih!</div>
                                    <?php if ($buktiDp): ?>
                                        <a href="<?= base_url('/akun/bukti/' . $buktiDp['id']); ?>" target="_blank" rel="noopener" class="small">Lihat bukti DP</a>
                                    <?php endif; ?>
                                <?php elseif ($buktiDp && $buktiDp['status'] === 'menunggu'): ?>
                                    <div class="alert alert-warning mb-1">Bukti DP sedang menunggu verifikasi admin.</div>
                                    <a href="<?= base_url('/akun/bukti/' . $buktiDp['id']); ?>" target="_blank" rel="noopener" class="small">Lihat bukti DP</a>
                                <?php else: ?>
                                    <?php if ($buktiDp && $buktiDp['status'] === 'ditolak'): ?>
                                        <div class="alert alert-danger">Bukti DP ditolak<?= $buktiDp['catatan_admin'] ? ': ' . esc($buktiDp['catatan_admin']) : ''; ?>. Silakan unggah ulang.</div>
                                    <?php endif; ?>
                                    <p class="text-muted-mg">Bayar uang muka <strong><?= esc(format_rupiah($dp)); ?></strong> lalu unggah
                                        bukti transfer Anda (JPG/PNG/PDF, maks 3 MB).</p>
                                    <form action="<?= base_url('/akun/pesanan/' . $pengajuan['id'] . '/bukti-dp'); ?>" method="post"
                                        enctype="multipart/form-data">
                                        <?= csrf_field(); ?>
                                        <div class="row g-2 mb-3">
                                            <div class="col-md-4">
                                                <label class="form-label small">Nama Pengirim <span class="text-muted-mg">(opsional)</span></label>
                                                <input type="text" name="nama_pengirim" class="form-control" maxlength="150"
                                                    value="<?= esc(old('nama_pengirim')); ?>" placeholder="Nama di rekening">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">No. Rekening <span class="text-muted-mg">(opsional)</span></label>
                                                <input type="text" name="no_rekening" class="form-control" maxlength="50"
                                                    value="<?= esc(old('no_rekening')); ?>" placeholder="mis. 1234567890">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Bank <span class="text-muted-mg">(opsional)</span></label>
                                                <input type="text" name="bank_pengirim" class="form-control" maxlength="50"
                                                    value="<?= esc(old('bank_pengirim')); ?>" placeholder="mis. BCA / BRI">
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2 align-items-center flex-wrap">
                                            <input type="file" name="bukti" class="form-control" accept="image/jpeg,image/png,application/pdf"
                                                required style="max-width:280px;">
                                            <button class="btn btn-gold">Upload Bukti DP</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php // ---- Cicilan angsuran ---- ?>
                        <h6 class="fw-bold mb-2"><?= $dp > 0 ? '2. ' : ''; ?>Angsuran</h6>
                        <p class="text-muted-mg">Jadwal angsuran &amp; upload bukti per angsuran ada di halaman detail kredit.</p>
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
                                enctype="multipart/form-data">
                                <?= csrf_field(); ?>
                                <div class="row g-2 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label small">Nama Pengirim <span class="text-muted-mg">(opsional)</span></label>
                                        <input type="text" name="nama_pengirim" class="form-control" maxlength="150"
                                            value="<?= esc(old('nama_pengirim')); ?>" placeholder="Nama di rekening">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">No. Rekening <span class="text-muted-mg">(opsional)</span></label>
                                        <input type="text" name="no_rekening" class="form-control" maxlength="50"
                                            value="<?= esc(old('no_rekening')); ?>" placeholder="mis. 1234567890">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Bank <span class="text-muted-mg">(opsional)</span></label>
                                        <input type="text" name="bank_pengirim" class="form-control" maxlength="50"
                                            value="<?= esc(old('bank_pengirim')); ?>" placeholder="mis. BCA / BRI">
                                    </div>
                                </div>
                                <div class="d-flex gap-2 align-items-center flex-wrap">
                                    <input type="file" name="bukti" class="form-control" accept="image/jpeg,image/png,application/pdf"
                                        required style="max-width:280px;">
                                    <button class="btn btn-gold">Upload Bukti</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection(); ?>
