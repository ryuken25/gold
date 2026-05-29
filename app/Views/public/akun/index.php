<?= $this->extend('layouts/public'); ?>

<?= $this->section('content'); ?>
<section class="section-padding bg-cream-soft" style="min-height: 70vh;">
    <div class="container-mg">
        <div class="section-heading mb-4">
            <p class="section-eyebrow">Akun Saya</p>
            <h2 class="fw-black mb-1">Halo, <?= esc($pelanggan['nama']); ?>!</h2>
            <p class="text-muted-mg mb-0"><?= esc($pelanggan['email']); ?></p>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="feature-card p-4">
                    <h5 class="fw-bold mb-3">Riwayat Pengajuan</h5>
                    <?php if (empty($pengajuan)): ?>
                        <p class="text-muted-mg mb-0">Belum ada pengajuan. <a href="<?= base_url('/katalog'); ?>">Lihat katalog</a></p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Metode</th>
                                        <th>Nama</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pengajuan as $i => $p): ?>
                                        <tr>
                                            <td><?= $i + 1; ?></td>
                                            <td><?= esc(ucfirst($p['metode_pembayaran'])); ?></td>
                                            <td><?= esc($p['nama']); ?></td>
                                            <td>
                                                <span class="badge bg-<?= status_badge_class($p['status']); ?>">
                                                    <?= esc(ucfirst($p['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?= esc(format_tanggal($p['created_at'], 'd M Y')); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="feature-card p-4">
                    <h5 class="fw-bold mb-3">Aksi</h5>
                    <a href="<?= base_url('/katalog'); ?>" class="btn btn-gold w-100 mb-2">Lihat Katalog</a>
                    <form action="<?= base_url('/logout'); ?>" method="post">
                        <?= csrf_field(); ?>
                        <button type="submit" class="btn btn-outline-secondary w-100">Keluar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection(); ?>
