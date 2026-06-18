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
                        <?= view('partials/empty_state', [
                            'title' => 'Belum ada kredit',
                            'description' => 'Anda belum memiliki kredit emas aktif di MahenGold.',
                        ]); ?>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Produk</th>
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

                                        if ($status === 'lunas') {
                                            $rowClass = 'row-lunas';
                                        } elseif ($status === 'aktif' && $sisaPiutang > 0) {
                                            // Check effective state
                                            $jadwal = (new \App\Models\JadwalAngsuranModel())
                                                ->where('kredit_id', $k['id'])
                                                ->where('status !=', 'dibayar')
                                                ->orderBy('tanggal_jatuh_tempo', 'ASC')
                                                ->first();
                                            $isOverdue = $jadwal && strtotime($jadwal['tanggal_jatuh_tempo']) < strtotime('today');
                                            $rowClass = $isOverdue ? 'row-overdue' : '';
                                        } else {
                                            $rowClass = '';
                                        }
                                        ?>
                                        <tr class="<?= esc($rowClass); ?> clickable-row" data-href="<?= base_url('/akun/kredit/' . $k['id']); ?>" tabindex="0" role="link" style="cursor:pointer;">
                                            <td><span class="fw-semibold"><?= esc($k['kode_kredit']); ?></span></td>
                                            <td><?= esc($k['nama_produk'] ?? '-'); ?></td>
                                            <td><?= esc(format_rupiah($totalHarga)); ?></td>
                                            <td><?= esc(format_rupiah($totalTerbayar)); ?></td>
                                            <td>
                                                <?php if ($status === 'lunas'): ?>
                                                    <span class="text-success fw-bold">Lunas</span>
                                                <?php else: ?>
                                                    <?= esc(format_rupiah($sisaPiutang)); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($status === 'lunas'): ?>
                                                    <span class="badge bg-success">Lunas</span>
                                                <?php elseif ($status === 'aktif'): ?>
                                                    <span class="badge bg-primary">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= esc(ucfirst($status)); ?></span>
                                                <?php endif; ?>
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
