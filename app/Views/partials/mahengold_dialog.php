<?php
/**
 * MahenDialog — Reusable dialog system partial.
 *
 * Include this in layouts AFTER bootstrap.js and mahengold.js:
 *   <?= $this->include('partials/mahengold_dialog'); ?>
 *
 * Dialog JS loaded by layout; this partial only renders flash messages.
 */
?>
<script>
(function () {
    <?php
    $flashKeys = ['success', 'error', 'warning', 'info'];
    foreach ($flashKeys as $key):
        $flashValue = session()->getFlashdata($key);
        if ($flashValue !== null && $flashValue !== ''):
            $jsonKey = json_encode($key);
            $jsonVal = json_encode($flashValue);
    ?>
    (function () {
        var type = <?= $jsonKey ?>;
        var message = <?= $jsonVal ?>;
        if (!message) return;
        // Short messages → toast, longer messages → dialog
        if (message.length <= 80 && type !== 'error') {
            if (window.MahenToast) {
                MahenToast.show({ message: message, type: type, duration: type === 'error' ? 6000 : 4000 });
            }
        } else {
            if (window.MahenDialog) {
                var titles = {
                    success: 'Berhasil!',
                    error: 'Terjadi Kesalahan',
                    warning: 'Perhatian',
                    info: 'Informasi'
                };
                MahenDialog[type]({
                    title: titles[type] || 'Pemberitahuan',
                    message: message
                });
            }
        }
    })();
    <?php
        endif;
    endforeach;
    ?>
})();
</script>
