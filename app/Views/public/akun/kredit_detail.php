<?= $this->extend('layouts/public'); ?>

<?= $this->section('content'); ?>
<?php
$total     = (float) $kredit['total_harga_kredit'];
$uangMuka  = (float) ($kredit['uang_muka'] ?? 0);
$sisaPokok = (float) ($kredit['sisa_pokok_kredit'] ?? $total);
$terbayar  = (float) $kredit['total_terbayar'];
// Progress dihitung dari sisa pokok (yang benar-benar dicicil), bukan total kotor.
$persen    = $sisaPokok > 0 ? min(100, round($terbayar / $sisaPokok * 100)) : 0;
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

        <div class="row g-3 g-lg-4 mb-4">
            <div class="col-6 col-lg-3">
                <div class="akun-stat feature-card p-4">
                    <span class="akun-stat-label">Total Harga Kredit</span>
                    <span class="akun-stat-value akun-stat-sm"><?= esc(format_rupiah($total)); ?></span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="akun-stat feature-card p-4 h-100">
                    <span class="akun-stat-label">Uang Muka (DP)</span>
                    <span class="akun-stat-value akun-stat-sm"><?= esc(format_rupiah($uangMuka)); ?></span>
                    <?php if (($kredit['dp_status'] ?? '') === 'terverifikasi' || ($kredit['dp_status'] ?? '') === 'terverifikasi'): ?>
                        <?php
                        $db = \Config\Database::connect();
                        $buktiDp = $db->table('bukti_pembayaran')
                            ->where('pengajuan_id', $kredit['pengajuan_id'])
                            ->where('tipe', 'dp')
                            ->where('status', 'terverifikasi')
                            ->orderBy('id', 'DESC')
                            ->get()->getRowArray();
                        $tglDp = !empty($buktiDp['created_at']) ? format_tanggal_id($buktiDp['created_at']) : format_tanggal_id($kredit['dp_verified_at'] ?: date('Y-m-d H:i:s'));
                        $blnDp = !empty($buktiDp['created_at']) ? format_tanggal_id($buktiDp['created_at'], 'F Y') : format_tanggal_id($kredit['dp_verified_at'] ?: date('Y-m-d H:i:s'), 'F Y');
                        ?>
                        <div class="small text-muted-mg mt-2" style="font-size: 0.75rem; line-height: 1.3;">
                            <div>Bayar: <?= esc($tglDp); ?></div>
                            <div>Bulan: <?= esc($blnDp); ?></div>
                        </div>
                        <div class="d-flex gap-1 mt-2">
                            <a href="<?= base_url('/akun/kredit/' . $kredit['id'] . '/nota-dp'); ?>" class="btn btn-xs btn-outline-gold py-0 px-2" style="font-size: 0.75rem; border-radius: 4px; font-weight: normal; padding: 2px 6px;"><i class="bi bi-file-text"></i> Nota</a>
                            <a href="<?= base_url('/akun/kredit/' . $kredit['id'] . '/print-dp'); ?>" target="_blank" rel="noopener" class="btn btn-xs btn-gold py-0 px-2" style="font-size: 0.75rem; border-radius: 4px; font-weight: normal; padding: 2px 6px; background-color: var(--mg-gold); color: var(--mg-dark);"><i class="bi bi-printer"></i> Print</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="akun-stat feature-card p-4">
                    <span class="akun-stat-label">Sisa Pokok (dicicil)</span>
                    <span class="akun-stat-value akun-stat-sm"><?= esc(format_rupiah($sisaPokok)); ?></span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
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
            <?php
            $dpStatus = $kredit['dp_status'] ?? 'belum';
            $dpDibutuhkan = (int)($kredit['uang_muka'] ?? 0) > 0;
            $dpBelumVerified = $dpDibutuhkan && $dpStatus !== 'terverifikasi';
            ?>
            <?php if ($dpBelumVerified): ?>
                <div class="alert alert-warning mb-0 p-4 rounded-3 text-center">
                    <i class="bi bi-clock-history fs-3 d-block mb-2 text-warning"></i>
                    <h6 class="fw-bold">Menunggu Verifikasi Uang Muka</h6>
                    <p class="mb-0 small">Jadwal angsuran belum aktif karena pembayaran Uang Muka (DP) belum diverifikasi oleh admin.</p>
                    <?php if ($dpStatus === 'ditolak'): ?>
                        <p class="mt-2 mb-0 text-danger fw-bold">Bukti DP sebelumnya ditolak. Silakan unggah ulang bukti DP dari menu <a href="<?= base_url('/akun/pesanan/' . $kredit['pengajuan_id']); ?>">Riwayat Pesanan</a>.</p>
                    <?php endif; ?>
                </div>
            <?php elseif (empty($jadwal)): ?>
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
                                            <span class="text-success small fw-semibold d-block mb-1">Lunas &#10003;</span>
                                            <div class="d-flex gap-1 mb-1">
                                                <a href="<?= base_url('/akun/kredit/' . $kredit['id'] . '/nota-angsuran/' . $row['id']); ?>" class="btn btn-xs btn-outline-gold py-0 px-2" style="font-size: 0.75rem; border-radius: 4px; font-weight: normal; padding: 2px 6px;"><i class="bi bi-file-text"></i> Nota</a>
                                                <a href="<?= base_url('/akun/kredit/' . $kredit['id'] . '/print-angsuran/' . $row['id']); ?>" target="_blank" rel="noopener" class="btn btn-xs btn-gold py-0 px-2" style="font-size: 0.75rem; border-radius: 4px; font-weight: normal; padding: 2px 6px; background-color: var(--mg-gold); color: var(--mg-dark);"><i class="bi bi-printer"></i> Print</a>
                                            </div>
                                            <?php if ($b): ?>
                                                <a href="<?= base_url('/akun/bukti/' . $b['id']); ?>" target="_blank" rel="noopener" class="small text-muted-mg" style="font-size: 0.75rem;">Lihat bukti</a>
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
