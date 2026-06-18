<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= esc($pageTitle ?? 'Admin MahenGold'); ?>
    </title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/mahengold.css'); ?>" rel="stylesheet">
</head>

<body class="admin-body">
    <div class="admin-shell">
        <?= $this->include('partials/admin/sidebar'); ?>
        <div class="admin-backdrop" id="adminBackdrop"></div>
        <main class="admin-main">
            <?= $this->include('partials/admin/header'); ?>
            <div class="container-fluid py-4 px-3 px-lg-4">
                <?= $this->include('partials/alerts'); ?>
                <?= $this->renderSection('content'); ?>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('assets/js/mahengold.js'); ?>"></script>
    <?= $this->include('partials/mahengold_dialog'); ?>
    <?= $this->renderSection('scripts'); ?>
</body>

</html>