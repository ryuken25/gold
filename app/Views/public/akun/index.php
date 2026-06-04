<?= $this->extend('layouts/public'); ?>

<?= $this->section('content'); ?>
<section class="section-padding bg-cream-soft akun-section">
    <div class="container-mg">
        <div class="section-heading mb-4">
            <p class="section-eyebrow">Akun Saya</p>
            <h2 class="fw-black mb-1">Halo, <?= esc($pelanggan['nama']); ?>!</h2>
            <p class="text-muted-mg mb-0">Ringkasan pesanan dan kredit emas Anda.</p>
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-lg-3">
                <?= $this->include('public/akun/_nav'); ?>
            </div>

            <div class="col-lg-9">
                <div class="row g-3 g-lg-4 mb-1">
                    <div class="col-sm-6 col-xl-4">
                        <div class="akun-stat feature-card p-4">
                            <span class="akun-stat-label">Total Pesanan</span>
                            <span class="akun-stat-value"><?= esc($jumlahPesanan); ?></span>
                            <a href="<?= base_url('/akun/pesanan'); ?>" class="akun-stat-link">Lihat riwayat →</a>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <div class="akun-stat feature-card p-4">
                            <span class="akun-stat-label">Kredit Aktif</span>
                            <span class="akun-stat-value"><?= esc(count($kreditAktif)); ?></span>
                            <span class="akun-stat-link text-muted-mg">Kredit berjalan</span>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="akun-stat feature-card p-4">
                            <span class="akun-stat-label">Status Akun</span>
                            <span class="akun-stat-value akun-stat-sm"><?= $akunTertaut ? 'Tertaut' : 'Belum tertaut'; ?></span>
                            <span class="akun-stat-link text-muted-mg">Tautan nasabah kredit</span>
                        </div>
                    </div>
                </div>

                <?php if ($nextAngsuran): ?>
                    <?php $j = $nextAngsuran['jadwal']; $k = $nextAngsuran['kredit']; ?>
                    <div class="akun-duecard premium-card p-4 p-lg-5 mt-1">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                            <div>
                                <span class="section-eyebrow d-block mb-2">Jatuh Tempo Berikutnya</span>
                                <h3 class="fw-black mb-1"><?= esc(format_rupiah($j['nominal_tagihan'])); ?></h3>
                                <p class="text-muted-mg mb-0">
                                    Angsuran ke-<?= esc($j['angsuran_ke']); ?>
                                    <?php if ($k): ?>· <?= esc($k['kode_kredit']); ?><?php endif; ?>
                                </p>
                            </div>
                            <div class="akun-duecard-date text-md-end">
                                <span class="akun-due-label">Tanggal</span>
                                <strong class="akun-due-value"><?= esc(format_tanggal($j['tanggal_jatuh_tempo'], 'd M Y')); ?></strong>
                                <?php if ($k): ?>
                                    <a href="<?= base_url('/akun/kredit/' . $k['id']); ?>" class="btn btn-gold mt-2">Lihat Jadwal</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="akun-duecard feature-card p-4 p-lg-5 mt-1">
                        <span class="section-eyebrow d-block mb-2">Jatuh Tempo Berikutnya</span>
                        <h5 class="fw-bold mb-1">Tidak ada angsuran jatuh tempo</h5>
                        <p class="text-muted-mg mb-0">
                            <?php if (!$akunTertaut): ?>
                                Akun Anda belum ditautkan ke data nasabah kredit. Hubungi admin MahenGold setelah pengajuan kredit disetujui.
                            <?php else: ?>
                                Anda belum memiliki kredit aktif. Mulai dari <a href="<?= base_url('/katalog'); ?>">katalog</a>.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($kreditAktif)): ?>
                    <div class="feature-card p-4 mt-4">
                        <h5 class="fw-bold mb-3">Kredit Aktif</h5>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Tenor</th>
                                        <th class="text-end">Sisa Piutang</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kreditAktif as $k): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= esc($k['kode_kredit']); ?></td>
                                            <td><?= esc($k['tenor_bulan']); ?> bln · <?= esc(ucfirst($k['periode_angsuran'])); ?></td>
                                            <td class="text-end"><?= esc(format_rupiah($k['sisa_piutang'])); ?></td>
                                            <td class="text-end">
                                                <a href="<?= base_url('/akun/kredit/' . $k['id']); ?>"
                                                    class="btn btn-sm btn-outline-gold">Detail</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection(); ?>
