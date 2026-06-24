<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="row g-4">
    <div class="col-lg-4">
        <?php
        // Find target installment for manual reminder
        $targetJadwal = null;
        $kreditStatus = $kredit['status'] ?? 'aktif';

        if (in_array($kreditStatus, ['aktif', 'terlambat'], true)) {
            $overdueTarget = null;
            $thisMonthTarget = null;
            $nextMonthTarget = null;
            $firstUnpaidTarget = null;

            $todayTime = strtotime(date('Y-m-d'));
            $thisMonthStart = strtotime(date('Y-m-01'));
            $thisMonthEnd = strtotime(date('Y-m-t 23:59:59'));
            $nextMonthStart = strtotime(date('Y-m-01', strtotime('+1 month')));
            $nextMonthEnd = strtotime(date('Y-m-t 23:59:59', strtotime('+1 month')));

            foreach ($jadwal as $row) {
                if ($row['status'] === 'dibayar') {
                    continue;
                }

                $dueTime = strtotime($row['tanggal_jatuh_tempo']);

                // First unpaid target
                if ($firstUnpaidTarget === null) {
                    $firstUnpaidTarget = $row;
                }

                // Overdue target
                if ($dueTime < $todayTime) {
                    if ($overdueTarget === null) {
                        $overdueTarget = $row;
                    }
                }

                // This month target
                if ($dueTime >= $thisMonthStart && $dueTime <= $thisMonthEnd) {
                    if ($thisMonthTarget === null) {
                        $thisMonthTarget = $row;
                    }
                }

                // Next month target
                if ($dueTime >= $nextMonthStart && $dueTime <= $nextMonthEnd) {
                    if ($nextMonthTarget === null) {
                        $nextMonthTarget = $row;
                    }
                }
            }

            if ($overdueTarget !== null) {
                $targetJadwal = $overdueTarget;
            } elseif ($thisMonthTarget !== null) {
                $targetJadwal = $thisMonthTarget;
            } elseif ($nextMonthTarget !== null) {
                $targetJadwal = $nextMonthTarget;
            } else {
                $targetJadwal = $firstUnpaidTarget;
            }
        }
        ?>

        <div class="premium-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="text-muted small">Nomor Kredit</div>
                    <h4 class="fw-bold mb-0"><?= esc($kredit['kode_kredit']); ?></h4>
                </div>
                <span
                    class="badge text-bg-<?= esc(status_badge_class($kredit['status'])); ?>"><?= esc($kredit['status']); ?></span>
            </div>
            <div class="mini-stats mb-3">
                <div><span>Nasabah</span><strong><?= esc($kredit['nama_nasabah']); ?></strong></div>
                <div><span>Produk</span><strong><?= esc($kredit['nama_produk']); ?></strong></div>
                <div><span>Total Kredit</span><strong><?= esc(format_rupiah($kredit['total_harga_kredit'])); ?></strong>
                </div>
                <div>
                    <span>Uang Muka (DP)</span>
                    <strong><?= esc(format_rupiah($kredit['uang_muka'] ?? 0)); ?></strong>
                    <?php if (($kredit['dp_status'] ?? '') === 'terverifikasi'): ?>
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
                        <div class="small text-muted ps-2" style="font-size: 0.75rem; line-height: 1.3;">
                            <div>Bayar: <?= esc($tglDp); ?></div>
                            <div>Bulan: <?= esc($blnDp); ?></div>
                            <div class="d-flex gap-1 mt-1">
                                <a href="<?= base_url('/admin/kredit/' . $kredit['id'] . '/nota-dp'); ?>" class="btn btn-xs btn-outline-gold py-0 px-2" style="font-size: 0.7rem; border-radius: 4px; font-weight: normal; padding: 1px 4px; line-height: 1.2;"><i class="bi bi-file-text"></i> Nota</a>
                                <a href="<?= base_url('/admin/kredit/' . $kredit['id'] . '/print-dp'); ?>" target="_blank" rel="noopener" class="btn btn-xs btn-gold py-0 px-2" style="font-size: 0.7rem; border-radius: 4px; font-weight: normal; padding: 1px 4px; line-height: 1.2; background-color: var(--mg-gold); color: var(--mg-dark);"><i class="bi bi-printer"></i> Print</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div><span>Sisa Pokok (dicicil)</span><strong><?= esc(format_rupiah($kredit['sisa_pokok_kredit'] ?? $kredit['total_harga_kredit'])); ?></strong></div>
                <div><span>Total Terbayar</span><strong><?= esc(format_rupiah($kredit['total_terbayar'])); ?></strong>
                </div>
                <div><span>Sisa Piutang</span><strong><?= esc(format_rupiah($kredit['sisa_piutang'])); ?></strong></div>
                <div>
                    <span>Angsuran</span><strong><?= esc(format_rupiah($kredit['nominal_angsuran'])); ?>/<?= esc(periode_label($kredit['periode_angsuran'])); ?></strong>
                </div>
                <div><span>Jatuh Tempo Pertama</span><strong><?= esc(format_tanggal($jadwalPertama)); ?></strong></div>
            </div>
            <div class="d-grid gap-2">
                <?php if ($kredit['status'] === 'aktif'): ?><a class="btn btn-outline-gold rounded-pill"
                        href="<?= base_url('/admin/pembayaran/create?kredit_id=' . $kredit['id']); ?>">Catat
                        Pembayaran</a><?php endif; ?>
                <?php if ($kredit['status'] === 'aktif'): ?>
                    <button type="button" class="btn btn-outline-danger rounded-pill w-100" id="btnBatalkan">
                        <i class="bi bi-x-circle"></i> Batalkan Kredit
                    </button>
                <?php endif; ?>
                <div class="alert alert-info mb-0 small">
                    <i class="bi bi-envelope-check"></i> Notifikasi otomatis via email.
                </div>
            </div>
        </div>

        <?php if ($targetJadwal): ?>
            <?php
            $selisih = (int) round((strtotime($targetJadwal['tanggal_jatuh_tempo']) - strtotime(date('Y-m-d'))) / 86400);
            $totalAngsuran = (float) $targetJadwal['nominal_tagihan'];
            $sudahDibayar = (float) $targetJadwal['nominal_dibayar'];
            $sisaPembayaran = max(0, $totalAngsuran - $sudahDibayar);

            if ($selisih < 0) {
                $statusText = 'Telat ' . abs($selisih) . ' hari';
                $statusClass = 'text-danger fw-bold';
            } elseif ($selisih === 0) {
                $statusText = 'Jatuh tempo hari ini';
                $statusClass = 'text-warning fw-bold';
            } elseif ($selisih > 0 && $selisih <= 3) {
                $statusText = $selisih . ' hari lagi jatuh tempo';
                $statusClass = 'text-warning fw-bold';
            } elseif (date('Y-m', strtotime($targetJadwal['tanggal_jatuh_tempo'])) === date('Y-m', strtotime('+1 month'))) {
                $statusText = 'Jatuh tempo bulan depan';
                $statusClass = 'text-muted';
            } else {
                $statusText = 'Jatuh tempo tanggal ' . format_tanggal($targetJadwal['tanggal_jatuh_tempo']);
                $statusClass = 'text-muted';
            }
            ?>
            <div class="premium-card p-4">
                <h5 class="fw-bold mb-3">Reminder Pembayaran</h5>
                <div class="mini-stats mb-3">
                    <div><span>Tenor Aktif</span><strong>Angsuran ke-<?= esc($targetJadwal['angsuran_ke']); ?></strong></div>
                    <div><span>Total Angsuran</span><strong><?= esc(format_rupiah($totalAngsuran)); ?></strong></div>
                    <div><span>Sudah Dibayar</span><strong><?= esc(format_rupiah($sudahDibayar)); ?></strong></div>
                    <div><span>Sisa Pembayaran</span><strong><?= esc(format_rupiah($sisaPembayaran)); ?></strong></div>
                    <div><span>Jatuh Tempo</span><strong><?= esc(format_tanggal($targetJadwal['tanggal_jatuh_tempo'])); ?></strong></div>
                    <div><span>Status</span><strong class="<?= $statusClass; ?>"><?= esc($statusText); ?></strong></div>
                </div>
                <button type="button" class="btn btn-gold w-100 rounded-pill" id="btnReminder" data-jadwal-id="<?= $targetJadwal['id']; ?>">
                    <i class="bi bi-bell"></i> Kirim Reminder Email
                </button>
                <?php
                $db = \Config\Database::connect();
                $lastLog = $db->table('reminder_angsuran_logs')
                    ->where('jadwal_angsuran_id', $targetJadwal['id'])
                    ->where('status', 'terkirim')
                    ->orderBy('created_at', 'DESC')
                    ->get()->getRowArray();
                ?>
                <?php if ($lastLog): ?>
                    <div class="text-center mt-2 small text-muted">
                        <i class="bi bi-info-circle"></i> Sudah pernah dikirim.<br>
                        Terakhir: <?= esc(format_tanggal($lastLog['created_at'], 'd M Y H:i')); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-lg-8">
        <div class="premium-card p-4 mb-4">
            <h5 class="fw-bold mb-3">Jadwal Angsuran</h5>
            <div class="table-responsive">
                <table class="table table-modern align-middle">
                    <thead>
                        <tr>
                            <th>Ke</th>
                            <th>Jatuh Tempo</th>
                            <th>Tagihan</th>
                            <th>Dibayar</th>
                            <th>Status</th>
                            <th>Bukti &amp; Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody><?php foreach ($jadwal as $row): ?>
                            <?php // UPDATED: color coding menggunakan kredit_state helper ?>
                            <?php $state = kredit_state($row); ?>
                            <tr class="<?= esc($state['class']); ?>">
                                <td><?= esc($row['angsuran_ke']); ?></td>
                                <td><?= esc(format_tanggal($row['tanggal_jatuh_tempo'])); ?></td>
                                <td><?= esc(format_rupiah($row['nominal_tagihan'])); ?></td>
                                <td><?= esc(format_rupiah($row['nominal_dibayar'])); ?></td>
                                <td>
                                    <span class="badge text-bg-<?= esc(status_badge_class($row['status'])); ?>">
                                        <?= esc(ucfirst($row['status'])); ?>
                                    </span>
                                    <?php if (!empty($state['label'])): ?>
                                        <div class="small mt-1"><?= esc($state['icon'] . ' ' . $state['label']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $b = $buktiByJadwal[(int) $row['id']] ?? null; ?>
                                    <div class="d-flex flex-column gap-1">
                                        <?php if ($row['status'] === 'dibayar'): ?>
                                            <div class="d-flex gap-1 mb-1">
                                                <a href="<?= base_url('/admin/kredit/' . $kredit['id'] . '/nota-angsuran/' . $row['id']); ?>" class="btn btn-xs btn-outline-gold py-0 px-2" style="font-size: 0.75rem; border-radius: 4px; font-weight: normal; padding: 2px 6px;"><i class="bi bi-file-text"></i> Nota</a>
                                                <a href="<?= base_url('/admin/kredit/' . $kredit['id'] . '/print-angsuran/' . $row['id']); ?>" target="_blank" rel="noopener" class="btn btn-xs btn-gold py-0 px-2" style="font-size: 0.75rem; border-radius: 4px; font-weight: normal; padding: 2px 6px; background-color: var(--mg-gold); color: var(--mg-dark);"><i class="bi bi-printer"></i> Print</a>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($b): ?>
                                            <div>
                                                <a href="<?= base_url('/admin/pembayaran/' . $b['id'] . '/bukti'); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-gold rounded-pill px-2 py-0">
                                                    <i class="bi bi-eye"></i> Lihat Bukti
                                                </a>
                                            </div>
                                            <?php if ($b['status'] === 'menunggu'): ?>
                                                <span class="badge text-bg-warning small">Menunggu verifikasi</span>
                                                <div class="btn-group btn-group-sm mt-1" style="max-width: 140px;">
                                                    <button type="button" class="btn btn-gold py-0 px-2 js-verif-bukti" data-id="<?= $b['id']; ?>" title="Verifikasi">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger py-0 px-2 js-tolak-bukti" data-id="<?= $b['id']; ?>" title="Tolak">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                </div>
                                            <?php elseif ($b['status'] === 'terverifikasi'): ?>
                                                <span class="badge text-bg-success small">Terverifikasi</span>
                                            <?php elseif ($b['status'] === 'ditolak'): ?>
                                                <span class="badge text-bg-danger small">Ditolak</span>
                                                <?php if (!empty($b['catatan_admin'])): ?>
                                                    <small class="text-danger" style="font-size:0.75rem;">Alasan: <?= esc($b['catatan_admin']); ?></small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php elseif ($row['status'] !== 'dibayar'): ?>
                                            -
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr><?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="premium-card p-4">
            <h5 class="fw-bold mb-3">Riwayat Pembayaran</h5>
            <?php if ($payments): ?>
                <div class="table-responsive">
                    <table class="table table-modern align-middle">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Tanggal</th>
                                <th>Nominal</th>
                                <th>Metode</th>
                            </tr>
                        </thead>
                        <tbody><?php foreach ($payments as $row): ?>
                                <tr>
                                    <td><?= esc($row['kode_pembayaran']); ?></td>
                                    <td><?= esc(format_tanggal($row['tanggal_bayar'])); ?></td>
                                    <td><?= esc(format_rupiah($row['nominal_bayar'])); ?></td>
                                    <td><?= esc($row['metode_pembayaran']); ?></td>
                                </tr><?php endforeach; ?>
                        </tbody>
                    </table>
                </div><?php else: ?><?= view('partials/empty_state', ['title' => 'Belum ada pembayaran']); ?><?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection(); ?>

<?= $this->section('scripts'); ?>
<script>
(function() {
    const ID = <?= (int) $kredit['id'] ?>;

    document.getElementById('btnBatalkan')?.addEventListener('click', () => {
        MahenDialog.confirm({
            title: 'Batalkan Kredit',
            message: 'Apakah Anda yakin ingin membatalkan kredit ini? Tindakan ini dapat memengaruhi stok dan tidak dapat dibatalkan secara otomatis.',
            confirmText: 'Ya, Batalkan',
            confirmClass: 'btn-danger',
            onConfirm: async (helpers) => {
                try {
                    const res = await MahenAjax.post('/admin/kredit/' + ID + '/batalkan');
                    helpers.close();
                    MahenDialog.success({ title: 'Dibatalkan', message: res.message, onConfirm: () => window.location.href = res.redirect || '/admin/kredit/' + ID });
                } catch (err) { helpers.finish(); MahenDialog.error({ title: 'Gagal', message: err.message }); }
            }
        });
    });

    document.querySelectorAll('.js-verif-bukti').forEach(btn => {
        btn.addEventListener('click', () => {
            MahenDialog.confirm({
                title: 'Verifikasi Pembayaran',
                message: 'Pastikan nominal, rekening pengirim, dan bukti pembayaran sudah sesuai sebelum melanjutkan.',
                confirmText: 'Ya, Verifikasi',
                onConfirm: async (helpers) => {
                    try {
                        const res = await MahenAjax.post('/admin/pembayaran/' + btn.dataset.id + '/verifikasi');
                        helpers.close();
                        MahenDialog.success({ title: 'Berhasil', message: res.message, onConfirm: () => window.location.reload() });
                    } catch (err) { helpers.finish(); MahenDialog.error({ title: 'Gagal', message: err.message }); }
                }
            });
        });
    });

    document.querySelectorAll('.js-tolak-bukti').forEach(btn => {
        btn.addEventListener('click', () => {
            MahenDialog.form({
                title: 'Tolak Bukti Pembayaran',
                fields: [{ name: 'catatan_admin', label: 'Alasan Penolakan', type: 'textarea', required: true, minlength: 5, placeholder: 'Jelaskan alasan penolakan...', rows: 3 }],
                submitText: 'Tolak',
                submitClass: 'btn-danger',
                onsubmit: async (data, helpers) => {
                    try {
                        const res = await MahenAjax.post('/admin/pembayaran/' + btn.dataset.id + '/tolak', { catatan_admin: data.catatan_admin || '' });
                        helpers.close();
                        MahenDialog.success({ title: 'Ditolak', message: res.message, onConfirm: () => window.location.reload() });
                    } catch (err) { helpers.setError(err.message); helpers.finish(); }
                }
            });
        });
    });

    document.getElementById('btnReminder')?.addEventListener('click', function() {
        const jadwalId = this.dataset.jadwalId;
        MahenDialog.confirm({
            title: 'Kirim Pengingat',
            message: 'Kirim reminder manual untuk angsuran ini ke email pelanggan?',
            confirmText: 'Ya, Kirim',
            onConfirm: async (helpers) => {
                try {
                    const res = await MahenAjax.post('/admin/kredit/' + ID + '/angsuran/' + jadwalId + '/reminder');
                    helpers.close();
                    if (res.warning) {
                        MahenDialog.warning({
                            title: 'Pemberitahuan',
                            message: res.message,
                            onConfirm: () => window.location.reload()
                        });
                    } else {
                        MahenDialog.success({
                            title: 'Berhasil',
                            message: res.message,
                            onConfirm: () => window.location.reload()
                        });
                    }
                } catch (err) {
                    helpers.finish();
                    MahenDialog.error({ title: 'Gagal', message: err.message });
                }
            }
        });
    });
})();
</script>
<?= $this->endSection(); ?>