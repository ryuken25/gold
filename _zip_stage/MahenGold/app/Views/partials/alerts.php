<?php foreach (['success', 'error', 'warning', 'info'] as $flashKey): ?>
    <?php if (session()->getFlashdata($flashKey)): ?>
        <div class="container-fluid px-3 px-lg-4 pt-3">
            <div class="alert alert-<?= esc(flash_class($flashKey)); ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= esc(session()->getFlashdata($flashKey)); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>