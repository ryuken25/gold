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
    'wa_konfirmasi_dikirim' => 'bi-whatsapp',
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
                    <div><span>Jumlah Periode</span><strong><?= esc($simulasi['jumlah_periode']); ?> <?= esc($simulasi['periode_label']); ?></strong></div>
                    <div><span>Estimasi Angsuran</span><strong><?= esc(format_rupiah($simulasi['nominal_angsuran'])); ?> / <?= esc($simulasi['periode_label']); ?></strong></div>
                </div>
            <?php endif; ?>

            <?php if ($pengajuan['metode_pembayaran'] === 'kredit'): ?>
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
            <?php endif; ?>
        </div>

        <?php // Panel WhatsApp manual ?>
        <div class="premium-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Pesan WhatsApp (Manual)</h5>
                <?php if ($waTenor): ?>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-gold active" data-wa-tab="#wa-panel-konfirmasi">Konfirmasi</button>
                        <button type="button" class="btn btn-outline-gold" data-wa-tab="#wa-panel-tenor">Info Tenor</button>
                    </div>
                <?php endif; ?>
            </div>

            <div data-wa-panel id="wa-panel-konfirmasi">
                <textarea id="wa-msg-konfirmasi" class="form-control font-monospace small mb-2" rows="9" readonly><?= esc($waKonfirmasi['message']); ?></textarea>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-gold" data-copy-target="#wa-msg-konfirmasi">Salin pesan</button>
                    <a href="<?= esc($waKonfirmasi['wa_url']); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-whatsapp">Buka WhatsApp</a>
                </div>
            </div>

            <?php if ($waTenor): ?>
                <div data-wa-panel id="wa-panel-tenor" class="d-none">
                    <textarea id="wa-msg-tenor" class="form-control font-monospace small mb-2" rows="9" readonly><?= esc($waTenor['message']); ?></textarea>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-gold" data-copy-target="#wa-msg-tenor">Salin pesan</button>
                        <a href="<?= esc($waTenor['wa_url']); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-whatsapp">Buka WhatsApp</a>
                    </div>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('/admin/pengajuan/' . $pengajuan['id'] . '/wa-terkirim'); ?>" method="post" class="mt-3">
                <?= csrf_field(); ?>
                <button type="submit" class="btn btn-sm btn-gold rounded-pill">Tandai sudah dikirim</button>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <?php // Panel aksi ?>
        <div class="premium-card p-4 mb-4">
            <h5 class="fw-bold mb-3">Aksi</h5>

            <?php if ($statusFinal): ?>
                <div class="alert alert-secondary">Pesanan sudah final (<?= esc($pengajuan['status']); ?>). Aksi tidak tersedia.</div>
            <?php else: ?>
                <?php if ($bisaVerifikasi): ?>
                    <form action="<?= base_url('/admin/pengajuan/' . $pengajuan['id'] . '/verifikasi'); ?>" method="post" class="mb-3">
                        <?= csrf_field(); ?>
                        <?php if ($pengajuan['metode_pembayaran'] === 'kredit'): ?>
                            <div class="form-text mb-2">Menyetujui akan otomatis membuat kredit + jadwal angsuran untuk
                                pelanggan.</div>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-gold rounded-pill w-100">
                            Verifikasi Pesanan
                        </button>
                    </form>

                    <form action="<?= base_url('/admin/pengajuan/' . $pengajuan['id'] . '/tolak'); ?>" method="post" class="mb-3">
                        <?= csrf_field(); ?>
                        <label class="form-label">Alasan Penolakan</label>
                        <textarea name="alasan" class="form-control mb-2" rows="2" placeholder="Wajib diisi saat menolak..."></textarea>
                        <button type="submit" class="btn btn-outline-danger rounded-pill w-100">Tolak Pesanan</button>
                    </form>
                <?php endif; ?>

                <form action="<?= base_url('/admin/pengajuan/' . $pengajuan['id'] . '/batalkan'); ?>" method="post"
                    onsubmit="return confirm('Batalkan pesanan ini?');" class="mb-3">
                    <?= csrf_field(); ?>
                    <button type="submit" class="btn btn-outline-danger rounded-pill w-100">Batalkan Pesanan</button>
                </form>

                <hr>
                <form action="<?= base_url('/admin/pengajuan/' . $pengajuan['id'] . '/status'); ?>" method="post">
                    <?= csrf_field(); ?>
                    <label class="form-label">Ubah Status Lanjutan</label>
                    <div class="d-flex gap-2">
                        <select name="status" class="form-select form-select-sm">
                            <?php foreach ($statusList as $item): ?>
                                <option value="<?= esc($item); ?>" <?= $pengajuan['status'] === $item ? 'selected' : ''; ?>>
                                    <?= esc(ucfirst($item)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-gold rounded-pill px-3">Simpan</button>
                    </div>
                </form>
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
    document.querySelectorAll('[data-copy-target]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const el = document.querySelector(btn.dataset.copyTarget);
            if (!el) return;
            const done = () => { const t = btn.textContent; btn.textContent = 'Tersalin!'; setTimeout(() => { btn.textContent = t; }, 1500); };
            if (navigator.clipboard) { navigator.clipboard.writeText(el.value).then(done).catch(() => { el.select(); document.execCommand('copy'); done(); }); }
            else { el.select(); document.execCommand('copy'); done(); }
        });
    });
    document.querySelectorAll('[data-wa-tab]').forEach((tab) => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('[data-wa-panel]').forEach((p) => p.classList.add('d-none'));
            const target = document.querySelector(tab.dataset.waTab);
            if (target) target.classList.remove('d-none');
            document.querySelectorAll('[data-wa-tab]').forEach((t) => t.classList.remove('active'));
            tab.classList.add('active');
        });
    });
</script>
<?= $this->endSection(); ?>
