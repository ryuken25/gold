<?= $this->extend('layouts/admin'); ?>
<?= $this->section('content'); ?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="premium-card p-4 h-100">
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
                <div><span>Total Terbayar</span><strong><?= esc(format_rupiah($kredit['total_terbayar'])); ?></strong>
                </div>
                <div><span>Sisa Piutang</span><strong><?= esc(format_rupiah($kredit['sisa_piutang'])); ?></strong></div>
                <div>
                    <span>Angsuran</span><strong><?= esc(format_rupiah($kredit['nominal_angsuran'])); ?>/<?= esc(periode_label($kredit['periode_angsuran'])); ?></strong>
                </div>
                <div><span>Jatuh Tempo Pertama</span><strong><?= esc(format_tanggal($jadwalPertama)); ?></strong></div>
            </div>
            <div class="d-grid gap-2">
                <a class="btn btn-whatsapp rounded-pill"
                    href="<?= base_url('/admin/kredit/' . $kredit['id'] . '/wa-info'); ?>">Kirim Info Transaksi via
                    WhatsApp</a>
                <?php if ($kredit['status'] === 'lunas'): ?><a class="btn btn-outline-gold rounded-pill"
                        href="<?= base_url('/admin/kredit/' . $kredit['id'] . '/wa-lunas'); ?>">Kirim Notifikasi
                        Lunas</a><?php endif; ?>
                <?php if ($kredit['status'] === 'aktif'): ?><a class="btn btn-outline-gold rounded-pill"
                        href="<?= base_url('/admin/pembayaran/create?kredit_id=' . $kredit['id']); ?>">Catat
                        Pembayaran</a><?php endif; ?>
                <?php if ($kredit['status'] === 'aktif'): ?>
                    <form action="<?= base_url('/admin/kredit/' . $kredit['id'] . '/batalkan'); ?>" method="post"
                        onsubmit="return confirm('Batalkan kredit ini?');"><?= csrf_field(); ?><button
                            class="btn btn-outline-danger rounded-pill w-100">Batalkan Kredit</button></form><?php endif; ?>
            </div>
        </div>
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
                            <th></th>
                        </tr>
                    </thead>
                    <tbody><?php foreach ($jadwal as $row): ?>
                            <tr>
                                <td><?= esc($row['angsuran_ke']); ?></td>
                                <td><?= esc(format_tanggal($row['tanggal_jatuh_tempo'])); ?></td>
                                <td><?= esc(format_rupiah($row['nominal_tagihan'])); ?></td>
                                <td><?= esc(format_rupiah($row['nominal_dibayar'])); ?></td>
                                <td><span
                                        class="badge text-bg-<?= esc(status_badge_class($row['status'])); ?>"><?= esc($row['status']); ?></span>
                                </td>
                                <td class="text-end"><a
                                        href="<?= base_url('/admin/kredit/' . $kredit['id'] . '/wa-pengingat/' . $row['id']); ?>"
                                        class="btn btn-sm btn-whatsapp rounded-pill">Pengingat</a></td>
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