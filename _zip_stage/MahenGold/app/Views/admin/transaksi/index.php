<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="premium-card p-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <div>
            <h5 class="fw-bold mb-1">Transaksi</h5>
            <p class="text-muted small mb-0">Semua transaksi cash & kredit dalam satu halaman.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('/admin/kredit/create'); ?>" class="btn btn-gold rounded-pill px-4">Buat Kredit</a>
        </div>
    </div>

    <!-- Filter Chips -->
    <div class="d-flex gap-2 mb-4 flex-wrap">
        <?php
        $chips = [
            ''       => ['label' => 'Semua',       'icon' => 'bi-list-ul'],
            'cash'   => ['label' => 'Cash',         'icon' => 'bi-cash-coin'],
            'kredit' => ['label' => 'Kredit',       'icon' => 'bi-credit-card'],
        ];
        foreach ($chips as $val => $chip):
            $isActive = $tipe === $val;
        ?>
            <a href="<?= base_url('/admin/transaksi' . ($val !== '' ? '?tipe=' . $val : '')); ?>"
               class="btn btn-sm <?= $isActive ? 'btn-gold' : 'btn-outline-gold'; ?> rounded-pill px-3">
                <i class="bi <?= esc($chip['icon']); ?>"></i> <?= esc($chip['label']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($rows)): ?>
        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Pelanggan</th>
                        <th>Produk</th>
                        <th>Metode</th>
                        <th>Total</th>
                        <th>Terbayar</th>
                        <th>Sisa</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $isKredit = ($row['tipe_transaksi'] ?? '') === 'kredit';
                        $rowClass = '';
                        $statusBadge = '';
                        $statusText = '';

                        if ($isKredit) {
                            $href = base_url('/admin/kredit/' . $row['id']);
                            if ($row['status'] === 'lunas') {
                                $rowClass = 'row-lunas';
                                $statusBadge = 'bg-success';
                                $statusText = 'Lunas';
                            } elseif ($row['status'] === 'aktif' && !empty($row['is_terlambat'])) {
                                $rowClass = 'row-overdue';
                                $statusBadge = 'bg-danger';
                                $statusText = 'Terlambat';
                            } elseif ($row['status'] === 'aktif') {
                                $rowClass = 'row-aktif';
                                $statusBadge = 'bg-primary';
                                $statusText = 'Aktif';
                            } else {
                                $rowClass = '';
                                $statusBadge = 'bg-secondary';
                                $statusText = ucfirst($row['status']);
                            }
                        } else {
                            $href = base_url('/admin/pengajuan/' . $row['id']);
                            $statusText = pesanan_status_label($row['status']);
                            $statusBadge = 'bg-' . pesanan_badge_class($row['status']);
                            // Cash row class based on status
                            if (in_array($row['status'], ['selesai'])) {
                                $rowClass = 'row-lunas';
                            } elseif (in_array($row['status'], ['disetujui', 'dikirim'])) {
                                $rowClass = 'row-aktif';
                            } elseif (in_array($row['status'], ['ditolak', 'dibatalkan'])) {
                                $rowClass = 'row-overdue';
                            } else {
                                $rowClass = '';
                            }
                        }
                        ?>
                        <tr class="<?= esc($rowClass); ?> clickable-row" data-href="<?= esc($href); ?>" tabindex="0" role="link">
                            <td>
                                <span class="fw-semibold"><?= esc($isKredit ? ($row['kode_kredit'] ?? '') : ($row['kode_pesanan'] ?? '')); ?></span>
                                <?php if (!empty($row['bukti_pending'])): ?>
                                    <span class="badge bg-warning text-dark ms-1"><?= esc($row['bukti_pending']); ?> pending</span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($isKredit ? ($row['nama_nasabah'] ?? '') : ($row['nama_user'] ?? '')); ?></td>
                            <td><?= esc($row['nama_produk'] ?? ''); ?></td>
                            <td><span class="badge bg-<?= $isKredit ? 'warning' : 'info'; ?>"><?= esc(ucfirst($row['tipe_transaksi'])); ?></span></td>
                            <td><?= esc(format_rupiah($isKredit ? ($row['total_harga_kredit'] ?? 0) : ($row['total_pembayaran'] ?? 0))); ?></td>
                            <td><?= esc(format_rupiah($row['total_terbayar'] ?? 0)); ?></td>
                            <td><?= esc(format_rupiah($row['sisa_piutang'] ?? 0)); ?></td>
                            <td><span class="badge <?= esc($statusBadge); ?>"><?= esc($statusText); ?></span></td>
                            <td class="text-nowrap"><?= esc(format_tanggal($row['created_at'], 'd M Y')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Legend -->
        <div class="mt-3 d-flex flex-wrap gap-3 small">
            <span class="legend-lunas px-3 py-1 rounded">Lunas</span>
            <span class="legend-aktif px-3 py-1 rounded">Aktif — Lancar</span>
            <span class="legend-terlambat px-3 py-1 rounded">Aktif — Terlambat</span>
        </div>
    <?php else: ?>
        <?= view('partials/empty_state', ['title' => 'Belum ada transaksi']); ?>
    <?php endif; ?>
</div>
<?= $this->endSection(); ?>
