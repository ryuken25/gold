<header class="admin-header px-3 px-lg-4 py-3">
    <div class="d-flex gap-3 justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3 min-w-0">
            <button type="button" class="btn btn-outline-gold admin-mobile-toggle px-3" data-admin-sidebar-toggle
                aria-label="Buka menu admin">
                <i class="bi bi-list"></i>
            </button>
            <div class="min-w-0">
                <h1 class="h4 fw-black mb-1 text-truncate"><?= esc($pageTitle ?? 'Dashboard'); ?></h1>
                <p class="text-muted-mg mb-0 d-none d-sm-block">Monitoring penjualan dan kredit emas MahenGold.</p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="text-end admin-user-meta">
                <div class="fw-semibold"><?= esc($admin['nama'] ?? 'Admin'); ?></div>
                <small class="text-muted-mg"><?= esc($admin['username'] ?? 'admin'); ?></small>
            </div>
            <form action="<?= base_url('/admin/logout'); ?>" method="post" class="mb-0">
                <?= csrf_field(); ?>
                <button type="submit" class="btn btn-outline-gold px-3">Logout</button>
            </form>
        </div>
    </div>
</header>