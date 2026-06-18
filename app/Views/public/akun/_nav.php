<?php
/**
 * Sidebar navigasi area akun pelanggan.
 * Membutuhkan: $activeTab (dashboard|pesanan|profil), $pelanggan
 */
$tab = $activeTab ?? 'dashboard';
$items = [
    'dashboard' => ['label' => 'Dashboard', 'url' => base_url('/akun'), 'icon' => '🏠'],
    'pesanan'   => ['label' => 'Pesanan',   'url' => base_url('/akun/pesanan'), 'icon' => '🧾'],
    'kredit'    => ['label' => 'Kredit',    'url' => base_url('/akun/kredit'), 'icon' => '💳'],
    'profil'    => ['label' => 'Profil',     'url' => base_url('/akun/profil'), 'icon' => '👤'],
];
?>
<aside class="akun-sidebar feature-card p-0">
    <div class="akun-sidebar-head">
        <div class="akun-avatar"><?= esc(strtoupper(mb_substr($pelanggan['nama'] ?? 'M', 0, 1))); ?></div>
        <div class="min-w-0">
            <strong class="d-block text-truncate"><?= esc($pelanggan['nama'] ?? ''); ?></strong>
            <small class="text-muted-mg d-block text-truncate"><?= esc($pelanggan['email'] ?? ''); ?></small>
        </div>
    </div>
    <nav class="akun-nav">
        <?php foreach ($items as $key => $item): ?>
            <a class="akun-nav-link <?= $tab === $key ? 'is-active' : ''; ?>" href="<?= esc($item['url']); ?>">
                <span class="akun-nav-icon" aria-hidden="true"><?= $item['icon']; ?></span>
                <?= esc($item['label']); ?>
            </a>
        <?php endforeach; ?>
        <form action="<?= base_url('/logout'); ?>" method="post" class="akun-nav-logout">
            <?= csrf_field(); ?>
            <button type="submit" class="akun-nav-link akun-nav-danger">
                <span class="akun-nav-icon" aria-hidden="true">↩</span> Keluar
            </button>
        </form>
    </nav>
</aside>
