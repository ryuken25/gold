<?= $this->extend('layouts/admin'); ?>

<?= $this->section('content'); ?>
<div class="dashboard-grid">
    <?php foreach ([
        ['label' => 'Total Nilai Kredit', 'value' => format_rupiah($metrics['total_nilai_kredit']), 'icon' => '💰'],
        ['label' => 'Total Pembayaran Masuk', 'value' => format_rupiah($metrics['total_pembayaran']), 'icon' => '💳'],
        ['label' => 'Total Sisa Piutang', 'value' => format_rupiah($metrics['total_sisa_piutang']), 'icon' => '📊'],
        ['label' => 'Kredit Aktif', 'value' => $metrics['kredit_aktif'], 'icon' => '📄'],
        ['label' => 'Kredit Lunas', 'value' => $metrics['kredit_lunas'], 'icon' => '✅'],
        ['label' => 'Jatuh Tempo Hari Ini', 'value' => $metrics['jatuh_tempo_hari_ini'], 'icon' => '⏰'],
        ['label' => 'Angsuran Terlambat', 'value' => $metrics['angsuran_terlambat'], 'icon' => '⚠️'],
    ] as $card): ?>
        <div class="premium-card admin-card h-100 p-4">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted small mb-2"><?= esc($card['label']); ?></p>
                    <h3 class="fw-bold mb-0"><?= esc((string) $card['value']); ?></h3>
                </div>
                <span class="metric-icon"><?= esc($card['icon']); ?></span>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="premium-card admin-card h-100 p-4">
        <p class="text-muted small mb-3">Aksi Cepat</p>
        <div class="d-grid gap-2">
            <a class="btn btn-gold rounded-pill" href="<?= base_url('/admin/produk/create'); ?>">Tambah Produk</a>
            <a class="btn btn-outline-gold rounded-pill" href="<?= base_url('/admin/nasabah/create'); ?>">Tambah
                Nasabah</a>
            <a class="btn btn-outline-gold rounded-pill" href="<?= base_url('/admin/kredit/create'); ?>">Buat
                Kredit</a>
            <a class="btn btn-outline-gold rounded-pill" href="<?= base_url('/admin/pembayaran/create'); ?>">Catat
                Pembayaran</a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-6">
        <div class="premium-card p-4 h-100">
            <h5 class="fw-bold mb-3">Transaksi Kredit Terbaru</h5>
            <?php if ($recentCredits): ?>
                <div class="table-responsive">
                    <table class="table table-modern align-middle">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nasabah</th>
                                <th>Produk</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentCredits as $row): ?>
                                <tr>
                                    <td><a
                                            href="<?= base_url('/admin/kredit/' . $row['id']); ?>"><?= esc($row['kode_kredit']); ?></a>
                                    </td>
                                    <td><?= esc($row['nama_nasabah']); ?></td>
                                    <td><?= esc($row['nama_produk']); ?></td>
                                    <td><span
                                            class="badge text-bg-<?= esc(status_badge_class($row['status'])); ?>"><?= esc($row['status']); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?= view('partials/empty_state', ['title' => 'Belum ada transaksi kredit']); ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="premium-card p-4 h-100">
            <h5 class="fw-bold mb-3">Pembayaran Terbaru</h5>
            <?php if ($recentPayments): ?>
                <div class="table-responsive">
                    <table class="table table-modern align-middle">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nasabah</th>
                                <th>Nominal</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPayments as $row): ?>
                                <tr>
                                    <td><?= esc($row['kode_pembayaran']); ?></td>
                                    <td><?= esc($row['nama_nasabah']); ?></td>
                                    <td><?= esc(format_rupiah($row['nominal_bayar'])); ?></td>
                                    <td><?= esc(format_tanggal($row['tanggal_bayar'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?= view('partials/empty_state', ['title' => 'Belum ada pembayaran']); ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="premium-card p-4 h-100">
            <h5 class="fw-bold mb-3">Jatuh Tempo Terdekat</h5>
            <?php if ($upcomingDue): ?>
                <div class="table-responsive">
                    <table class="table table-modern align-middle">
                        <thead>
                            <tr>
                                <th>Nasabah</th>
                                <th>Jatuh Tempo</th>
                                <th>Nominal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingDue as $row): ?>
                                <tr>
                                    <td><?= esc($row['nama_nasabah']); ?>
                                        <div class="small text-muted"><?= esc($row['kode_kredit']); ?></div>
                                    </td>
                                    <td><?= esc(format_tanggal($row['tanggal_jatuh_tempo'])); ?></td>
                                    <td><?= esc(format_rupiah($row['nominal_tagihan'])); ?></td>
                                    <td><span class="badge bg-warning text-dark">H-3</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?= view('partials/empty_state', ['title' => 'Tidak ada jadwal jatuh tempo']); ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="premium-card p-4 h-100">
            <h5 class="fw-bold mb-3">Nasabah dengan Sisa Piutang Terbesar</h5>
            <?php if ($topReceivables): ?>
                <div class="table-responsive">
                    <table class="table table-modern align-middle">
                        <thead>
                            <tr>
                                <th>Nasabah</th>
                                <th>Total Kredit</th>
                                <th>Sisa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topReceivables as $row): ?>
                                <tr>
                                    <td><?= esc($row['nama']); ?>
                                        <div class="small text-muted"><?= esc($row['no_telepon']); ?></div>
                                    </td>
                                    <td><?= esc(format_rupiah($row['total_kredit'])); ?></td>
                                    <td><?= esc(format_rupiah($row['sisa_piutang'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?= view('partials/empty_state', ['title' => 'Belum ada data piutang']); ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-12">
        <div class="premium-card p-4">
            <h5 class="fw-bold mb-3">Log Email Terbaru</h5>
            <?php if (!empty($recentLogs)): ?>
                <div class="table-responsive">
                    <table class="table table-modern align-middle">
                        <thead>
                            <tr>
                                <th>Tipe</th>
                                <th>Penerima</th>
                                <th>Status</th>
                                <th>Subjek</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLogs as $row): ?>
                                <tr>
                                    <td><?= esc($row['tipe']); ?></td>
                                    <td><?= esc($row['nama_tujuan'] ?? $row['tujuan_email'] ?? '-'); ?></td>
                                    <td><span
                                            class="badge text-bg-<?= esc(status_badge_class($row['status'])); ?>"><?= esc($row['status']); ?></span>
                                    </td>
                                    <td class="small"><?= esc(mb_substr($row['subjek'] ?? '', 0, 120)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?= view('partials/empty_state', ['title' => 'Belum ada log email']); ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection(); ?>