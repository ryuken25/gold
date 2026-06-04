<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="premium-card p-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <form method="get" class="d-flex gap-2">
            <select name="status" class="form-select">
                <option value="">Semua Status</option>
                <?php foreach ($statusList as $item): ?>
                    <option value="<?= esc($item); ?>" <?= $status === $item ? 'selected' : ''; ?>>
                        <?= esc(ucfirst($item)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-gold rounded-pill px-4">Filter</button>
        </form>
    </div>

    <?php if ($pengajuan): ?>
        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Produk</th>
                        <th>Metode</th>
                        <th>KTP</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pengajuan as $row): ?>
                        <tr>
                            <td><span class="fw-semibold"><?= esc($row['kode_pesanan'] ?? '-'); ?></span></td>
                            <td><?= esc(format_tanggal($row['created_at'], 'd M Y H:i')); ?></td>
                            <td>
                                <span class="fw-semibold d-block"><?= esc($row['nama']); ?></span>
                                <small class="text-muted-mg"><?= esc($row['email_user'] ?? '-'); ?></small>
                            </td>
                            <td>
                                <span class="d-block"><?= esc($row['nama_produk'] ?? '-'); ?></span>
                                <small class="text-muted-mg"><?= esc($row['kode_produk'] ?? ''); ?></small>
                            </td>
                            <td>
                                <span class="badge text-bg-<?= $row['metode_pembayaran'] === 'kredit' ? 'warning' : 'info'; ?>">
                                    <?= esc(ucfirst($row['metode_pembayaran'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($row['foto_ktp'])): ?>
                                    <i class="bi bi-check-circle-fill text-success" title="KTP terlampir"></i>
                                <?php else: ?>
                                    <span class="text-muted-mg">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge text-bg-<?= esc(status_badge_class($row['status'])); ?>">
                                    <?= esc(ucfirst($row['status'])); ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="<?= base_url('/admin/pengajuan/' . $row['id']); ?>"
                                    class="btn btn-sm btn-outline-gold rounded-pill">Detail</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <?= view('partials/empty_state', ['title' => 'Belum ada pengajuan masuk']); ?>
    <?php endif; ?>
</div>
<?= $this->endSection(); ?>
