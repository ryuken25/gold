/**
 * MahenDialog — Reusable dialog & toast system for MahenGold.
 * Vanilla JS, zero external deps beyond Bootstrap 5 modals + Bootstrap Icons.
 *
 * Usage:
 *   MahenDialog.success({ title, message, onConfirm, confirmText, showCancel, cancelText })
 *   MahenDialog.error({ title, message, onConfirm, confirmText })
 *   MahenDialog.warning({ title, message, onConfirm, confirmText, showCancel, cancelText })
 *   MahenDialog.info({ title, message, onConfirm, confirmText })
 *   MahenDialog.confirm({ title, message, onConfirm, onCancel, confirmText, cancelText, confirmClass })
 *   MahenDialog.form({ title, fields, onsubmit, submitText, cancelText })
 *   MahenToast.show({ message, type, duration })
 */
(function () {
    'use strict';

    /* ===================================================================
       Helpers
       =================================================================== */

    /** Escape HTML to prevent XSS. */
    function esc(str) {
        if (str == null) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str)));
        return div.innerHTML;
    }

    /** Build an icon element (Bootstrap Icons). */
    function iconEl(cls, colorVar) {
        var i = document.createElement('i');
        i.className = 'bi ' + cls;
        i.style.fontSize = '3.2rem';
        i.style.lineHeight = '1';
        i.style.color = colorVar;
        return i;
    }

    /** Get or create a singleton element by id. */
    function ensure(id, tag, attrs) {
        var el = document.getElementById(id);
        if (el) return el;
        el = document.createElement(tag || 'div');
        if (attrs) Object.keys(attrs).forEach(function (k) { el.setAttribute(k, attrs[k]); });
        el.id = id;
        document.body.appendChild(el);
        return el;
    }

    /** Create a spinner element. */
    function spinner() {
        var span = document.createElement('span');
        span.className = 'spinner-border spinner-border-sm';
        span.setAttribute('role', 'status');
        span.setAttribute('aria-hidden', 'true');
        return span;
    }

    /* ===================================================================
       Focus trap (returns a release function)
       =================================================================== */
    function trapFocus(modalEl) {
        function handler(e) {
            if (e.key !== 'Tab') return;
            var focusable = modalEl.querySelectorAll(
                'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            );
            if (focusable.length === 0) return;
            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            if (e.shiftKey) {
                if (document.activeElement === first) { e.preventDefault(); last.focus(); }
            } else {
                if (document.activeElement === last) { e.preventDefault(); first.focus(); }
            }
        }
        document.addEventListener('keydown', handler);
        return function () { document.removeEventListener('keydown', handler); };
    }

    /* ===================================================================
       Shared modal shell (reused for every dialog)
       =================================================================== */
    var SHELL_ID = 'mgDialogShell';
    var TOAST_ID = 'mgToastContainer';
    var activeReleaseTrap = null;

    function getShell() {
        var existing = document.getElementById(SHELL_ID);
        if (existing) return existing;

        var shell = document.createElement('div');
        shell.id = SHELL_ID;
        shell.innerHTML =
            '<div class="modal fade" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="mgDialogTitle" aria-describedby="mgDialogMessage">' +
            '  <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">' +
            '    <div class="modal-content" style="border:0;border-radius:24px;overflow:hidden;box-shadow:0 28px 70px rgba(20,10,5,.22);">' +
            '      <div class="modal-body text-center px-4 pt-5 pb-4" style="background:#fff;">' +
            '        <div id="mgDialogIcon" class="mb-3"></div>' +
            '        <h4 id="mgDialogTitle" class="fw-bold mb-2" style="color:#1c1a17;font-size:1.2rem;"></h4>' +
            '        <p id="mgDialogMessage" class="mb-0" style="color:#756858;font-size:.95rem;line-height:1.55;"></p>' +
            '      </div>' +
            '      <div id="mgDialogFooter" class="px-4 pb-4 pt-0" style="background:#fff;"></div>' +
            '    </div>' +
            '  </div>' +
            '</div>';
        document.body.appendChild(shell);
        return shell;
    }

    function getToastContainer() {
        var el = ensure(TOAST_ID, 'div');
        el.setAttribute('aria-live', 'polite');
        el.setAttribute('aria-atomic', 'true');
        // Fixed positioning, top-right
        el.style.cssText =
            'position:fixed;top:24px;right:24px;z-index:10900;display:flex;flex-direction:column;gap:10px;pointer-events:none;max-width:380px;';
        return el;
    }

    /* ===================================================================
       Core show / hide
       =================================================================== */
    function showDialog(opts) {
        var shell = getShell();
        var bsModal = shell.querySelector('.modal');
        var iconBox = shell.querySelector('#mgDialogIcon');
        var titleEl = shell.querySelector('#mgDialogTitle');
        var msgEl = shell.querySelector('#mgDialogMessage');
        var footerEl = shell.querySelector('#mgDialogFooter');

        // Reset
        iconBox.innerHTML = '';
        titleEl.textContent = '';
        msgEl.textContent = '';
        footerEl.innerHTML = '';

        // Icon
        var colorMap = {
            success: 'var(--mg-green, #19B85A)',
            error: 'var(--mg-danger, #D9534F)',
            warning: '#E89B1A',
            info: '#3B82F6'
        };
        var iconMap = {
            success: 'bi-check-circle-fill',
            error: 'bi-x-circle-fill',
            warning: 'bi-exclamation-triangle-fill',
            info: 'bi-info-circle-fill'
        };
        if (opts.type && iconMap[opts.type]) {
            iconBox.appendChild(iconEl(iconMap[opts.type], colorMap[opts.type] || colorMap.info));
        } else if (opts.iconHtml) {
            iconBox.innerHTML = opts.iconHtml;
        }

        // Title & message
        if (opts.title) titleEl.textContent = opts.title;
        if (opts.message) msgEl.textContent = opts.message;

        // Buttons
        if (opts._isForm) {
            footerEl.appendChild(buildFormUI(opts));
        } else {
            footerEl.appendChild(buildButtons(opts));
        }

        // Show
        var instance = new bootstrap.Modal(bsModal, { backdrop: 'static', keyboard: false });
        instance.show();

        // ESC handling (close non-required)
        function onEsc(e) {
            if (e.key === 'Escape') {
                if (opts.type !== 'error' && !opts.required) {
                    hideDialog(instance, onEsc);
                }
            }
        }
        document.addEventListener('keydown', onEsc);

        // Focus trap
        activeReleaseTrap = trapFocus(bsModal);

        // Focus first button
        setTimeout(function () {
            var firstBtn = footerEl.querySelector('button');
            if (firstBtn) firstBtn.focus();
        }, 100);

        return { instance: instance, onEsc: onEsc };
    }

    function hideDialog(instance, onEsc) {
        if (onEsc) document.removeEventListener('keydown', onEsc);
        if (activeReleaseTrap) { activeReleaseTrap(); activeReleaseTrap = null; }
        instance.hide();
    }

    function cleanupAfterHide(bsModal, onEsc) {
        bsModal.addEventListener('hidden.bs.modal', function handler() {
            if (onEsc) document.removeEventListener('keydown', onEsc);
            if (activeReleaseTrap) { activeReleaseTrap(); activeReleaseTrap = null; }
            bsModal.removeEventListener('hidden.bs.modal', handler);
        });
    }

    /* ===================================================================
       Button builder
       =================================================================== */
    function buildButtons(opts) {
        var wrap = document.createElement('div');
        wrap.className = 'd-grid gap-2';

        // Confirm button
        var confirmBtn = document.createElement('button');
        confirmBtn.type = 'button';
        confirmBtn.className = 'btn fw-bold';
        var confirmClass = opts.confirmClass || 'btn-gold';
        confirmBtn.classList.add(confirmClass);
        confirmBtn.style.cssText = 'border-radius:14px;min-height:48px;font-size:.95rem;';
        confirmBtn.textContent = opts.confirmText || 'OK';
        wrap.appendChild(confirmBtn);

        // Cancel button
        if (opts.showCancel || opts.type === 'confirm') {
            var cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.className = 'btn btn-outline-secondary fw-bold';
            cancelBtn.style.cssText = 'border-radius:14px;min-height:48px;font-size:.95rem;';
            cancelBtn.textContent = opts.cancelText || 'Batal';
            wrap.appendChild(cancelBtn);

            cancelBtn.addEventListener('click', function () {
                hideDialog(opts._ref.instance, opts._ref.onEsc);
                if (typeof opts.onCancel === 'function') opts.onCancel();
            });
        }

        confirmBtn.addEventListener('click', function () {
            // Loading state
            var origText = confirmBtn.textContent;
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '';
            confirmBtn.appendChild(spinner());
            confirmBtn.appendChild(document.createTextNode(' Memproses...'));

            function finish() {
                confirmBtn.disabled = false;
                confirmBtn.textContent = origText;
            }

            if (typeof opts.onConfirm === 'function') {
                var result = opts.onConfirm(finish);
                // If onConfirm returns a thenable, auto-finish
                if (result && typeof result.then === 'function') {
                    result.then(finish, finish);
                } else {
                    finish();
                }
            } else {
                finish();
                hideDialog(opts._ref.instance, opts._ref.onEsc);
            }
        });

        return wrap;
    }

    /* ===================================================================
       Form dialog builder
       =================================================================== */
    function buildFormUI(opts) {
        var wrap = document.createElement('div');

        var form = document.createElement('form');
        form.noValidate = true;
        form.className = 'mg-dialog-form';

        (opts.fields || []).forEach(function (f) {
            var group = document.createElement('div');
            group.className = 'mb-3';

            var label = document.createElement('label');
            label.className = 'form-label fw-bold';
            label.style.cssText = 'color:#1F160E;font-size:.88rem;';
            label.textContent = f.label || f.name;
            if (f.required) {
                var star = document.createElement('span');
                star.style.color = 'var(--mg-danger, #D9534F)';
                star.textContent = ' *';
                label.appendChild(star);
            }
            group.appendChild(label);

            var input;
            if (f.type === 'textarea') {
                input = document.createElement('textarea');
                input.rows = f.rows || 3;
            } else if (f.type === 'select') {
                input = document.createElement('select');
                (f.options || []).forEach(function (opt) {
                    var o = document.createElement('option');
                    if (typeof opt === 'object') {
                        o.value = opt.value;
                        o.textContent = opt.label;
                    } else {
                        o.value = opt;
                        o.textContent = opt;
                    }
                    input.appendChild(o);
                });
            } else {
                input = document.createElement('input');
                input.type = f.type || 'text';
            }

            input.className = 'form-control';
            input.name = f.name;
            if (f.placeholder) input.placeholder = f.placeholder;
            if (f.required) input.required = true;
            input.style.cssText = 'border-radius:12px;min-height:44px;font-size:.92rem;';

            group.appendChild(input);
            form.appendChild(group);
        });

        wrap.appendChild(form);

        // Buttons
        var btnRow = document.createElement('div');
        btnRow.className = 'd-grid gap-2 mt-3';

        var submitBtn = document.createElement('button');
        submitBtn.type = 'submit';
        submitBtn.className = 'btn fw-bold btn-gold';
        submitBtn.style.cssText = 'border-radius:14px;min-height:48px;font-size:.95rem;';
        submitBtn.textContent = opts.submitText || 'Simpan';
        btnRow.appendChild(submitBtn);

        var cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'btn btn-outline-secondary fw-bold';
        cancelBtn.style.cssText = 'border-radius:14px;min-height:48px;font-size:.95rem;';
        cancelBtn.textContent = opts.cancelText || 'Batal';
        btnRow.appendChild(cancelBtn);

        wrap.appendChild(btnRow);

        // Prevent double submit & call onsubmit
        var submitting = false;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (submitting) return;

            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            submitting = true;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '';
            submitBtn.appendChild(spinner());
            submitBtn.appendChild(document.createTextNode(' Memproses...'));

            // Gather values
            var data = {};
            var inputs = form.querySelectorAll('input, textarea, select');
            for (var i = 0; i < inputs.length; i++) {
                data[inputs[i].name] = inputs[i].value;
            }

            if (typeof opts.onsubmit === 'function') {
                var result = opts.onsubmit(data, function finish() {
                    submitting = false;
                    submitBtn.disabled = false;
                    submitBtn.textContent = opts.submitText || 'Simpan';
                });
                if (result && typeof result.then === 'function') {
                    result.then(function () {
                        submitting = false;
                    }, function () {
                        submitting = false;
                        submitBtn.disabled = false;
                        submitBtn.textContent = opts.submitText || 'Simpan';
                    });
                }
            } else {
                submitting = false;
                submitBtn.disabled = false;
                submitBtn.textContent = opts.submitText || 'Simpan';
            }
        });

        cancelBtn.addEventListener('click', function () {
            if (opts._ref) hideDialog(opts._ref.instance, opts._ref.onEsc);
        });

        return wrap;
    }

    /* ===================================================================
       Public API — MahenDialog
       =================================================================== */
    var MahenDialog = {

        success: function (opts) {
            opts = Object.assign({ type: 'success', confirmText: 'OK' }, opts);
            var ref = showDialog(opts);
            opts._ref = ref;
            cleanupAfterHide(ref.instance, ref.onEsc);
        },

        error: function (opts) {
            opts = Object.assign({ type: 'error', confirmText: 'OK' }, opts);
            var ref = showDialog(opts);
            opts._ref = ref;
            cleanupAfterHide(ref.instance, ref.onEsc);
        },

        warning: function (opts) {
            opts = Object.assign({ type: 'warning', confirmText: 'OK' }, opts);
            var ref = showDialog(opts);
            opts._ref = ref;
            cleanupAfterHide(ref.instance, ref.onEsc);
        },

        info: function (opts) {
            opts = Object.assign({ type: 'info', confirmText: 'OK' }, opts);
            var ref = showDialog(opts);
            opts._ref = ref;
            cleanupAfterHide(ref.instance, ref.onEsc);
        },

        confirm: function (opts) {
            opts = Object.assign({ type: 'confirm', confirmText: 'Ya, Lanjutkan', cancelText: 'Batal', showCancel: true }, opts);
            // Confirm dialogs use the warning icon as default
            if (!opts.iconHtml && !opts.type) opts.type = 'warning';
            if (!opts.confirmClass) opts.confirmClass = 'btn-gold';
            var ref = showDialog(opts);
            opts._ref = ref;
            cleanupAfterHide(ref.instance, ref.onEsc);
        },

        form: function (opts) {
            opts = Object.assign({ _isForm: true, submitText: 'Simpan', cancelText: 'Batal' }, opts);
            var ref = showDialog(opts);
            opts._ref = ref;
            cleanupAfterHide(ref.instance, ref.onEsc);
        }
    };

    /* ===================================================================
       MahenToast
       =================================================================== */
    var MahenToast = {

        show: function (opts) {
            opts = opts || {};
            var message = opts.message || '';
            var type = opts.type || 'info';
            var duration = opts.duration || 4000;

            var container = getToastContainer();

            var colorMap = {
                success: 'var(--mg-green, #19B85A)',
                error: 'var(--mg-danger, #D9534F)',
                warning: '#E89B1A',
                info: '#3B82F6'
            };
            var bgMap = {
                success: 'rgba(25,184,90,.12)',
                error: 'rgba(217,83,79,.12)',
                warning: 'rgba(232,155,26,.12)',
                info: 'rgba(59,130,246,.12)'
            };
            var iconMap = {
                success: 'bi-check-circle-fill',
                error: 'bi-x-circle-fill',
                warning: 'bi-exclamation-triangle-fill',
                info: 'bi-info-circle-fill'
            };

            var toast = document.createElement('div');
            toast.style.cssText =
                'pointer-events:auto;display:flex;align-items:flex-start;gap:12px;' +
                'padding:14px 18px;border-radius:18px;min-width:280px;max-width:100%;' +
                'background:var(--mg-white, #fff);border:1px solid rgba(231,216,189,.72);' +
                'box-shadow:0 14px 36px rgba(20,10,5,.14);transform:translateX(120%);' +
                'transition:transform .3s cubic-bezier(.22,1,.36,1),opacity .3s ease;opacity:0;' +
                'font-family:inherit;';

            // Icon
            var icon = document.createElement('i');
            icon.className = 'bi ' + (iconMap[type] || iconMap.info);
            icon.style.cssText = 'font-size:1.3rem;line-height:1.3;flex:0 0 auto;color:' + (colorMap[type] || colorMap.info);
            toast.appendChild(icon);

            // Message
            var msgEl = document.createElement('div');
            msgEl.style.cssText = 'flex:1;font-size:.9rem;font-weight:600;color:#1F160E;line-height:1.45;';
            msgEl.textContent = message;
            toast.appendChild(msgEl);

            // Close button
            var closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.setAttribute('aria-label', 'Tutup');
            closeBtn.style.cssText =
                'flex:0 0 auto;border:0;background:none;color:#756858;font-size:1.2rem;line-height:1;padding:0;cursor:pointer;';
            closeBtn.innerHTML = '<i class="bi bi-x"></i>';
            closeBtn.addEventListener('click', function () { dismissToast(); });
            toast.appendChild(closeBtn);

            container.appendChild(toast);

            // Animate in
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    toast.style.transform = 'translateX(0)';
                    toast.style.opacity = '1';
                });
            });

            // Auto-dismiss
            var timer = setTimeout(dismissToast, duration);

            function dismissToast() {
                clearTimeout(timer);
                toast.style.transform = 'translateX(120%)';
                toast.style.opacity = '0';
                setTimeout(function () {
                    if (toast.parentNode) toast.parentNode.removeChild(toast);
                }, 350);
            }
        }
    };

    /* ===================================================================
       Expose globally
       =================================================================== */
    window.MahenDialog = MahenDialog;
    window.MahenToast = MahenToast;

})();
