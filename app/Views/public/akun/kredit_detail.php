<?= $this->extend('layouts/public'); ?>

<?= $this->section('content'); ?>
<?php
$total     = (float) $kredit['total_harga_kredit'];
$terbayar  = (float) $kredit['total_terbayar'];
$persen    = $total > 0 ? min(100, round($terbayar / $total * 100)) : 0;
?>
<section class="section-padding bg-cream-soft akun-section">
    <div class="container-mg">
        <div class="breadcrumb-mg mb-3">
            <a href="<?= base_url('/akun'); ?>">Akun</a><span>/</span><span>Detail Kredit</span>
        </div>
        <div class="section-heading mb-4">
            <p class="section-eyebrow">Detail Kredit</p>
            <h2 class="fw-black mb-1"><?= esc($kredit['kode_kredit']); ?></h2>
            <p class="text-muted-mg mb-0">
                <?= esc($kredit['nama_produk'] ?? 'Produk emas'); ?>
                · <?= esc($kredit['tenor_bulan']); ?> bulan
                · <?= esc(ucfirst($kredit['periode_angsuran'])); ?>
            </p>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="akun-stat feature-card p-4">
                    <span class="akun-stat-label">Total Harga Kredit</span>
                    <span class="akun-stat-value akun-stat-sm"><?= esc(format_rupiah($total)); ?></span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="akun-stat feature-card p-4">
                    <span class="akun-stat-label">Total Terbayar</span>
                    <span class="akun-stat-value akun-stat-sm text-success"><?= esc(format_rupiah($terbayar)); ?></span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="akun-stat feature-card p-4">
                    <span class="akun-stat-label">Sisa Piutang</span>
                    <span class="akun-stat-value akun-stat-sm"><?= esc(format_rupiah($kredit['sisa_piutang'])); ?></span>
                </div>
            </div>
        </div>

        <div class="feature-card p-4 mb-4">
            <div class="d-flex justify-content-between mb-2">
                <strong>Progress Pembayaran</strong>
                <span class="text-muted-mg"><?= esc($persen); ?>%</span>
            </div>
            <div class="progress akun-progress" role="progressbar" aria-valuenow="<?= esc($persen); ?>"
                aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: <?= esc($persen); ?>%"></div>
            </div>
        </div>

        <div class="feature-card p-4">
            <h5 class="fw-bold mb-3">Jadwal Angsuran</h5>
            <?php if (empty($jadwal)): ?>
                <p class="text-muted-mg mb-0">Jadwal angsuran belum tersedia.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Angsuran</th>
                                <th>Jatuh Tempo</th>
                                <th class="text-end">Tagihan</th>
                                <th class="text-end">Dibayar</th>
                                <th>Status</th>
                                <th>Bukti Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jadwal as $row): ?>
                                <tr>
                                    <td>Ke-<?= esc($row['angsuran_ke']); ?></td>
                                    <td><?= esc(format_tanggal($row['tanggal_jatuh_tempo'], 'd M Y')); ?></td>
                                    <td class="text-end"><?= esc(format_rupiah($row['nominal_tagihan'])); ?></td>
                                    <td class="text-end"><?= esc(format_rupiah($row['nominal_dibayar'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?= status_badge_class($row['status']); ?>">
                                            <?= esc(ucfirst(str_replace('_', ' ', $row['status']))); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php $b = $buktiByJadwal[(int) $row['id']] ?? null; ?>
                                        <?php if ($row['status'] === 'dibayar'): ?>
                                            <span class="text-success small fw-semibold">Lunas &#10003;</span>
                                            <?php if ($b): ?>
                                                <a href="<?= base_url('/akun/bukti/' . $b['id']); ?>" target="_blank" rel="noopener" class="small d-block">Lihat bukti</a>
                                            <?php endif; ?>
                                        <?php elseif ($b && $b['status'] === 'menunggu'): ?>
                                            <span class="badge bg-warning">Menunggu verifikasi</span>
                                            <a href="<?= base_url('/akun/bukti/' . $b['id']); ?>" target="_blank" rel="noopener" class="small d-block">Lihat bukti</a>
                                        <?php else: ?>
                                            <?php if ($b && $b['status'] === 'ditolak'): ?>
                                                <div class="small text-danger mb-1">Ditolak<?= $b['catatan_admin'] ? ': ' . esc($b['catatan_admin']) : ''; ?>. Unggah ulang.</div>
                                            <?php endif; ?>
                                            <form action="<?= base_url('/akun/kredit/' . $kredit['id'] . '/bukti/' . $row['id']); ?>"
                                                method="post" enctype="multipart/form-data" class="d-flex gap-2 align-items-center">
                                                <?= csrf_field(); ?>
                                                <input type="file" name="bukti" class="form-control form-control-sm"
                                                    accept="image/jpeg,image/png,application/pdf" required style="max-width:160px;">
                                                <button class="btn btn-sm btn-gold">Upload</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <a href="<?= base_url('/akun'); ?>" class="btn btn-outline-gold mt-3">← Kembali ke Dashboard</a>
        </div>
    </div>
</section>
<?= $this->endSection(); ?>
