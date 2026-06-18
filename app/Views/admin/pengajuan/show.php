<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<?php
$statusFinal    = in_array($pengajuan['status'], ['ditolak', 'dibatalkan', 'selesai'], true);
$bisaVerifikasi = in_array($pengajuan['status'], ['baru', 'diproses'], true);
$telepon        = $pengajuan['no_telepon'] ?: ($pengajuan['telepon_user'] ?? '-');
$aksiIcon = [
    'dibuat'                => 'bi-plus-circle',
    'diverifikasi'          => 'bi-check-circle',
    'ditolak'               => 'bi-x-circle',
    'dibatalkan'            => 'bi-slash-circle',
    'status_diubah'         => 'bi-arrow-repeat',
];
$relatif = static function ($datetime): string {
    if (!$datetime) {
        return '-';
    }
    $diff = time() - strtotime($datetime);
    if ($diff < 60) {
        return 'baru saja';
    }
    if ($diff < 3600) {
        return (int) floor($diff / 60) . ' menit lalu';
    }
    if ($diff < 86400) {
        return (int) floor($diff / 3600) . ' jam lalu';
    }
    return (int) floor($diff / 86400) . ' hari lalu';
};
?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <span class="section-eyebrow d-block mb-1"><?= esc($pengajuan['kode_pesanan'] ?? ('Pengajuan #' . $pengajuan['id'])); ?></span>
        <h2 class="fw-black mb-1"><?= esc($pengajuan['nama']); ?></h2>
        <span class="badge text-bg-<?= esc(status_badge_class($pengajuan['status'])); ?>">
            <?= esc(ucfirst($pengajuan['status'])); ?>
        </span>
    </div>
    <a href="<?= base_url('/admin/pengajuan'); ?>" class="btn btn-sm btn-outline-gold rounded-pill">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="premium-card p-4 mb-4">
            <h5 class="fw-bold mb-3">Informasi Pesanan</h5>
            <div class="mini-stats">
                <div><span>No. WhatsApp</span><strong><?= esc($telepon); ?></strong></div>
                <div><span>Email</span><strong><?= esc($pengajuan['email_user'] ?? '-'); ?></strong></div>
                <div><span>Produk</span><strong><?= esc($pengajuan['nama_produk'] ?? '-'); ?> (<?= esc($pengajuan['kode_produk'] ?? '-'); ?>)</strong></div>
                <div><span>Jenis / Kadar</span><strong><?= esc($pengajuan['jenis_emas'] ?? '-'); ?> / <?= esc($pengajuan['kadar'] ?? '-'); ?></strong></div>
                <div><span>Berat</span><strong><?= esc(format_angka($pengajuan['berat_gram'] ?? 0, 2)); ?> gram</strong></div>
                <div><span>Harga Pokok</span><strong><?= esc(format_rupiah($pengajuan['harga_pokok'] ?? 0)); ?></strong></div>
                <div><span>Metode</span><strong><?= esc(ucfirst($pengajuan['metode_pembayaran'])); ?></strong></div>
                <div><span>Status Pembayaran</span><strong><?= esc(ucfirst($pengajuan['pembayaran_status'] ?? 'belum')); ?></strong></div>
                <?php if ($pengajuan['metode_pembayaran'] === 'kredit'): ?>
                    <div><span>Tenor</span><strong><?= esc($pengajuan['tenor_bulan']); ?> bulan (<?= esc($pengajuan['periode_angsuran']); ?>)</strong></div>
                <?php endif; ?>
            </div>

            <div class="mt-3">
                <div class="text-muted small mb-1">Alamat</div>
                <p class="mb-0"><?= nl2br(esc($pengajuan['alamat'])); ?></p>
            </div>
            <?php if (!empty($pengajuan['catatan'])): ?>
                <div class="mt-3">
                    <div class="text-muted small mb-1">Catatan</div>
                    <p class="mb-0"><?= nl2br(esc($pengajuan['catatan'])); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($pengajuan['metode_pembayaran'] === 'kredit' && $simulasi): ?>
                <hr class="my-3">
                <h6 class="fw-bold mb-2">Simulasi Kredit</h6>
                <div class="mini-stats">
                    <div><span>Margin</span><strong><?= esc(format_angka($simulasi['margin_persen'], 0)); ?>%</strong></div>
                    <div><span>Total Harga Kredit</span><strong><?= esc(format_rupiah($simulasi['total_harga_kredit'])); ?></strong></div>
                    <div><span>Uang Muka (DP)</span><strong><?= esc(format_rupiah($simulasi['uang_muka'])); ?></strong></div>
                    <div><span>Sisa Diangsur</span><strong><?= esc(format_rupiah($simulasi['sisa_pokok'])); ?></strong></div>
                    <div><span>Jumlah Periode</span><strong><?= esc($simulasi['jumlah_periode']); ?> <?= esc($simulasi['periode_label']); ?></strong></div>
                    <div><span>Estimasi Angsuran</span><strong><?= esc(format_rupiah($simulasi['nominal_angsuran'])); ?> / <?= esc($simulasi['periode_label']); ?></strong></div>
                </div>
            <?php endif; ?>

            <?php // UPDATED: Foto KTP ditampilkan untuk SEMUA metode (cash & kredit) ?>
            <hr class="my-3">
            <h6 class="fw-bold mb-2">Foto KTP</h6>
            <?php if (!empty($pengajuan['foto_ktp'])): ?>
                <a href="<?= base_url('/admin/pengajuan/' . $pengajuan['id'] . '/ktp'); ?>" target="_blank" rel="noopener">
                    <img src="<?= base_url('/admin/pengajuan/' . $pengajuan['id'] . '/ktp'); ?>" alt="Foto KTP"
                        style="max-height:180px;width:auto;" class="rounded border">
                </a>
            <?php else: ?>
                <div class="alert alert-warning mb-0">KTP belum diunggah.</div>
            <?php endif; ?>

            <?php // UPDATED: Bukti Pembayaran ditampilkan untuk SEMUA metode ?>
            <?php
            $buktiModel = new \App\Models\BuktiPembayaranModel();
            $buktiList = $buktiModel->where('pengajuan_id', $pengajuan['id'])->orderBy('id', 'DESC')->findAll();
            ?>
            <?php if (!empty($buktiList)): ?>
                <hr class="my-3">
                <h6 class="fw-bold mb-2">Bukti Pembayaran</h6>
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($buktiList as $b): ?>
                        <div class="text-center">
                            <a href="<?= base_url('/admin/pembayaran/' . $b['id'] . '/bukti'); ?>" target="_blank" rel="noopener">
                                <?php
                                $ext = pathinfo($b['file_path'], PATHINFO_EXTENSION);
                                if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])):
                                ?>
                                    <img src="<?= base_url('/admin/pembayaran/' . $b['id'] . '/bukti'); ?>" alt="Bukti <?= esc($b['tipe']); ?>"
                                        style="max-height:150px;width:auto;" class="rounded border">
                                <?php else: ?>
                                    <div class="btn btn-sm btn-outline-gold rounded-pill">
                                        <i class="bi bi-file-earmark-pdf"></i> Lihat PDF
                                    </div>
                                <?php endif; ?>
                            </a>
                            <div class="small text-muted-mg mt-1">
                                <?= esc(ucfirst($b['tipe'])); ?> — <?= esc(ucfirst($b['status'])); ?>
                                <?php if (!empty($b['nominal'])): ?>
                                    <br><?= esc(format_rupiah($b['nominal'])); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php // UPDATED: Panel notifikasi email (pengganti WA manual) ?>
        <div class="premium-card p-4">
            <h5 class="fw-bold mb-3">Notifikasi</h5>
            <p class="text-muted-mg mb-0">
                <i class="bi bi-envelope-check text-gold-soft"></i>
                Notifikasi email otomatis dikirim ke pelanggan setiap perubahan status.
                Tidak ada alur penagihan via WhatsApp.
            </p>
        </div>
    </div>

    <div class="col-lg-5">
        <?php // Panel aksi — workflow bertahap ?>
        <div class="premium-card p-4 mb-4">
            <h5 class="fw-bold mb-3">Aksi</h5>

            <?php if ($statusFinal): ?>
                <div class="alert alert-secondary mb-0">Pesanan sudah final (<?= esc(pesanan_status_label($pengajuan['status'])); ?>). Aksi tidak tersedia.</div>

            <?php elseif (in_array($pengajuan['status'], ['baru', 'diproses'], true)): ?>
                <button type="button" class="btn btn-gold rounded-pill w-100 mb-2" id="btnVerifikasi">
                    <i class="bi bi-check-circle"></i> Verifikasi Pesanan
                </button>
                <button type="button" class="btn btn-outline-danger rounded-pill w-100" id="btnTolak">
                    <i class="bi bi-x-circle"></i> Tolak Pesanan
                </button>

            <?php elseif ($pengajuan['status'] === 'disetujui'): ?>
                <?php
                $payStatus = $pengajuan['pembayaran_status'] ?? 'belum';
                $metode    = $pengajuan['metode_pembayaran'] ?? 'cash';
                $uangMuka  = (int) ($pengajuan['uang_muka'] ?? 0);
                $bisaKirim = false;
                $kirimReason = '';

                if ($metode === 'cash') {
                    if ($payStatus === 'terverifikasi') {
                        $bisaKirim = true;
                    } else {
                        $kirimReason = 'Pembayaran cash belum terverifikasi. Verifikasi pembayaran terlebih dahulu.';
                    }
                } elseif ($metode === 'kredit') {
                    if ($uangMuka > 0 && $payStatus !== 'terverifikasi') {
                        $kirimReason = 'DP belum terverifikasi. Verifikasi pembayaran DP terlebih dahulu.';
                    } else {
                        $bisaKirim = true;
                    }
                }
                ?>
                <?php if ($bisaKirim): ?>
                    <button type="button" class="btn btn-gold rounded-pill w-100 mb-2" id="btnKirim">
                        <i class="bi bi-truck"></i> Kirim Pesanan
                    </button>
                <?php else: ?>
                    <button type="button" class="btn btn-secondary rounded-pill w-100 mb-2" disabled>
                        <i class="bi bi-truck"></i> Kirim Pesanan
                    </button>
                    <div class="alert alert-warning mb-0 small">
                        <i class="bi bi-exclamation-triangle"></i> <?= esc($kirimReason); ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($pengajuan['status'] === 'dikirim'): ?>
                <button type="button" class="btn btn-gold rounded-pill w-100" id="btnSelesai">
                    <i class="bi bi-check-circle-fill"></i> Tandai Selesai
                </button>
            <?php endif; ?>
        </div>

        <?php // Riwayat aktivitas ?>
        <div class="premium-card p-4">
            <h5 class="fw-bold mb-3">Riwayat Aktivitas</h5>
            <?php if (empty($aktivitas)): ?>
                <p class="text-muted-mg mb-0">Belum ada aktivitas.</p>
            <?php else: ?>
                <ul class="activity-timeline list-unstyled mb-0">
                    <?php foreach ($aktivitas as $a): ?>
                        <li class="d-flex gap-3 mb-3">
                            <i class="bi <?= esc($aksiIcon[$a['aksi']] ?? 'bi-dot'); ?> fs-5 text-gold-soft"></i>
                            <div class="min-w-0">
                                <div class="fw-semibold text-capitalize"><?= esc(str_replace('_', ' ', $a['aksi'])); ?></div>
                                <?php if (!empty($a['keterangan'])): ?>
                                    <div class="small text-muted-mg"><?= esc($a['keterangan']); ?></div>
                                <?php endif; ?>
                                <div class="small text-muted-mg">
                                    <?= esc($a['aktor'] ?? '-'); ?> · <?= esc($relatif($a['created_at'])); ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection(); ?>

