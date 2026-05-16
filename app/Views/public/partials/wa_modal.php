<div class="modal fade wa-modal" id="waPengajuanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-header border-0 p-4 pb-2">
                <div class="pe-3">
                    <span class="section-eyebrow d-block mb-2">Pengajuan WhatsApp</span>
                    <h5 class="modal-title fw-black mb-1">Ajukan via WhatsApp</h5>
                    <p class="text-muted-mg mb-0">Isi nama dan alamat terlebih dahulu sebelum membuka WhatsApp.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-4">
                <form id="waPengajuanForm" action="<?= base_url('/wa/pengajuan'); ?>" method="post">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="produk_id" id="wa_produk_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control form-control-lg" name="nama" id="wa_nama"
                                autocomplete="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Produk</label>
                            <input type="text" class="form-control form-control-lg" id="wa_produk_label" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control form-control-lg" name="alamat" id="wa_alamat" rows="3"
                                required></textarea>
                            <div class="form-text">Nomor WhatsApp akan mengikuti nomor pengirim chat ini.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tenor</label>
                            <select class="form-select form-select-lg" name="tenor_bulan" id="wa_tenor">
                                <option value="6">6 bulan</option>
                                <option value="10">10 bulan</option>
                                <option value="12" selected>12 bulan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Periode Angsuran</label>
                            <select class="form-select form-select-lg" name="periode_angsuran" id="wa_periode">
                                <option value="bulanan">Bulanan</option>
                                <option value="mingguan">Mingguan</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="wa-summary p-3">
                                <div class="simulation-row"><span>Total Kredit</span><strong
                                        id="wa_total_kredit">-</strong></div>
                                <div class="simulation-row"><span>Jumlah Periode</span><strong
                                        id="wa_jumlah_periode">-</strong></div>
                                <div class="simulation-row"><span>Estimasi Angsuran</span><strong
                                        id="wa_nominal_angsuran">-</strong></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Preview Template WhatsApp</label>
                            <textarea class="form-control font-monospace small" id="wa_preview" rows="10"
                                readonly>Isi nama dan alamat untuk melihat preview pesan WhatsApp.</textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 p-4 pt-3 justify-content-between">
                <span class="small text-muted-mg">Admin memverifikasi pengajuan dan pembayaran secara manual.</span>
                <button type="submit" form="waPengajuanForm" class="btn btn-whatsapp px-4">Buka WhatsApp</button>
            </div>
        </div>
    </div>
</div>