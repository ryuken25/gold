<aside class="admin-sidebar" id="adminSidebar">
    <div class="p-4 border-bottom border-dark-subtle">
        <a href="<?= base_url('/admin/dashboard'); ?>"
            class="d-flex align-items-center gap-3 text-decoration-none text-white">
            <span class="brand-mark">
                <?= esc($pengaturan['logo_text'] ?? 'MG'); ?>
            </span>
            <div>
                <div class="fw-bold">
                    <?= esc($pengaturan['nama_toko'] ?? 'MahenGold'); ?>
                </div>
                <small class="text-gold-soft">Admin Console</small>
            </div>
        </a>
    </div>
    <div class="p-3">
        <?php
        $menu = [
            ['icon' => 'bi-grid', 'label' => 'Dashboard', 'url' => '/admin/dashboard', 'segment' => 'admin/dashboard'],
            ['icon' => 'bi-inbox', 'label' => 'Pengajuan', 'url' => '/admin/pengajuan', 'segment' => 'admin/pengajuan', 'badge' => ($pengajuanBaru ?? 0)],
            ['icon' => 'bi-arrow-left-right', 'label' => 'Transaksi', 'url' => '/admin/transaksi', 'segment' => 'admin/transaksi'],
            ['icon' => 'bi-gem', 'label' => 'Produk', 'url' => '/admin/produk', 'segment' => 'admin/produk'],
            ['icon' => 'bi-person-badge', 'label' => 'Pelanggan', 'url' => '/admin/pelanggan', 'segment' => 'admin/pelanggan'],
            ['icon' => 'bi-people', 'label' => 'Nasabah', 'url' => '/admin/nasabah', 'segment' => 'admin/nasabah'],
            ['icon' => 'bi-receipt', 'label' => 'Detail Kredit', 'url' => '/admin/kredit', 'segment' => 'admin/kredit'],
            ['icon' => 'bi-cash-coin', 'label' => 'Verifikasi Bayar', 'url' => '/admin/pembayaran', 'segment' => 'admin/pembayaran'],
            ['icon' => 'bi-file-earmark-text', 'label' => 'Laporan', 'url' => '/admin/laporan/kredit', 'segment' => 'admin/laporan'],
        ];
        ?>
        <nav class="nav flex-column gap-1">
            <?php foreach ($menu as $item): ?>
                <a class="admin-nav-link <?= is_active_menu($item['segment']) ? 'active' : ''; ?>"
                    href="<?= base_url($item['url']); ?>">
                    <i class="bi <?= esc($item['icon']); ?>"></i>
                    <span>
                        <?= esc($item['label']); ?>
                    </span>
                    <?php if (!empty($item['badge'])): ?>
                        <span class="badge bg-danger rounded-pill ms-auto"><?= esc($item['badge']); ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</aside>