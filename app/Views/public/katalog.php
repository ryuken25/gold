<?= $this->extend('layouts/public'); ?>

<?= $this->section('content'); ?>
<section class="page-hero">
    <div class="container-mg">
        <div class="breadcrumb-mg"><a href="<?= base_url('/'); ?>">Beranda</a><span>/</span><span>Katalog</span></div>
        <span class="mg-badge px-3 py-2 mb-3">Katalog Publik</span>
        <h1 class="fw-black mb-3">Katalog Emas MahenGold</h1>
        <p class="text-light-emphasis mb-0 col-lg-7">Pilih produk emas, cek informasi pembelian, lalu ajukan minat
            melalui WhatsApp MahenGold.</p>
    </div>
</section>

<section class="catalog-filter">
    <div class="container-mg">
        <form class="filter-shell row g-3 align-items-center" method="get">
            <div class="col-lg-6">
                <label class="form-label small mb-1">Cari Produk</label>
                <input type="search" class="form-control form-control-lg" name="q"
                    value="<?= esc(service('request')->getGet('q')); ?>"
                    placeholder="Cari nama, kode, jenis, atau kadar emas">
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label small mb-1">Jenis</label>
                <select class="form-select form-select-lg" name="jenis">
                    <option value="">Semua Jenis</option>
                    <option value="Perhiasan" <?= service('request')->getGet('jenis') === 'Perhiasan' ? 'selected' : ''; ?>>Perhiasan</option>
                    <option value="Logam Mulia" <?= service('request')->getGet('jenis') === 'Logam Mulia' ? 'selected' : ''; ?>>Logam Mulia</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-3 d-grid d-md-flex gap-2 align-self-end catalog-filter-actions">
                <button class="btn btn-gold flex-fill">Filter</button>
                <a href="<?= base_url('/katalog'); ?>" class="btn btn-outline-gold flex-fill">Reset</a>
            </div>
        </form>
    </div>
</section>

<section class="section-padding bg-cream-soft pt-5">
    <div class="container-mg">
        <?php if ($produk): ?>
            <div class="product-grid">
                <?php $catalogImages = ['product-ring.jpg', 'product-necklace.jpg', 'product-earrings.jpg', 'gold-bars.jpg']; ?>
                <?php foreach ($produk as $index => $item): ?>
                    <?php $imageName = $catalogImages[$index % count($catalogImages)]; ?>
                    <article class="product-card">
                        <div class="product-visual image-visual">
                            <img src="<?= base_url('assets/images/mahengold/' . $imageName); ?>"
                                alt="<?= esc($item['nama_produk']); ?>" loading="lazy">
                        </div>
                        <div class="product-content">
                            <div class="d-flex justify-content-between gap-3 align-items-start mb-3">
                                <div class="min-w-0">
                                    <div class="product-code"><?= esc($item['kode_produk']); ?></div>
                                    <h5 class="fw-bold mb-1"><?= esc($item['nama_produk']); ?></h5>
                                </div>
                                <span class="stock-badge"><?= esc($item['stok']); ?> stok</span>
                            </div>
                            <p class="text-muted-mg mb-3"><?= esc($item['jenis_emas']); ?> · <?= esc($item['kadar']); ?> ·
                                <?= esc(format_angka($item['berat_gram'], 2)); ?> gram
                            </p>
                            <div class="mb-3 catalog-price-list">
                                <div class="price-row"><span>Harga
                                        Pokok</span><strong><?= esc(format_rupiah($item['harga_pokok'])); ?></strong></div>
                                <div class="price-row"><span>Info Cicilan</span><strong>Tersedia</strong>
                                </div>
                            </div>
                            <div class="simulation-box mb-3">
                                <div class="simulation-row"><span>Estimasi bulanan
                                        bulan</span><strong><?= esc(format_rupiah($item['simulasi_bulanan']['nominal_angsuran'])); ?></strong>
                                </div>
                                <div class="simulation-row"><span>Estimasi mingguan
                                        minggu</span><strong><?= esc(format_rupiah($item['simulasi_mingguan']['nominal_angsuran'])); ?></strong>
                                </div>
                            </div>
                            <div class="product-actions">
                                <a href="<?= base_url('/produk/' . $item['kode_produk']); ?>"
                                    class="btn btn-outline-gold">Detail</a>
                                <button type="button" class="btn btn-whatsapp js-open-wa-modal" data-bs-toggle="modal"
                                    data-bs-target="#waPengajuanModal" data-produk-id="<?= esc($item['id']); ?>"
                                    data-kode="<?= esc($item['kode_produk']); ?>" data-nama="<?= esc($item['nama_produk']); ?>"
                                    data-link="<?= base_url('/produk/' . $item['kode_produk']); ?>">Ajukan via WhatsApp</button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <?= view('partials/empty_state', ['title' => 'Produk belum tersedia']); ?>
        <?php endif; ?>
    </div>
</section>

<?= $this->include('public/partials/wa_modal'); ?>
<?= $this->endSection(); ?>