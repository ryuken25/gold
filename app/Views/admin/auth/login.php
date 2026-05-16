<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= esc($pageTitle); ?>
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/mahengold.css'); ?>" rel="stylesheet">
</head>

<body class="login-body">
    <div class="container py-5">
        <?= $this->include('partials/alerts'); ?>
        <div class="row justify-content-center align-items-center min-vh-100 g-4">
            <div class="col-lg-5">
                <div class="glass-card p-4 p-lg-5">
                    <div class="text-center mb-4">
                        <span class="brand-mark mx-auto mb-3">
                            <?= esc($pengaturan['logo_text']); ?>
                        </span>
                        <h1 class="h3 fw-bold text-white mb-2">Admin
                            <?= esc($pengaturan['nama_toko']); ?>
                        </h1>
                        <p class="text-light-emphasis mb-0">Login untuk mengelola penjualan dan kredit emas.</p>
                    </div>
                    <form action="<?= base_url('/admin/login'); ?>" method="post">
                        <?= csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label text-white">Username</label>
                            <input type="text" class="form-control form-control-lg" name="username"
                                value="<?= esc(old('username', 'admin')); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-white">Password</label>
                            <input type="password" class="form-control form-control-lg" name="password" value="admin123"
                                required>
                        </div>
                        <button type="submit" class="btn btn-gold w-100 btn-lg rounded-pill">Login Admin</button>
                    </form>
                    <div class="bg-dark-subtle bg-opacity-10 rounded-4 p-3 mt-4 small text-light-emphasis">
                        Demo akun: <strong>admin</strong> / <strong>admin123</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>