<?= $this->section('scripts'); ?>
<script>
(function() {
    const BASE = '<?= base_url('/admin/pengajuan/' . $pengajuan['id']) ?>';

    // VERIFIKASI
    document.getElementById('btnVerifikasi')?.addEventListener('click', () => {
        MahenDialog.confirm({
            title: 'Verifikasi Pesanan',
            message: <?= $pengajuan['metode_pembayaran'] === 'kredit'
                ? "'Menyetujui akan otomatis membuat kredit + jadwal angsuran. Lanjutkan?'"
                : "'Verifikasi pesanan ini?'" ?>,
            confirmText: 'Ya, Verifikasi',
            confirmClass: 'btn-gold',
            onConfirm: async (helpers) => {
                try {
                    const res = await MahenAjax.post(BASE + '/verifikasi');
                    helpers.close();
                    MahenDialog.success({ title: 'Berhasil', message: res.message, onConfirm: () => window.location.href = res.redirect || BASE });
                } catch (err) {
                    helpers.finish();
                    MahenDialog.error({ title: 'Gagal', message: err.message });
                }
            }
        });
    });

    // TOLAK
    document.getElementById('btnTolak')?.addEventListener('click', () => {
        MahenDialog.form({
            title: 'Tolak Pesanan',
            fields: [{ name: 'alasan', label: 'Alasan Penolakan', type: 'textarea', required: true, minlength: 5, maxlength: 1000, placeholder: 'Jelaskan alasan penolakan agar dapat dipahami oleh pelanggan.', rows: 3 }],
            submitText: 'Tolak Pesanan',
            submitClass: 'btn-danger',
            onsubmit: async (data, helpers) => {
                try {
                    const res = await MahenAjax.post(BASE + '/tolak', { alasan: data.alasan || '' });
                    helpers.close();
                    MahenDialog.success({ title: 'Pesanan Ditolak', message: res.message, onConfirm: () => window.location.href = res.redirect || BASE });
                } catch (err) {
                    helpers.setError(err.message);
                    helpers.finish();
                }
            }
        });
    });

    // KIRIM
    document.getElementById('btnKirim')?.addEventListener('click', () => {
        MahenDialog.form({
            title: 'Kirim Pesanan',
            message: 'Pilih metode pengiriman dan masukkan referensi.',
            fields: [
                { name: 'metode_pengiriman', label: 'Metode', type: 'select', required: true, options: [{value:'resi',label:'Nomor Resi'},{value:'no_hp',label:'Nomor HP Pengiriman'}] },
                { name: 'referensi_pengiriman', label: 'Referensi', type: 'text', required: true, placeholder: 'Masukkan nomor resi atau nomor HP...' }
            ],
            submitText: 'Kirim Pesanan',
            onsubmit: async (data, helpers) => {
                const metode = String(data.metode_pengiriman || '').trim();
                const ref = String(data.referensi_pengiriman || '').trim();
                if (!['resi', 'no_hp'].includes(metode)) {
                    helpers.setError('Pilih metode pengiriman yang valid.');
                    helpers.finish();
                    return;
                }
                if (!ref) {
                    helpers.setError('Referensi pengiriman wajib diisi.');
                    helpers.finish();
                    return;
                }
                try {
                    const res = await MahenAjax.post(BASE + '/kirim', { metode_pengiriman: metode, referensi_pengiriman: ref });
                    helpers.close();
                    MahenDialog.success({ title: 'Pesanan Dikirim', message: res.message, onConfirm: () => window.location.href = res.redirect || BASE });
                } catch (err) {
                    helpers.setError(err.message);
                    helpers.finish();
                }
            }
        });
    });

    // SELESAI
    document.getElementById('btnSelesai')?.addEventListener('click', () => {
        MahenDialog.confirm({
            title: 'Tandai Selesai',
            message: 'Tandai pesanan ini sebagai selesai? Pastikan pesanan telah diterima oleh pelanggan.',
            confirmText: 'Ya, Selesai',
            confirmClass: 'btn-gold',
            onConfirm: async (helpers) => {
                try {
                    const res = await MahenAjax.post(BASE + '/selesai');
                    helpers.close();
                    MahenDialog.success({ title: 'Selesai', message: res.message, onConfirm: () => window.location.href = res.redirect || BASE });
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
