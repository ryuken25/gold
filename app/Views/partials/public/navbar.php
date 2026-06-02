<?php $waNumber = $pengaturan['nomor_whatsapp_toko'] ?? '6282146575233'; ?>
<nav class="public-navbar sticky-top" aria-label="Navigasi utama MahenGold">
    <div class="container-mg public-nav-inner">
        <button class="public-menu-toggle" type="button" data-public-drawer-toggle aria-label="Buka menu">
            <span></span><span></span><span></span>
        </button>

        <a class="public-brand" href="<?= base_url('/'); ?>" aria-label="MahenGold beranda">
            <img class="brand-logo-img" src="<?= base_url('assets/images/mahengold/logo-mg.svg'); ?>"
                alt="MahenGold logo" width="48" height="48">
            <span>
                <span class="brand-text"><?= esc($pengaturan['nama_toko'] ?? 'MahenGold'); ?></span>
                <span class="brand-subtext">Gold Credit Store</span>
            </span>
        </a>

        <div class="public-nav-desktop">
            <a class="nav-link <?= is_active_menu('') ? 'active' : ''; ?>" href="<?= base_url('/'); ?>">Beranda</a>
            <a class="nav-link <?= is_active_menu('katalog') ? 'active' : ''; ?>"
                href="<?= base_url('/katalog'); ?>">Katalog</a>
            <a class="nav-link" href="<?= base_url('/#alur-pengajuan'); ?>">Cara Pengajuan</a>
            <a class="nav-link" href="<?= base_url('/#kontak'); ?>">Kontak</a>
        </div>

        <div class="public-nav-actions">
            <a class="btn btn-gold px-4" href="<?= base_url('/katalog'); ?>">Lihat Produk</a>
            <?php if (is_pelanggan_logged_in()): ?>
                <div class="dropdown account-dropdown">
                    <button class="btn btn-outline-gold px-3 dropdown-toggle account-toggle" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="account-avatar" aria-hidden="true"><?= esc(strtoupper(mb_substr(current_pelanggan()['nama'], 0, 1))); ?></span>
                        <span class="account-name d-none d-xl-inline"><?= esc(current_pelanggan()['nama']); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end account-menu shadow">
                        <li><a class="dropdown-item" href="<?= base_url('/akun'); ?>">🏠 Dashboard</a></li>
                        <li><a class="dropdown-item" href="<?= base_url('/akun/pesanan'); ?>">🧾 Pesanan</a></li>
                        <li><a class="dropdown-item" href="<?= base_url('/akun/profil'); ?>">👤 Profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="<?= base_url('/logout'); ?>" method="post" class="d-inline">
                                <?= csrf_field(); ?>
                                <button type="submit" class="dropdown-item text-danger">↩ Keluar</button>
                            </form>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <a class="btn btn-outline-gold px-4 nav-cta" href="<?= base_url('/login'); ?>">Masuk</a>
                <a class="btn btn-whatsapp px-4 nav-cta" href="<?= base_url('/register'); ?>">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="public-drawer-backdrop" data-public-drawer-close></div>
<aside class="public-drawer" id="publicDrawer" aria-hidden="true">
    <div class="public-drawer-header">
        <a class="public-brand" href="<?= base_url('/'); ?>">
            <img class="brand-logo-img" src="<?= base_url('assets/images/mahengold/logo-mg.svg'); ?>"
                alt="MahenGold logo" width="48" height="48">
            <span>
                <span class="brand-text"><?= esc($pengaturan['nama_toko'] ?? 'MahenGold'); ?></span>
                <span class="brand-subtext">Gold Credit Store</span>
            </span>
        </a>
        <button class="public-drawer-close" type="button" data-public-drawer-close aria-label="Tutup menu">×</button>
    </div>
    <div class="public-drawer-menu">
        <a class="drawer-link <?= is_active_menu('') ? 'active' : ''; ?>" href="<?= base_url('/'); ?>">Beranda</a>
        <a class="drawer-link <?= is_active_menu('katalog') ? 'active' : ''; ?>"
            href="<?= base_url('/katalog'); ?>">Katalog</a>
        <a class="drawer-link" href="<?= base_url('/#alur-pengajuan'); ?>">Cara Pengajuan</a>
        <a class="drawer-link" href="<?= base_url('/#kontak'); ?>">Kontak</a>
    </div>
    <div class="public-drawer-actions">
        <a class="btn btn-gold w-100" href="<?= base_url('/katalog'); ?>">Lihat Produk</a>
        <?php if (is_pelanggan_logged_in()): ?>
            <div class="drawer-account">
                <span class="account-avatar" aria-hidden="true"><?= esc(strtoupper(mb_substr(current_pelanggan()['nama'], 0, 1))); ?></span>
                <span class="min-w-0">
                    <strong class="d-block text-truncate"><?= esc(current_pelanggan()['nama']); ?></strong>
                    <small class="d-block text-truncate"><?= esc(current_pelanggan()['email']); ?></small>
                </span>
            </div>
            <a class="btn btn-outline-gold w-100" href="<?= base_url('/akun'); ?>">Dashboard</a>
            <a class="btn btn-outline-gold w-100" href="<?= base_url('/akun/pesanan'); ?>">Pesanan</a>
            <a class="btn btn-outline-gold w-100" href="<?= base_url('/akun/profil'); ?>">Profil</a>
            <form action="<?= base_url('/logout'); ?>" method="post">
                <?= csrf_field(); ?>
                <button type="submit" class="btn btn-outline-secondary w-100">Keluar</button>
            </form>
        <?php else: ?>
            <a class="btn btn-outline-gold w-100" href="<?= base_url('/login'); ?>">Masuk</a>
            <a class="btn btn-whatsapp w-100" href="<?= base_url('/register'); ?>">Daftar</a>
        <?php endif; ?>
    </div>
</aside>