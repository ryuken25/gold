<?= $this->extend('layouts/public'); ?>

<?= $this->section('content'); ?>
<?php $errors = session()->getFlashdata('errors') ?? []; ?>
<section class="section-padding bg-cream-soft akun-section">
    <div class="container-mg">
        <div class="section-heading mb-4">
            <p class="section-eyebrow">Akun Saya</p>
            <h2 class="fw-black mb-1">Profil &amp; Keamanan</h2>
            <p class="text-muted-mg mb-0">Perbarui data pribadi dan password akun Anda.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-3">
                <?= $this->include('public/akun/_nav'); ?>
            </div>

            <div class="col-lg-9">
                <div class="row g-4">
                    <div class="col-xl-6">
                        <div class="feature-card p-4 h-100">
                            <h5 class="fw-bold mb-3">Data Pribadi</h5>
                            <form action="<?= base_url('/akun/profil'); ?>" method="post" novalidate>
                                <?= csrf_field(); ?>
                                <div class="mb-3">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control form-control-lg <?= isset($errors['nama']) ? 'is-invalid' : ''; ?>"
                                        name="nama" value="<?= esc(old('nama', $pelanggan['nama'])); ?>" required>
                                    <?php if (isset($errors['nama'])): ?>
                                        <div class="invalid-feedback"><?= esc($errors['nama']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control form-control-lg <?= isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                        name="email" value="<?= esc(old('email', $pelanggan['email'])); ?>" required>
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback"><?= esc($errors['email']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Nomor Telepon</label>
                                    <input type="text" class="form-control form-control-lg <?= isset($errors['no_telepon']) ? 'is-invalid' : ''; ?>"
                                        name="no_telepon" value="<?= esc(old('no_telepon', $pelanggan['no_telepon'] ?? '')); ?>"
                                        placeholder="08xxxxxxxxxx">
                                    <?php if (isset($errors['no_telepon'])): ?>
                                        <div class="invalid-feedback"><?= esc($errors['no_telepon']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <button type="submit" class="btn btn-gold px-4">Simpan Profil</button>
                            </form>
                        </div>
                    </div>

                    <div class="col-xl-6">
                        <div class="feature-card p-4 h-100">
                            <h5 class="fw-bold mb-3">Ganti Password</h5>
                            <form action="<?= base_url('/akun/password'); ?>" method="post" novalidate>
                                <?= csrf_field(); ?>
                                <div class="mb-3">
                                    <label class="form-label">Password Lama</label>
                                    <input type="password" class="form-control form-control-lg" name="password_lama"
                                        autocomplete="current-password" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password Baru</label>
                                    <input type="password" class="form-control form-control-lg" name="password_baru"
                                        autocomplete="new-password" required>
                                    <div class="form-text">Minimal 8 karakter.</div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Konfirmasi Password Baru</label>
                                    <input type="password" class="form-control form-control-lg" name="password_konfirmasi"
                                        autocomplete="new-password" required>
                                </div>
                                <button type="submit" class="btn btn-outline-gold px-4">Ganti Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection(); ?>
