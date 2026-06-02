<div class="modal fade wa-modal" id="waPengajuanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-header border-0 p-4 pb-2">
                <div class="pe-3">
                    <span class="section-eyebrow d-block mb-2">Pengajuan</span>
                    <h5 class="modal-title fw-black mb-1">Ajukan Pembelian</h5>
                    <p class="text-muted-mg mb-0">Pilih metode pembayaran lalu isi data diri Anda.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-4">
                <form id="waPengajuanForm" action="<?= base_url('/wa/pengajuan'); ?>" method="post"
                    enctype="multipart/form-data">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="produk_id" id="wa_produk_id">

                    <?php // Pilihan metode pembayaran (toggle card) ?>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Metode Pembayaran</label>
                        <div class="metode-toggle">
                            <label class="metode-option is-active" for="metode_kredit">
                                <input class="metode-option-input" type="radio" name="metode_pembayaran"
                                    id="metode_kredit" value="kredit" checked>
                                <span class="metode-option-icon" aria-hidden="true">💳</span>
                                <span class="metode-option-body">
                                    <span class="metode-option-title">Kredit</span>
                                    <span class="metode-option-desc">Cicil ringan tiap periode</span>
                                </span>
                                <span class="metode-option-check" aria-hidden="true"></span>
                            </label>
                            <label class="metode-option" for="metode_cash">
                                <input class="metode-option-input" type="radio" name="metode_pembayaran"
                                    id="metode_cash" value="cash">
                                <span class="metode-option-icon" aria-hidden="true">💰</span>
                                <span class="metode-option-body">
                                    <span class="metode-option-title">Cash</span>
                                    <span class="metode-option-desc">Bayar sekaligus saat transaksi</span>
                                </span>
                                <span class="metode-option-check" aria-hidden="true"></span>
                            </label>
                        </div>
                    </div>

                    <div class="row g-3">
                        <?php $waPelanggan = function_exists('current_pelanggan') ? current_pelanggan() : null; ?>
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control form-control-lg" name="nama" id="wa_nama"
                                value="<?= esc($waPelanggan['nama'] ?? ''); ?>" autocomplete="name" required>
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

                        <?php // Field khusus kredit ?>
                        <div class="col-md-6 wa-kredit-field">
                            <label class="form-label">Tenor</label>
                            <select class="form-select form-select-lg" name="tenor_bulan" id="wa_tenor">
                                <option value="6">6 bulan</option>
                                <option value="10">10 bulan</option>
                                <option value="12" selected>12 bulan</option>
                            </select>
                        </div>
                        <div class="col-md-6 wa-kredit-field">
                            <label class="form-label">Periode Angsuran</label>
                            <select class="form-select form-select-lg" name="periode_angsuran" id="wa_periode">
                                <option value="bulanan">Bulanan</option>
                                <option value="mingguan">Mingguan</option>
                            </select>
                        </div>

                        <div class="col-12 wa-kredit-field">
                            <div class="wa-summary p-3">
                                <div class="simulation-row"><span>Total Kredit</span><strong
                                        id="wa_total_kredit">-</strong></div>
                                <div class="simulation-row"><span>Jumlah Periode</span><strong
                                        id="wa_jumlah_periode">-</strong></div>
                                <div class="simulation-row"><span>Estimasi Angsuran</span><strong
                                        id="wa_nominal_angsuran">-</strong></div>
                            </div>
                        </div>

                        <?php // Ringkasan cash ?>
                        <div class="col-12 wa-cash-field" style="display:none;">
                            <div class="wa-summary p-3">
                                <div class="simulation-row"><span>Harga Pokok</span><strong
                                        id="wa_harga_pokok_cash">-</strong></div>
                                <p class="text-muted-mg small mb-0 mt-2">Pembayaran dilakukan sekaligus saat
                                    transaksi.</p>
                            </div>
                        </div>

                        <?php // Upload KTP hanya untuk kredit ?>
                        <div class="col-12 wa-kredit-field" id="wa_ktp_wrapper">
                            <label class="form-label">Foto KTP <span class="text-danger">*</span></label>
                            <input type="file" class="form-control form-control-lg" name="foto_ktp" id="wa_foto_ktp"
                                accept="image/jpeg,image/png">
                            <div class="form-text">Format JPG/PNG, maks. 3 MB. Wajib untuk pengajuan kredit.</div>
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
