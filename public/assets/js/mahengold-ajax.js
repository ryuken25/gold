/**
 * MahenAjax — Centralized AJAX helper for MahenGold.
 * Handles CSRF, error parsing, and consistent responses.
 */
(function () {
    'use strict';

    function getCsrf() {
        var nameEl = document.querySelector('meta[name="csrf-token-name"]');
        var valEl = document.querySelector('meta[name="csrf-token-value"]');
        return {
            name: nameEl ? nameEl.content : 'csrf_token',
            hash: valEl ? valEl.content : ''
        };
    }

    function updateCsrf(csrf) {
        if (!csrf) return;
        if (csrf.name) {
            var nameEl = document.querySelector('meta[name="csrf-token-name"]');
            if (nameEl) nameEl.content = csrf.name;
        }
        if (csrf.hash) {
            var valEl = document.querySelector('meta[name="csrf-token-value"]');
            if (valEl) valEl.content = csrf.hash;
        }
    }

    function parseError(res, body) {
        // Network or non-JSON
        if (!body || typeof body !== 'object') {
            return 'Server mengembalikan response tidak valid. Cek writable/logs.';
        }

        // CSRF / 403 / 419
        if (res.status === 403 || res.status === 419) {
            return 'Sesi keamanan sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.';
        }

        // 422 Validation
        if (res.status === 422 && body.errors) {
            var msgs = [];
            for (var key in body.errors) {
                if (Array.isArray(body.errors[key])) {
                    msgs.push(body.errors[key].join(' '));
                } else {
                    msgs.push(body.errors[key]);
                }
            }
            return msgs.join('\n') || body.message || 'Validasi gagal.';
        }

        // 500 JSON
        if (res.status >= 500) {
            return body.message || 'Terjadi kesalahan server. Cek writable/logs.';
        }

        return body.message || 'Terjadi kesalahan.';
    }

    window.MahenAjax = {
        post: function (url, data, options) {
            options = options || {};
            var csrf = getCsrf();
            var fd;

            if (data instanceof FormData) {
                fd = data;
                if (!fd.has(csrf.name)) fd.append(csrf.name, csrf.hash);
            } else {
                fd = new FormData();
                fd.append(csrf.name, csrf.hash);
                if (data && typeof data === 'object') {
                    for (var k in data) {
                        if (data.hasOwnProperty(k)) fd.append(k, String(data[k] == null ? '' : data[k]));
                    }
                }
            }

            return fetch(url, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function (res) {
                var contentType = res.headers.get('Content-Type') || '';
                if (contentType.indexOf('application/json') === -1) {
                    // Got HTML instead of JSON — server error page
                    throw new Error('Server mengembalikan halaman error. Buka writable/logs untuk detail.');
                }
                return res.json().then(function (body) {
                    // Update CSRF if server provides new hash
                    if (body.csrf) updateCsrf(body.csrf);

                    if (!res.ok) {
                        var err = new Error(parseError(res, body));
                        err.status = res.status;
                        err.data = body;
                        throw err;
                    }
                    return body;
                });
            })
            .catch(function (err) {
                if (err.message && err.message.indexOf('Server mengembalikan') !== -1) throw err;
                if (err.status) throw err;
                // Network error
                throw new Error('Tidak dapat menghubungi server. Pastikan server lokal masih berjalan.');
            });
        }
    };
})();
