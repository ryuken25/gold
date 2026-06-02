<?= $this->extend('layouts/public'); ?>

<?= $this->section('content'); ?>
<section class="page-hero">
    <div class="container-mg">
        <div class="breadcrumb-mg"><a href="<?= base_url('/'); ?>">Beranda</a><span>/</span><a
                href="<?= base_url('/katalog'); ?>">Katalog</a><span>/</span><span><?= esc($produk['kode_produk']); ?></span>
        </div>
        <div class="row g-4 align-items-center">
            <div class="col-lg-7">
                <span class="mg-badge px-3 py-2 mb-3">Detail Produk</span>
                <h1 class="fw-black mb-3"><?= esc($produk['nama_produk']); ?></h1>
                <p class="text-light-emphasis mb-0"><?= esc($produk['jenis_emas']); ?> · <?= esc($produk['kadar']); ?> ·
                    <?= esc(format_angka($produk['berat_gram'], 2)); ?> gram
                </p>
            </div>
            <div class="col-lg-5">
                <div class="glass-card p-4">
                    <div class="detail-row"><span class="text-light-emphasis">Kode Produk</span><strong
                            class="text-white"><?= esc($produk['kode_produk']); ?></strong></div>
                    <div class="detail-row"><span class="text-light-emphasis">Harga Pokok</span><strong
                            class="text-white"><?= esc(format_rupiah($produk['harga_pokok'])); ?></strong></div>
                    <div class="detail-row"><span class="text-light-emphasis">Margin Default</span><strong
                            class="text-white"><?= esc(format_angka($marginDefault, 0)); ?>%</strong></div>
                    <div class="detail-row"><span class="text-light-emphasis">Estimasi 12 Bulan</span><strong
                            class="text-white"><?= esc(format_rupiah($simulasiDefault['nominal_angsuran'])); ?>/bulan</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding bg-cream-soft">
    <div class="container-mg">
        <div class="row g-4 g-lg-5 align-items-start">
            <div class="col-lg-6">
                <?php $detailImage = stripos($produk['nama_produk'] . ' ' . $produk['jenis_emas'], 'kalung') !== false ? 'product-necklace.jpg' : (stripos($produk['nama_produk'] . ' ' . $produk['jenis_emas'], 'anting') !== false ? 'product-earrings.jpg' : (stripos($produk['nama_produk'] . ' ' . $produk['jenis_emas'], 'logam') !== false ? 'gold-bars.jpg' : 'product-ring.jpg')); ?>
                <div class="product-visual image-visual detail-visual mb-4"><img
                        src="<?= base_url('assets/images/mahengold/' . $detailImage); ?>"
                        alt="<?= esc($produk['nama_produk']); ?>"></div>
                <div class="premium-card p-4">
                    <h4 class="fw-bold mb-3">Informasi Produk</h4>
                    <p class="text-muted-mg mb-4">
                        <?= esc($produk['deskripsi'] ?: 'Produk emas MahenGold dengan tampilan premium dan informasi pembelian yang mudah dipahami.'); ?>
                    </p>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="info-chip">Jenis<br><strong><?= esc($produk['jenis_emas']); ?></strong></div>
                        </div>
                        <div class="col-6">
                            <div class="info-chip">Kadar<br><strong><?= esc($produk['kadar']); ?></strong></div>
                        </div>
                        <div class="col-6">
                            <div class="info-chip">Berat<br><strong><?= esc(format_angka($produk['berat_gram'], 2)); ?>
                                    gram</strong></div>
                        </div>
                        <div class="col-6">
                            <div class="info-chip">Stok<br><strong><?= esc($produk['stok']); ?></strong></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="premium-card p-4 p-lg-5 sticky-lg-top preview-box">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-3 mb-4">
                        <div>
                            <p class="section-eyebrow mb-2">Estimasi Angsuran</p>
                            <h3 class="fw-black mb-2">Rincian Pengajuan</h3>
                            <p class="text-muted-mg mb-0">Nomor WhatsApp akan mengikuti nomor pengirim chat ini.</p>
                        </div>
                        <?php if (is_pelanggan_logged_in()): ?>
                            <button type="button" class="btn btn-whatsapp js-open-wa-modal" data-bs-toggle="modal"
                                data-bs-target="#waPengajuanModal" data-produk-id="<?= esc($produk['id']); ?>"
                                data-kode="<?= esc($produk['kode_produk']); ?>"
                                data-harga-pokok="<?= esc($produk['harga_pokok']); ?>"
                                data-nama="<?= esc($produk['nama_produk']); ?>">Ajukan via WhatsApp</button>
                        <?php else: ?>
                            <a href="<?= base_url('/login?redirect=' . urlencode('/produk/' . $produk['kode_produk'])); ?>"
                                class="btn btn-whatsapp">Masuk untuk Memesan</a>
                        <?php endif; ?>
                    </div>
                    <div class="simulation-box p-3 p-lg-4 mb-4">
                        <div class="simulation-row"><span>Total Harga
                                Kredit</span><strong><?= esc(format_rupiah($simulasiDefault['total_harga_kredit'])); ?></strong>
                        </div>
                        <div class="simulation-row"><span>Bulanan 12
                                bulan</span><strong><?= esc(format_rupiah($simulasiDefault['nominal_angsuran'])); ?>/bulan</strong>
                        </div>
                        <div class="simulation-row"><span>Mingguan 48
                                minggu</span><strong><?= esc(format_rupiah($simulasiMingguan['nominal_angsuran'])); ?>/minggu</strong>
                        </div>
                    </div>
                    <div class="tenor-info-row">
                        <span class="tenor-info-chip"><span class="tenor-info-label">Tenor tersedia</span>6 / 10 / 12
                            bulan</span>
                        <span class="tenor-info-chip"><span class="tenor-info-label">Periode</span>Bulanan /
                            Mingguan</span>
                    </div>
                    <p class="small text-muted-mg mt-3 mb-0">Pilih tenor dan periode angsuran saat membuka form Ajukan
                        via WhatsApp.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?= $this->include('public/partials/wa_modal'); ?>
<?= $this->endSection(); ?>