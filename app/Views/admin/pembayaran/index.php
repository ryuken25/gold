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
                <option value="dp" <?= $f['tipe'] === 'dp' ? 'selected' : ''; ?>>Uang Muka (DP)</option>
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
                            <?php
                                $tipeBadge = ['cicilan' => 'warning', 'dp' => 'primary', 'cash' => 'info'][$r['tipe']] ?? 'info';
                                $tipeLabel = $r['tipe'] === 'dp' ? 'DP' : ucfirst($r['tipe']);
                            ?>
                            <td><span class="badge text-bg-<?= $tipeBadge; ?>"><?= esc($tipeLabel); ?></span></td>
                            <td><?= esc($r['nama_user'] ?? $r['nama_nasabah'] ?? '-'); ?></td>
                            <td>
                                <?php if ($r['tipe'] === 'cicilan'): ?>
                                    <?= esc($r['kode_kredit'] ?? '-'); ?> · Angsuran ke-<?= esc($r['angsuran_ke'] ?? '?'); ?>
                                <?php else: ?>
                                    Pesanan <?= esc($r['kode_pesanan'] ?? '-'); ?><?= $r['tipe'] === 'dp' ? ' · Uang Muka' : ''; ?>
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
                                    <button type="button" class="btn btn-sm btn-gold rounded-pill js-verif-bukti"
                                        data-id="<?= esc($r['id']); ?>">Verifikasi</button>

                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill js-tolak-bukti"
                                        data-id="<?= esc($r['id']); ?>">Tolak</button>
                                <?php elseif ($r['status'] === 'terverifikasi'): ?>
                                    <span class="badge bg-success">Terverifikasi</span>
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
(function() {
    const CSRF = '<?= csrf_token() ?>';
    const CSRF_VAL = '<?= csrf_hash() ?>';

    async function postAjax(url, body) {
        const fd = new FormData();
        fd.append(CSRF, CSRF_VAL);
        for (const [k, v] of Object.entries(body || {})) fd.append(k, v);
        const res = await fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        window.location.reload();
    }

    // VERIFIKASI BUKTI
    document.querySelectorAll('.js-verif-bukti').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            MahenDialog.confirm({
                title: 'Verifikasi Pembayaran',
                message: 'Pastikan nominal, rekening pengirim, dan bukti pembayaran sudah sesuai sebelum melanjutkan.',
                confirmText: 'Ya, Verifikasi',
                onConfirm: (finish) => { postAjax('/admin/pembayaran/' + id + '/verifikasi', {}); finish(); }
            });
        });
    });

    // TOLAK BUKTI
    document.querySelectorAll('.js-tolak-bukti').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            MahenDialog.form({
                title: 'Tolak Bukti Pembayaran',
                fields: [{ name: 'catatan_admin', label: 'Alasan Penolakan', type: 'textarea', required: true, placeholder: 'Jelaskan alasan penolakan...', rows: 3 }],
                submitText: 'Tolak',
                submitClass: 'btn-danger',
                onsubmit: (data, finish) => { postAjax('/admin/pembayaran/' + id + '/tolak', { catatan_admin: data.catatan_admin }); finish(); }
            });
        });
    });
})();
</script>
<?= $this->endSection(); ?>
