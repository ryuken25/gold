<?= $this->extend('layouts/public'); ?>

<?= $this->section('content'); ?>
<section class="section-padding bg-cream-soft akun-section">
    <div class="container-mg">
        <div class="section-heading mb-4">
            <p class="section-eyebrow">Akun Saya</p>
            <h2 class="fw-black mb-1">Riwayat Pesanan</h2>
            <p class="text-muted-mg mb-0">Daftar pengajuan dan pesanan yang pernah Anda buat.</p>
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-lg-3">
                <?= $this->include('public/akun/_nav'); ?>
            </div>

            <div class="col-lg-9">
                <div class="feature-card p-4">
                    <?php if (empty($pengajuan)): ?>
                        <?= view('partials/empty_state', [
                            'title' => 'Belum ada pesanan',
                            'description' => 'Telusuri katalog lalu ajukan pembelian emas favorit Anda.',
                        ]); ?>
                        <div class="text-center">
                            <a href="<?= base_url('/katalog'); ?>" class="btn btn-gold px-4">Lihat Katalog</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Produk</th>
                                        <th>Metode</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pengajuan as $i => $p): ?>
                                        <tr class="clickable-row" onclick="window.location='<?= base_url('/akun/pesanan/' . $p['id']); ?>'" style="cursor:pointer;">
                                            <td><?= $i + 1; ?></td>
                                            <td>
                                                <span class="fw-semibold d-block"><?= esc($p['nama_produk'] ?? 'Produk'); ?></span>
                                                <small class="text-muted-mg"><?= esc($p['kode_produk'] ?? ''); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $p['metode_pembayaran'] === 'kredit' ? 'warning' : 'info'; ?>">
                                                    <?= esc(ucfirst($p['metode_pembayaran'])); ?>
                                                </span>
                                            </td>
                                            <td><?= esc(format_tanggal($p['created_at'], 'd M Y')); ?></td>
                                            <td>
                                                <span class="badge bg-<?= esc(pesanan_badge_class($p['status'], $p['metode_pembayaran'], (int)($p['uang_muka'] ?? 0), $p['pembayaran_status'] ?? 'belum')); ?>">
                                                    <?= esc(pesanan_status_label($p['status'], $p['metode_pembayaran'], (int)($p['uang_muka'] ?? 0), $p['pembayaran_status'] ?? 'belum')); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection(); ?>
