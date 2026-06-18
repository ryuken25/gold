<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="premium-card p-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <div>
            <h5 class="fw-bold mb-1">Pengajuan</h5>
            <p class="text-muted small mb-0">Klik baris untuk membuka detail.</p>
        </div>
    </div>

    <!-- Filter Chips -->
    <div class="d-flex gap-2 mb-4 flex-wrap">
        <?php
        $chips = [
            ''       => ['label' => 'Semua',              'icon' => 'bi-list-ul'],
            'perlu'  => ['label' => 'Perlu Diverifikasi', 'icon' => 'bi-clock-history', 'count' => $counts['perlu'] ?? 0],
            'proses' => ['label' => 'Terverifikasi',      'icon' => 'bi-check2-circle', 'count' => $counts['proses'] ?? 0],
            'ditolak'=> ['label' => 'Ditolak',            'icon' => 'bi-x-circle',      'count' => $counts['ditolak'] ?? 0],
        ];
        foreach ($chips as $val => $chip):
            $isActive = $bucket === $val;
        ?>
            <a href="<?= base_url('/admin/pengajuan' . ($val !== '' ? '?bucket=' . $val : '')); ?>"
               class="btn btn-sm <?= $isActive ? 'btn-gold' : 'btn-outline-gold'; ?> rounded-pill px-3">
                <i class="bi <?= esc($chip['icon']); ?>"></i> <?= esc($chip['label']); ?>
                <?php if (isset($chip['count']) && $chip['count'] > 0): ?>
                    <span class="badge bg-<?= $isActive ? 'dark' : 'secondary'; ?> rounded-pill ms-1"><?= esc($chip['count']); ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($pengajuan)): ?>
        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Produk</th>
                        <th>Metode</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pengajuan as $row): ?>
                        <tr class="clickable-row" data-href="<?= base_url('/admin/pengajuan/' . $row['id']); ?>" tabindex="0" role="link">
                            <td><span class="fw-semibold"><?= esc($row['kode_pesanan'] ?? '-'); ?></span></td>
                            <td class="text-nowrap"><?= esc(format_tanggal($row['created_at'], 'd M Y H:i')); ?></td>
                            <td>
                                <span class="fw-semibold d-block"><?= esc($row['nama']); ?></span>
                                <small class="text-muted-mg"><?= esc($row['email_user'] ?? '-'); ?></small>
                            </td>
                            <td>
                                <span class="d-block"><?= esc($row['nama_produk'] ?? '-'); ?></span>
                                <small class="text-muted-mg"><?= esc($row['kode_produk'] ?? ''); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-<?= $row['metode_pembayaran'] === 'kredit' ? 'warning' : 'info'; ?>">
                                    <?= esc(ucfirst($row['metode_pembayaran'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= esc(pesanan_badge_class($row['status'], $row['metode_pembayaran'], (int)($row['uang_muka'] ?? 0), $row['pembayaran_status'] ?? 'belum')); ?>">
                                    <?= esc(pesanan_status_label($row['status'], $row['metode_pembayaran'], (int)($row['uang_muka'] ?? 0), $row['pembayaran_status'] ?? 'belum')); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <?= view('partials/empty_state', ['title' => 'Belum ada pengajuan']); ?>
    <?php endif; ?>
</div>
<?= $this->endSection(); ?>
