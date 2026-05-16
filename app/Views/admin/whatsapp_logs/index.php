<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="premium-card p-4">
    <form method="get" class="row g-3 mb-4">
        <div class="col-md-4"><select name="tipe" class="form-select"><option value="">Semua Tipe</option><?php foreach (['pengajuan_kredit','pengingat_jatuh_tempo','pembayaran_diterima','kredit_lunas','info_transaksi'] as $item): ?><option value="<?= esc($item); ?>" <?= $tipe === $item ? 'selected' : ''; ?>><?= esc($item); ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><select name="status" class="form-select"><option value="">Semua Status</option><?php foreach (['dibuat','dibuka','dikirim_manual','gagal'] as $item): ?><option value="<?= esc($item); ?>" <?= $status === $item ? 'selected' : ''; ?>><?= esc($item); ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4 d-flex gap-2"><button class="btn btn-outline-gold flex-fill">Filter</button><a href="<?= base_url('/admin/whatsapp-logs'); ?>" class="btn btn-outline-secondary flex-fill">Reset</a></div>
    </form>
    <?php if ($rows): ?><div class="table-responsive"><table class="table table-modern align-middle"><thead><tr><th>Tipe</th><th>Target</th><th>Status</th><th>Pesan</th><th></th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= esc($row['tipe']); ?></td><td><?= esc($row['nama_tujuan'] ?: $row['target']); ?><div class="small text-muted"><?= esc($row['tujuan_nomor']); ?></div></td><td><span class="badge text-bg-<?= esc(status_badge_class($row['status'])); ?>"><?= esc($row['status']); ?></span></td><td class="small"><pre class="mb-0 small text-wrap font-monospace"><?= esc($row['pesan']); ?></pre></td><td class="text-end"><?php if ($row['wa_url']): ?><a href="<?= esc($row['wa_url']); ?>" class="btn btn-sm btn-whatsapp rounded-pill" target="_blank">Buka ulang</a><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><div class="mt-3"><?= $pager->links(); ?></div><?php else: ?><?= view('partials/empty_state', ['title' => 'Belum ada log WhatsApp']); ?><?php endif; ?>
</div>
<?= $this->endSection(); ?>
