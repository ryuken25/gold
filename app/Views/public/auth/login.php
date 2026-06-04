<?= $this->extend('layouts/public'); ?>

<?= $this->section('content'); ?>
<section class="section-padding bg-cream-soft" style="min-height: 70vh; display:flex; align-items:center;">
    <div class="container-mg">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="glass-card p-4 p-lg-5">
                    <div class="text-center mb-4">
                        <img src="<?= base_url('assets/images/mahengold/logo-mg.svg'); ?>" alt="MahenGold" width="60" height="60" class="mb-3">
                        <h2 class="fw-black mb-1">Masuk</h2>
                        <p class="text-muted-mg mb-0">Login ke akun pelanggan MahenGold Anda.</p>
                    </div>

                    <?php if (session()->getFlashdata('success')): ?>
                        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div>
                    <?php endif; ?>
                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div>
                    <?php endif; ?>

                    <form action="<?= base_url('/login'); ?>" method="post" novalidate>
                        <?= csrf_field(); ?>
                        <?php if (!empty($redirect)): ?>
                            <input type="hidden" name="redirect" value="<?= esc($redirect, 'attr'); ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control form-control-lg" name="email"
                                value="<?= esc(old('email')); ?>" autocomplete="email" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control form-control-lg" name="password"
                                autocomplete="current-password" required>
                        </div>
                        <button type="submit" class="btn btn-gold btn-lg w-100 mb-3">Masuk</button>
                        <p class="text-center text-muted-mg mb-0">Belum punya akun?
                            <a href="<?= base_url('/register'); ?>" class="text-gold-soft fw-semibold">Daftar di sini</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection(); ?>
