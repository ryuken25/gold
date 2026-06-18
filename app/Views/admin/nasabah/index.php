<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="premium-card p-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <form class="d-flex gap-2" method="get">
            <input type="text" class="form-control" name="q" value="<?= esc($q); ?>" placeholder="Cari nasabah">
            <button class="btn btn-outline-gold rounded-pill px-4">Cari</button>
        </form>
        <a href="<?= base_url('/admin/nasabah/create'); ?>" class="btn btn-gold rounded-pill px-4">Tambah Nasabah</a>
    </div>
    <?php if ($nasabah): ?>
        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Telepon</th>
                        <th class="text-center">Kredit Aktif</th>
                        <th class="text-end">Sisa Piutang</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nasabah as $row): ?>
                        <tr>
                            <td><?= esc($row['kode_nasabah']); ?></td>
                            <td><?= esc($row['nama']); ?></td>
                            <td><?= esc($row['no_telepon']); ?></td>
                            <td class="text-center"><?= esc($row['kredit_aktif'] ?? 0); ?></td>
                            <td class="text-end"><?= esc(format_rupiah($row['sisa_piutang'] ?? 0)); ?></td>
                            <td class="text-end">
                                <a href="<?= base_url('/admin/nasabah/' . $row['id'] . '/kartu-piutang'); ?>"
                                    class="btn btn-sm btn-outline-gold rounded-pill">Kartu Piutang</a>
                                <a href="<?= base_url('/admin/nasabah/' . $row['id'] . '/edit'); ?>"
                                    class="btn btn-sm btn-outline-gold rounded-pill">Edit</a>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill"
                                    onclick="var self = this; MahenDialog.confirm({
                                        title: 'Hapus Nasabah',
                                        message: 'Apakah Anda yakin ingin menghapus nasabah <?= esc(addslashes($row['nama'])); ?>? Data yang dihapus tidak dapat dikembalikan.',
                                        confirmText: 'Ya, Hapus',
                                        confirmClass: 'btn-danger',
                                        onConfirm: function(finish) {
                                            document.getElementById('formHapus<?= esc($row['id']); ?>').submit();
                                            finish();
                                        }
                                    })">Hapus</button>
                                <form id="formHapus<?= esc($row['id']); ?>" action="<?= base_url('/admin/nasabah/' . $row['id'] . '/delete'); ?>" method="post" class="d-none">
                                    <?= csrf_field(); ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3"><?= $pager->links(); ?></div>
    <?php else: ?>
        <?= view('partials/empty_state', ['title' => 'Belum ada nasabah']); ?>
    <?php endif; ?>
</div>
<?= $this->endSection(); ?>