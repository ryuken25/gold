<?= $this->extend('layouts/public'); ?>

<?= $this->section('content'); ?>

<section class="hero-section hero-clean">
    <div class="container-mg">
        <div class="row align-items-center g-4 g-xl-5">
            <div class="col-lg-7">
                <span class="mg-badge px-3 py-2 mb-3">Penjualan &amp; Kredit Emas Terpercaya</span>
                <h1 class="fw-black mb-4">MahenGold</h1>
                <p class="lead text-light-emphasis mb-4">Pilih koleksi emas terbaik, ajukan kredit dengan mudah, dan
                    dapatkan layanan jual-beli emas yang jelas &amp; terpercaya.</p>
                <div class="hero-cta mb-4">
                    <a href="<?= base_url('/katalog'); ?>" class="btn btn-outline-gold btn-lg px-4">Lihat Katalog</a>
                    <?php if (is_pelanggan_logged_in()): ?>
                        <a href="<?= base_url('/katalog'); ?>" class="btn btn-gold btn-lg px-4">Pesan Sekarang</a>
                    <?php else: ?>
                        <a href="<?= base_url('/login'); ?>" class="btn btn-gold btn-lg px-4">Masuk untuk
                            Memesan</a>
                    <?php endif; ?>
                </div>
                <p class="hero-note small mb-0">Pesanan diproses langsung di sistem MahenGold.</p>
            </div>
            <div class="col-lg-5">
                <div class="hero-process-card glass-card p-4 p-lg-5">
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                        <div>
                            <p class="text-uppercase text-gold-soft small fw-bold mb-2">Untuk pelanggan</p>
                            <h3 class="text-white fw-bold mb-0">Cara Mudah Mengajukan</h3>
                        </div>
                        <img class="hero-mini-logo" src="<?= base_url('assets/images/mahengold/logo-mg.svg'); ?>"
                            alt="MahenGold" width="76" height="76">
                    </div>
                    <div class="hero-step-grid">
                        <?php foreach ([
                            ['icon' => 'icon-catalog.svg', 'title' => 'Lihat Produk', 'desc' => 'Telusuri katalog emas yang tersedia.'],
                            ['icon' => 'icon-selected-product.svg', 'title' => 'Pilih Produk', 'desc' => 'Pilih produk yang sesuai kebutuhan.'],
                            ['icon' => 'icon-form.svg', 'title' => 'Isi Data', 'desc' => 'Masukkan nama dan alamat singkat.'],
                            ['icon' => 'icon-order.svg', 'title' => 'Pesan di Sistem', 'desc' => 'Buat pesanan langsung tanpa chat.'],
                        ] as $feature): ?>
                            <div class="hero-step-item">
                                <span class="feature-icon svg-icon"><img
                                        src="<?= base_url('assets/icons/mahengold/' . $feature['icon']); ?>"
                                        alt="<?= esc($feature['title']); ?>"></span>
                                <div>
                                    <strong>
                                        <?= esc($feature['title']); ?>
                                    </strong>
                                    <small>
                                        <?= esc($feature['desc']); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding bg-cream-soft">
    <div class="container-mg">
        <div class="section-heading text-center mx-auto mb-5">
            <p class="section-eyebrow">Kenapa MahenGold</p>
            <h2 class="fw-black mb-3">Cara yang lebih nyaman untuk memilih emas</h2>
            <p class="text-muted-mg mb-0">MahenGold menghadirkan pengalaman memilih emas yang lebih rapi, praktis, dan
                mudah dipahami.</p>
        </div>
        <div class="row g-3 g-lg-4 justify-content-center">
            <?php foreach ([
                ['title' => 'Pilih dengan Lebih Yakin', 'desc' => 'Koleksi emas ditampilkan lebih jelas agar setiap pilihan terasa nyaman untuk dipertimbangkan.'],
                ['title' => 'Pesan dengan Lebih Praktis', 'desc' => 'Setelah menemukan produk yang cocok, pesanan langsung diproses di sistem.'],
                ['title' => 'Informasi Lebih Ringkas', 'desc' => 'Detail penting disusun sederhana agar pelanggan lebih mudah memahami pilihan produk.'],
            ] as $item): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card benefit-card">
                        <span class="benefit-mark"></span>
                        <h5 class="fw-bold mb-2">
                            <?= esc($item['title']); ?>
                        </h5>
                        <p class="text-muted-mg mb-0">
                            <?= esc($item['desc']); ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-padding bg-white">
    <div class="container-mg">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
            <div class="section-heading">
                <p class="section-eyebrow">Produk Pilihan</p>
                <h2 class="fw-black mb-2">Pilihan emas terbaru MahenGold</h2>
                <p class="text-muted-mg mb-0">Lihat detail produk lalu pesan langsung di sistem.</p>
            </div>
            <a href="<?= base_url('/katalog'); ?>" class="btn btn-outline-gold px-4">Lihat Semua Produk</a>
        </div>
        <div class="product-grid product-grid-featured">
            <?php $featuredImages = ['product-ring.jpg', 'product-necklace.jpg', 'product-earrings.jpg']; ?>
            <?php foreach ($produkUnggulan as $index => $item): ?>
                <?php $imageName = $featuredImages[$index % count($featuredImages)]; ?>
                <article class="product-card product-card-simple">
                    <div class="product-visual image-visual"><img
                            src="<?= base_url('assets/images/mahengold/' . $imageName); ?>"
                            alt="<?= esc($item['nama_produk']); ?>" loading="lazy"></div>
                    <div class="product-content">
                        <div class="product-code mb-1">
                            <?= esc($item['kode_produk']); ?>
                        </div>
                        <h5 class="fw-bold mb-2">
                            <?= esc($item['nama_produk']); ?>
                        </h5>
                        <p class="text-muted-mg mb-3">
                            <?= esc($item['jenis_emas']); ?> ·
                            <?= esc($item['kadar']); ?> ·
                            <?= esc(format_angka($item['berat_gram'], 2)); ?> gram
                        </p>
                        <div class="price-row mb-3"><span>Harga Pokok</span><strong>
                                <?= esc(format_rupiah($item['harga_pokok'])); ?>
                            </strong></div>
                        <div class="product-actions">
                            <a href="<?= base_url('/produk/' . $item['kode_produk']); ?>"
                                class="btn btn-outline-gold">Detail</a>
                            <?php if (is_pelanggan_logged_in()): ?>
                                <button type="button" class="btn btn-gold js-open-wa-modal" data-bs-toggle="modal"
                                    data-bs-target="#waPengajuanModal" data-produk-id="<?= esc($item['id']); ?>"
                                    data-kode="<?= esc($item['kode_produk']); ?>" data-nama="<?= esc($item['nama_produk']); ?>"
                                    data-harga-pokok="<?= esc($item['harga_pokok']); ?>"
                                    data-link="<?= base_url('/produk/' . $item['kode_produk']); ?>">Pesan Sekarang</button>
                            <?php else: ?>
                                <a href="<?= base_url('/login?redirect=' . urlencode('/produk/' . $item['kode_produk'])); ?>"
                                    class="btn btn-gold">Masuk</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-padding bg-cream-soft" id="alur-pengajuan">
    <div class="container-mg">
        <div class="section-heading mx-auto text-center mb-5">
            <p class="section-eyebrow">Cara Pengajuan</p>
            <h2 class="fw-black mb-0">Dari katalog ke pesanan dalam beberapa langkah</h2>
        </div>
        <div class="row g-3 g-lg-4">
            <?php foreach ([
                ['title' => 'Lihat Produk', 'desc' => 'Telusuri katalog emas MahenGold.'],
                ['title' => 'Pilih Detail', 'desc' => 'Buka produk yang diminati.'],
                ['title' => 'Isi Data', 'desc' => 'Masukkan nama dan alamat.'],
                ['title' => 'Kirim Pesanan', 'desc' => 'Pesanan diverifikasi admin di sistem.'],
            ] as $index => $step): ?>
                <div class="col-md-6 col-xl-3">
                    <div class="step-card">
                        <span class="step-number">
                            <?= $index + 1; ?>
                        </span>
                        <h5 class="fw-bold mb-2">
                            <?= esc($step['title']); ?>
                        </h5>
                        <p class="text-muted-mg mb-0">
                            <?= esc($step['desc']); ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?= $this->include('public/partials/wa_modal'); ?>
<?= $this->endSection(); ?>