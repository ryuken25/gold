<?= $this->extend('layouts/public'); ?>

<?= $this->section('content'); ?>
<?php $waNumber = $pengaturan['nomor_whatsapp_toko'] ?? '6282146575233'; ?>

<section class="hero-section hero-clean">
    <div class="container-mg">
        <div class="row align-items-center g-4 g-xl-5">
            <div class="col-lg-7">
                <span class="mg-badge px-3 py-2 mb-3">Penjualan &amp; Kredit Emas Tepercaya</span>
                <h1 class="fw-black mb-4">MahenGold</h1>
                <p class="lead text-light-emphasis mb-4">Pilih koleksi emas terbaik, ajukan kredit dengan mudah, dan
                    dapatkan layanan jual-beli emas yang jelas &amp; terpercaya.</p>
                <div class="hero-cta mb-4">
                    <a href="<?= base_url('/katalog'); ?>" class="btn btn-gold btn-lg px-4">Lihat Katalog</a>
                    <a href="https://wa.me/<?= esc($waNumber); ?>" target="_blank" rel="noopener"
                        class="btn btn-whatsapp btn-lg px-4">Ajukan via WhatsApp</a>
                </div>
                <p class="hero-note small mb-0">Pengajuan dilakukan langsung melalui WhatsApp MahenGold.</p>
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
                            ['icon' => 'icon-whatsapp.svg', 'title' => 'Ajukan WhatsApp', 'desc' => 'Lanjutkan pengajuan melalui chat.'],
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
                ['title' => 'Ajukan dengan Lebih Praktis', 'desc' => 'Setelah menemukan produk yang cocok, pengajuan dapat langsung dilanjutkan melalui WhatsApp.'],
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
                <p class="text-muted-mg mb-0">Lihat detail produk lalu ajukan langsung melalui WhatsApp.</p>
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
                            <a href="https://wa.me/<?= esc($waNumber); ?>" target="_blank" rel="noopener"
                                class="btn btn-whatsapp">WhatsApp</a>
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
            <h2 class="fw-black mb-0">Dari katalog ke WhatsApp dalam beberapa langkah</h2>
        </div>
        <div class="row g-3 g-lg-4">
            <?php foreach ([
                ['title' => 'Lihat Produk', 'desc' => 'Telusuri katalog emas MahenGold.'],
                ['title' => 'Pilih Detail', 'desc' => 'Buka produk yang diminati.'],
                ['title' => 'Isi Data', 'desc' => 'Masukkan nama dan alamat.'],
                ['title' => 'Kirim WhatsApp', 'desc' => 'Lanjutkan pengajuan melalui chat.'],
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
<?= $this->endSection(); ?>