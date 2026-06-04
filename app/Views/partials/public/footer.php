<?php $waNumber = $pengaturan['nomor_whatsapp_toko'] ?? '6282146575233'; ?>
<footer class="public-footer section-padding" id="kontak">
    <div class="container-mg">
        <div class="row g-4 g-lg-5 align-items-start">
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <img class="brand-logo-img" src="<?= base_url('assets/images/mahengold/logo-mg.svg'); ?>"
                        alt="MahenGold logo" width="52" height="52">
                    <div>
                        <h5 class="mb-0 fw-black">
                            <?= esc($pengaturan['nama_toko'] ?? 'MahenGold'); ?>
                        </h5>
                        <span class="brand-subtext">Premium Credit System</span>
                    </div>
                </div>
                <p class="mb-4">Katalog emas modern dengan pengajuan cepat langsung dari sistem MahenGold.
                </p>
                <a class="btn btn-whatsapp px-4" href="https://wa.me/<?= esc($waNumber); ?>" target="_blank"
                    rel="noopener">Hubungi WhatsApp</a>
            </div>
            <div class="col-6 col-lg-3">
                <h6 class="fw-bold mb-3">Menu</h6>
                <div class="d-grid gap-2">
                    <a href="<?= base_url('/'); ?>">Beranda</a>
                    <a href="<?= base_url('/katalog'); ?>">Katalog</a>
                    <a href="<?= base_url('/#alur-pengajuan'); ?>">Cara Pengajuan</a>
                    <a href="<?= base_url('/#kontak'); ?>">Kontak</a>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <h6 class="fw-bold mb-3">Kontak</h6>
                <div class="d-grid gap-2">
                    <span>WhatsApp:
                        <?= esc($waNumber); ?>
                    </span>
                    <span>MahenGold</span>
                    <span>Gold Credit Store</span>
                </div>
            </div>
        </div>
        <div class="border-top border-warning-subtle mt-5 pt-4 small">
            <span>©
                <?= date('Y'); ?>
                <?= esc($pengaturan['nama_toko'] ?? 'MahenGold'); ?>. All rights reserved.
            </span>
        </div>
    </div>
</footer>