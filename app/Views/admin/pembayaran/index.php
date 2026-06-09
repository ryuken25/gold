<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<?php $f = $filter; ?>
<div class="premium-card p-4">
    <form method="get" class="row g-2 align-items-end mb-4">
        <div class="col-6 col-lg-2">
            <label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Semua</option>
                <?php foreach (['menunggu', 'terverifikasi', 'ditolak'] as $s): ?>
                    <option value="<?= $s; ?>" <?= $f['status'] === $s ? 'selected' : ''; ?>><?= ucfirst($s); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label small mb-1">Tipe</label>
            <select name="tipe" class="form-select form-select-sm">
                <option value="">Semua</option>
                <option value="cash" <?= $f['tipe'] === 'cash' ? 'selected' : ''; ?>>Cash</option>
                <option value="cicilan" <?= $f['tipe'] === 'cicilan' ? 'selected' : ''; ?>>Cicilan</option>
            </select>
        </div>
        <div class="col-12 col-lg-3">
            <label class="form-label small mb-1">Cari</label>
            <input type="text" name="q" value="<?= esc($f['q']); ?>" class="form-control form-control-sm"
                placeholder="Nama / kode bukti / kode kredit / pesanan">
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label small mb-1">Dari</label>
            <input type="date" name="dari" value="<?= esc($f['dari']); ?>" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label small mb-1">Sampai</label>
            <input type="date" name="sampai" value="<?= esc($f['sampai']); ?>" class="form-control form-control-sm">
        </div>
        <div class="col-12 col-lg-1">
            <button class="btn btn-sm btn-gold w-100">Filter</button>
        </div>
    </form>

    <?php if ($rows): ?>
        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Tipe</th>
                        <th>Pelanggan</th>
                        <th>Konteks</th>
                        <th>Nominal</th>
                        <th>Status</th>
                        <th>Upload</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= esc($r['kode']); ?></td>
                            <td><span class="badge text-bg-<?= $r['tipe'] === 'cicilan' ? 'warning' : 'info'; ?>"><?= ucfirst($r['tipe']); ?></span></td>
                            <td><?= esc($r['nama_user'] ?? $r['nama_nasabah'] ?? '-'); ?></td>
                            <td>
                                <?php if ($r['tipe'] === 'cicilan'): ?>
                                    <?= esc($r['kode_kredit'] ?? '-'); ?> · Angsuran ke-<?= esc($r['angsuran_ke'] ?? '?'); ?>
                                <?php else: ?>
                                    Pesanan <?= esc($r['kode_pesanan'] ?? '-'); ?>
                                    <?php if (!empty($r['no_rekening']) || !empty($r['nama_pengirim'])): ?>
                                        <div class="small text-muted-mg">Transfer:
                                            <?= esc($r['nama_pengirim'] ?? ''); ?><?= !empty($r['bank_pengirim']) ? ' · ' . esc($r['bank_pengirim']) : ''; ?><?= !empty($r['no_rekening']) ? ' · ' . esc($r['no_rekening']) : ''; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td><?= esc(format_rupiah($r['nominal'])); ?></td>
                            <td><span class="badge text-bg-<?= esc(status_badge_class($r['status'])); ?>"><?= ucfirst($r['status']); ?></span></td>
                            <td><?= esc(format_tanggal($r['created_at'], 'd M Y H:i')); ?></td>
                            <td class="text-end" style="white-space:nowrap;">
                                <a href="<?= base_url('/admin/pembayaran/' . $r['id'] . '/bukti'); ?>" target="_blank"
                                    rel="noopener" class="btn btn-sm btn-outline-gold rounded-pill">Bukti</a>
                                <?php if ($r['status'] === 'menunggu'): ?>
                                    <form action="<?= base_url('/admin/pembayaran/' . $r['id'] . '/verifikasi'); ?>" method="post"
                                        class="d-inline" onsubmit="return confirm('Verifikasi pembayaran ini?');">
                                        <?= csrf_field(); ?>
                                        <button class="btn btn-sm btn-gold rounded-pill">Verifikasi</button>
                                    </form>
                                    <form action="<?= base_url('/admin/pembayaran/' . $r['id'] . '/tolak'); ?>" method="post" class="d-inline js-tolak">
                                        <?= csrf_field(); ?>
                                        <input type="hidden" name="catatan_admin">
                                        <button class="btn btn-sm btn-outline-danger rounded-pill">Tolak</button>
                                    </form>
                                <?php elseif ($r['status'] === 'terverifikasi'): ?>
                                    <a href="<?= base_url('/admin/pembayaran/' . $r['id'] . '/wa'); ?>" target="_blank"
                                        rel="noopener" class="btn btn-sm btn-whatsapp rounded-pill">Kirim WA</a>
                                <?php elseif ($r['status'] === 'ditolak' && !empty($r['catatan_admin'])): ?>
                                    <span class="small text-muted-mg d-block">Alasan: <?= esc($r['catatan_admin']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <?= view('partials/empty_state', ['title' => 'Tidak ada bukti pembayaran']); ?>
    <?php endif; ?>
</div>
<?= $this->endSection(); ?>

<?= $this->section('scripts'); ?>
<script>
    document.querySelectorAll('form.js-tolak').forEach((f) => {
        f.addEventListener('submit', (e) => {
            const alasan = prompt('Alasan penolakan bukti pembayaran:');
            if (!alasan) { e.preventDefault(); return; }
            f.querySelector('input[name="catatan_admin"]').value = alasan;
        });
    });
</script>
<?= $this->endSection(); ?>
