<?= $this->extend('layouts/public'); ?>

<?= $this->section('content'); ?>
<section class="section-padding bg-cream-soft akun-section">
    <div class="container-mg">
        <div class="section-heading mb-4">
            <p class="section-eyebrow">Akun Saya</p>
            <h2 class="fw-black mb-1">Kredit Saya</h2>
            <p class="text-muted-mg mb-0">Daftar kredit emas yang sedang berjalan.</p>
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-lg-3">
                <?= $this->include('public/akun/_nav'); ?>
            </div>

            <div class="col-lg-9">
                <div class="feature-card p-4">
                    <?php if (empty($kredit)): ?>
                        <div class="empty-state text-center py-5 px-4">
                            <div class="empty-state-icon mb-3">✨</div>
                            <h5 class="fw-bold mb-2">Tidak ada kredit aktif</h5>
                            <p class="text-muted-mg mb-3">
                                Anda tidak memiliki cicilan kredit emas yang sedang berjalan saat ini.
                            </p>
                            <a href="<?= base_url('/katalog'); ?>" class="btn btn-gold rounded-pill px-4">Lihat Katalog Produk</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Produk</th>
                                        <th>DP</th>
                                        <th>Total</th>
                                        <th>Terbayar</th>
                                        <th>Sisa</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kredit as $k): ?>
                                        <?php
                                        $sisaPiutang = (float) ($k['sisa_piutang'] ?? 0);
                                        $totalHarga = (float) ($k['total_harga_kredit'] ?? 0);
                                        $totalTerbayar = (float) ($k['total_terbayar'] ?? 0);
                                        $status = $k['status'] ?? 'aktif';

                                        // Check effective state for overdue indicator
                                        $jadwal = (new \App\Models\JadwalAngsuranModel())
                                            ->where('kredit_id', $k['id'])
                                            ->where('status !=', 'dibayar')
                                            ->orderBy('tanggal_jatuh_tempo', 'ASC')
                                            ->first();
                                        $isOverdue = $jadwal && strtotime($jadwal['tanggal_jatuh_tempo']) < strtotime('today');
                                        $rowClass = $isOverdue ? 'row-overdue' : '';
                                        ?>
                                        <tr class="<?= esc($rowClass); ?> clickable-row" data-href="<?= base_url('/akun/kredit/' . $k['id']); ?>" tabindex="0" role="link" style="cursor:pointer;">
                                            <td><span class="fw-semibold"><?= esc($k['kode_kredit']); ?></span></td>
                                            <td><?= esc($k['nama_produk'] ?? '-'); ?></td>
                                            <td><?= esc(format_rupiah($k['uang_muka'] ?? 0)); ?></td>
                                            <td><?= esc(format_rupiah($totalHarga)); ?></td>
                                            <td><?= esc(format_rupiah($totalTerbayar)); ?></td>
                                            <td><?= esc(format_rupiah($sisaPiutang)); ?></td>
                                            <td>
                                                <span class="badge bg-primary">Aktif</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection(); ?>
