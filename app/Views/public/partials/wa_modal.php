<div class="modal fade wa-modal" id="waPengajuanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-header border-0 p-4 pb-2">
                <div class="pe-3">
                    <span class="section-eyebrow d-block mb-2">Pemesanan</span>
                    <h5 class="modal-title fw-black mb-1">Ajukan Pembelian</h5>
                    <p class="text-muted-mg mb-0">Pilih metode pembayaran lalu isi data diri Anda. Pesanan diproses
                        langsung di sistem.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-4">
                <form id="waPengajuanForm" action="<?= base_url('/pesanan'); ?>" method="post"
                    enctype="multipart/form-data" data-csrf-name="<?= csrf_token(); ?>">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="produk_id" id="wa_produk_id">

                    <?php $waPelanggan = function_exists('current_pelanggan') ? current_pelanggan() : null; ?>

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
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control form-control-lg" name="nama" id="wa_nama"
                                value="<?= esc($waPelanggan['nama'] ?? ''); ?>" autocomplete="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No. Telepon</label>
                            <input type="tel" class="form-control form-control-lg" name="no_telepon" id="wa_no_telepon"
                                value="<?= esc($waPelanggan['no_telepon'] ?? ''); ?>" autocomplete="tel"
                                placeholder="08xxxxxxxxxx" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Produk</label>
                            <input type="text" class="form-control form-control-lg" id="wa_produk_label" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control form-control-lg" name="alamat" id="wa_alamat" rows="2"
                                required></textarea>
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

                        <?php // Uang muka (DP) — Pilihan Dropdown ?>
                        <div class="col-12 wa-kredit-field">
                            <label class="form-label" for="wa_uang_muka">Uang Muka (DP)</label>
                            <select class="form-select form-select-lg" name="uang_muka" id="wa_uang_muka">
                                <option value="200000" selected>Rp 200.000</option>
                                <option value="500000">Rp 500.000</option>
                                <option value="1000000">Rp 1.000.000</option>
                            </select>
                            <div class="form-text">Pilih nominal Uang Muka (DP). Uang muka harus lebih kecil dari harga produk.</div>
                        </div>

                        <?php // Estimasi live (khusus kredit) ?>
                        <div class="col-12 wa-kredit-field">
                            <div class="simulation-box p-3">
                                <div class="simulation-row"><span>Total Harga Kredit</span><strong
                                        id="wa_est_total">-</strong></div>
                                <div class="simulation-row"><span>Uang Muka (DP)</span><strong id="wa_est_dp">-</strong>
                                </div>
                                <div class="simulation-row"><span>Sisa Diangsur</span><strong id="wa_est_sisa">-</strong>
                                </div>
                                <div class="simulation-row"><span>Estimasi Angsuran</span><strong
                                        id="wa_est_angsuran">-</strong></div>
                            </div>
                        </div>

                        <?php // Upload KTP — wajib untuk semua metode ?>
                        <div class="col-12" id="wa_ktp_wrapper">
                            <label class="form-label">Foto KTP <span class="text-danger">*</span></label>
                            <input type="file" class="form-control form-control-lg" name="foto_ktp" id="wa_foto_ktp"
                                accept="image/jpeg,image/png" required>
                            <div class="form-text">Format JPG/PNG, maks. 3 MB. Wajib untuk semua metode pembelian.</div>
                        </div>

                        <?php // UPDATED: Info rekening bank untuk transfer ?>
                        <div class="col-12">
                            <div class="alert border-0 shadow-sm mb-0" style="background:linear-gradient(135deg, #f0f9ff, #e0f2fe); border-left:4px solid #0ea5e9 !important;">
                                <div class="d-flex align-items-start gap-3">
                                    <i class="bi bi-bank2 fs-4 text-primary"></i>
                                    <div class="flex-grow-1">
                                        <strong class="text-primary">Rekening Transfer Pembayaran</strong>
                                        <div class="mt-2">
                                            <span class="text-muted small d-block">Bank</span>
                                            <strong class="fs-5">BRI</strong>
                                        </div>
                                        <div class="mt-2">
                                            <span class="text-muted small d-block">Nomor Rekening</span>
                                            <strong class="fs-5 font-monospace">477-9010-0935-6536</strong>
                                            <button type="button" class="btn btn-sm btn-outline-secondary ms-2"
                                                onclick="navigator.clipboard.writeText('477901009356536').then(()=>{this.textContent='✓ Tersalin'; setTimeout(()=>this.textContent='Salin',2000)})">Salin</button>
                                        </div>
                                        <div class="mt-2">
                                            <span class="text-muted small d-block">Atas Nama</span>
                                            <strong>I Gusti Ayu Ari Satriani</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php // Upload bukti pembayaran — wajib untuk kedua metode. ?>
                        <div class="col-12">
                            <label class="form-label" id="wa_bukti_label">Bukti Pembayaran <span class="text-danger">*</span></label>
                            <input type="file" class="form-control form-control-lg" name="bukti" id="wa_bukti"
                                accept="image/jpeg,image/png,application/pdf" required>
                            <div class="form-text" id="wa_bukti_help">
                                Format JPG/PNG/PDF, maks. 3 MB. Unggah bukti transfer/ pembayaran Anda — akan diverifikasi admin.
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small">Nama Pengirim <span class="text-muted-mg">(opsional)</span></label>
                                <input type="text" name="nama_pengirim" class="form-control" maxlength="150"
                                    value="<?= esc(old('nama_pengirim')); ?>" placeholder="Nama di rekening">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">No. Rekening <span class="text-muted-mg">(opsional)</span></label>
                                <input type="text" name="no_rekening" class="form-control" maxlength="50"
                                    value="<?= esc(old('no_rekening')); ?>" placeholder="mis. 1234567890">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Bank <span class="text-muted-mg">(opsional)</span></label>
                                <input type="text" name="bank_pengirim" class="form-control" maxlength="50"
                                    value="<?= esc(old('bank_pengirim')); ?>" placeholder="mis. BCA / BRI">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Ringkasan Pesanan</label>
                            <textarea class="form-control font-monospace small" id="wa_preview" rows="8"
                                readonly>Isi data untuk melihat ringkasan pesanan.</textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 p-4 pt-3 justify-content-between">
                <span class="small text-muted-mg">Pesanan akan diverifikasi admin di sistem. Pantau statusnya di menu
                    Pesanan. Anda akan menerima email notifikasi otomatis.</span>
                <button type="submit" form="waPengajuanForm" class="btn btn-gold px-4">Kirim Pesanan</button>
            </div>
        </div>
    </div>
</div>

<!-- UPDATED: Popup sukses order -->
<div class="modal fade" id="orderSuccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 text-center p-5" style="border-radius:24px;">
            <div class="mb-3" style="font-size:3.5rem;">✅</div>
            <h4 class="fw-black mb-2">Pesanan Berhasil Dikirim!</h4>
            <p class="text-muted-mg mb-1">Pesanan Anda sedang diproses.</p>
            <p class="text-muted-mg mb-3">Mohon tunggu diverifikasi admin. Anda akan menerima email konfirmasi.</p>
            <button type="button" class="btn btn-gold px-4" id="orderSuccessOk">Lihat Pesanan Saya</button>
        </div>
    </div>
</div>
