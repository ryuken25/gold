<?= $this->extend('layouts/public'); ?>

<?= $this->section('content'); ?>
<section class="section-padding bg-cream-soft" style="min-height: 70vh; display:flex; align-items:center;">
    <div class="container-mg">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="glass-card p-4 p-lg-5">
                    <div class="text-center mb-4">
                        <img src="<?= base_url('assets/images/mahengold/logo-mg.svg'); ?>" alt="MahenGold" width="60" height="60" class="mb-3">
                        <h2 class="fw-black mb-1">Daftar Akun</h2>
                        <p class="text-muted-mg mb-0">Buat akun pelanggan MahenGold gratis.</p>
                    </div>

                    <?php if (session()->getFlashdata('errors')): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach (session()->getFlashdata('errors') as $err): ?>
                                    <li><?= esc($err); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="<?= base_url('/register'); ?>" method="post" novalidate>
                        <?= csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" name="nama"
                                value="<?= esc(old('nama')); ?>" autocomplete="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control form-control-lg" name="email"
                                value="<?= esc(old('email')); ?>" autocomplete="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor WhatsApp</label>
                            <input type="text" class="form-control form-control-lg" name="no_telepon"
                                value="<?= esc(old('no_telepon')); ?>" autocomplete="tel"
                                placeholder="Opsional, mis. 081234567890">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control form-control-lg" name="password"
                                autocomplete="new-password" required minlength="8">
                            <div class="form-text">Minimal 8 karakter.</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control form-control-lg" name="password_confirm"
                                autocomplete="new-password" required>
                        </div>
                        <button type="submit" class="btn btn-gold btn-lg w-100 mb-3">Buat Akun</button>
                        <p class="text-center text-muted-mg mb-0">Sudah punya akun?
                            <a href="<?= base_url('/login'); ?>" class="text-gold-soft fw-semibold">Masuk di sini</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection(); ?>
