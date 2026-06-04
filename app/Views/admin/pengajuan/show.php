<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="mb-3">
    <a href="<?= base_url('/admin/pengajuan'); ?>" class="btn btn-sm btn-outline-gold rounded-pill">
        <i class="bi bi-arrow-left"></i> Kembali ke daftar
    </a>
</div>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="premium-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="text-muted small">Pengajuan #<?= esc($pengajuan['id']); ?></div>
                    <h4 class="fw-bold mb-0"><?= esc($pengajuan['nama']); ?></h4>
                </div>
                <span class="badge text-bg-<?= esc(status_badge_class($pengajuan['status'])); ?>">
                    <?= esc(ucfirst($pengajuan['status'])); ?>
                </span>
            </div>
            <div class="mini-stats mb-3">
                <div><span>Metode</span><strong><?= esc(ucfirst($pengajuan['metode_pembayaran'])); ?></strong></div>
                <div><span>Akun Pelanggan</span><strong><?= esc($pengajuan['nama_user'] ?? '-'); ?></strong></div>
                <div><span>Email</span><strong><?= esc($pengajuan['email_user'] ?? '-'); ?></strong></div>
                <div><span>No. Telepon</span><strong><?= esc($pengajuan['no_telepon'] ?: ($pengajuan['telepon_user'] ?? '-')); ?></strong></div>
                <div><span>Tanggal</span><strong><?= esc(format_tanggal($pengajuan['created_at'], 'd M Y H:i')); ?></strong></div>
            </div>
            <div class="mb-1 text-muted small">Alamat</div>
            <p class="mb-0"><?= nl2br(esc($pengajuan['alamat'])); ?></p>
        </div>

        <div class="premium-card p-4 mb-4">
            <h5 class="fw-bold mb-3">Detail Produk</h5>
            <div class="mini-stats">
                <div><span>Produk</span><strong><?= esc($pengajuan['nama_produk'] ?? '-'); ?></strong></div>
                <div><span>Kode</span><strong><?= esc($pengajuan['kode_produk'] ?? '-'); ?></strong></div>
                <div><span>Jenis/Kadar</span><strong><?= esc($pengajuan['jenis_emas'] ?? '-'); ?> / <?= esc($pengajuan['kadar'] ?? '-'); ?></strong></div>
                <div><span>Berat</span><strong><?= esc(format_angka($pengajuan['berat_gram'] ?? 0, 2)); ?> gram</strong></div>
                <div><span>Harga Pokok</span><strong><?= esc(format_rupiah($pengajuan['harga_pokok'] ?? 0)); ?></strong></div>
            </div>
        </div>

        <?php if ($pengajuan['metode_pembayaran'] === 'kredit' && $simulasi): ?>
            <div class="premium-card p-4">
                <h5 class="fw-bold mb-3">Simulasi Kredit</h5>
                <div class="mini-stats">
                    <div><span>Tenor</span><strong><?= esc($pengajuan['tenor_bulan']); ?> bulan (<?= esc($pengajuan['periode_angsuran']); ?>)</strong></div>
                    <div><span>Margin</span><strong><?= esc(format_angka($simulasi['margin_persen'], 0)); ?>%</strong></div>
                    <div><span>Total Harga Kredit</span><strong><?= esc(format_rupiah($simulasi['total_harga_kredit'])); ?></strong></div>
                    <div><span>Jumlah Periode</span><strong><?= esc($simulasi['jumlah_periode']); ?> <?= esc($simulasi['periode_label']); ?></strong></div>
                    <div><span>Estimasi Angsuran</span><strong><?= esc(format_rupiah($simulasi['nominal_angsuran'])); ?> / <?= esc($simulasi['periode_label']); ?></strong></div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-5">
        <?php if ($pengajuan['metode_pembayaran'] === 'kredit'): ?>
            <div class="premium-card p-4 mb-4">
                <h5 class="fw-bold mb-3">Foto KTP</h5>
                <?php if (!empty($pengajuan['foto_ktp'])): ?>
                    <a href="<?= base_url('/admin/pengajuan/' . $pengajuan['id'] . '/ktp'); ?>" target="_blank" rel="noopener">
                        <img src="<?= base_url('/admin/pengajuan/' . $pengajuan['id'] . '/ktp'); ?>" alt="Foto KTP"
                            class="img-fluid rounded border">
                    </a>
                    <p class="text-muted-mg small mb-0 mt-2">Klik gambar untuk memperbesar di tab baru.</p>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">KTP belum diunggah untuk pengajuan ini.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="premium-card p-4">
            <h5 class="fw-bold mb-3">Perbarui Status</h5>
            <form action="<?= base_url('/admin/pengajuan/' . $pengajuan['id'] . '/status'); ?>" method="post">
                <?= csrf_field(); ?>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach ($statusList as $item): ?>
                            <option value="<?= esc($item); ?>" <?= $pengajuan['status'] === $item ? 'selected' : ''; ?>>
                                <?= esc(ucfirst($item)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan <span class="text-muted-mg">(opsional)</span></label>
                    <textarea name="catatan" class="form-control" rows="4"
                        placeholder="Catatan internal / alasan penolakan..."><?= esc($pengajuan['catatan'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn btn-gold rounded-pill w-100">Simpan Status</button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection(); ?>
