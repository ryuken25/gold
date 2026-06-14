document.addEventListener('DOMContentLoaded', () => {
    const currency = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value || 0));

    const adminSidebar = document.getElementById('adminSidebar');
    const adminBackdrop = document.getElementById('adminBackdrop');
    const adminToggle = document.querySelectorAll('[data-admin-sidebar-toggle]');
    const setSidebar = (isOpen) => {
        adminSidebar?.classList.toggle('show', isOpen);
        adminBackdrop?.classList.toggle('show', isOpen);
        document.body.classList.toggle('overflow-hidden', isOpen);
    };
    adminToggle.forEach((button) => button.addEventListener('click', () => setSidebar(!adminSidebar?.classList.contains('show'))));
    adminBackdrop?.addEventListener('click', () => setSidebar(false));
    adminSidebar?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setSidebar(false)));

    const publicDrawer = document.getElementById('publicDrawer');
    const publicBackdrop = document.querySelector('.public-drawer-backdrop');
    const publicToggle = document.querySelectorAll('[data-public-drawer-toggle]');
    const publicClose = document.querySelectorAll('[data-public-drawer-close]');
    const setPublicDrawer = (isOpen) => {
        publicDrawer?.classList.toggle('show', isOpen);
        publicBackdrop?.classList.toggle('show', isOpen);
        publicDrawer?.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        document.body.classList.toggle('overflow-hidden', isOpen);
    };
    publicToggle.forEach((button) => button.addEventListener('click', () => setPublicDrawer(true)));
    publicClose.forEach((button) => button.addEventListener('click', () => setPublicDrawer(false)));
    publicDrawer?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setPublicDrawer(false)));

    const waModal = document.getElementById('waPengajuanModal');
    if (waModal) {
        const form = document.getElementById('waPengajuanForm');
        const produkIdInput = document.getElementById('wa_produk_id');
        const produkLabel = document.getElementById('wa_produk_label');
        const tenorInput = document.getElementById('wa_tenor');
        const periodeInput = document.getElementById('wa_periode');
        const namaInput = document.getElementById('wa_nama');
        const alamatInput = document.getElementById('wa_alamat');
        const noTeleponInput = document.getElementById('wa_no_telepon');
        const previewEl = document.getElementById('wa_preview');
        const uangMukaInput = document.getElementById('wa_uang_muka');
        const estTotal = document.getElementById('wa_est_total');
        const estDp = document.getElementById('wa_est_dp');
        const estSisa = document.getElementById('wa_est_sisa');
        const estAngsuran = document.getElementById('wa_est_angsuran');
        const kreditFields = waModal.querySelectorAll('.wa-kredit-field');
        const ktpInput = document.getElementById('wa_foto_ktp');
        const buktiDpInput = document.getElementById('wa_bukti_dp');
        let currentHargaPokok = 0;
        let lastKalkulasi = null;

        const getMetode = () => (waModal.querySelector('input[name="metode_pembayaran"]:checked')?.value || 'kredit');

        const syncMetodeCards = () => {
            waModal.querySelectorAll('.metode-option').forEach((card) => {
                const input = card.querySelector('input[name="metode_pembayaran"]');
                card.classList.toggle('is-active', !!input?.checked);
            });
        };

        // Rakit ringkasan pesanan + update blok estimasi live.
        const buildSummary = (kalkulasi) => {
            const metode = getMetode();
            lastKalkulasi = metode === 'kredit' ? kalkulasi : null;
            const lines = [];
            lines.push(`Produk : ${produkLabel.value || '-'}`);
            lines.push(`Metode : ${metode === 'kredit' ? 'Kredit' : 'Cash'}`);
            lines.push('');
            if (metode === 'kredit') {
                lines.push(`Tenor  : ${tenorInput.value} bulan (${periodeInput.value})`);
                if (kalkulasi) {
                    lines.push(`Total Harga Kredit : ${currency(kalkulasi.total_harga_kredit)}`);
                    lines.push(`Uang Muka (DP)     : ${currency(kalkulasi.uang_muka)}`);
                    lines.push(`Sisa Diangsur      : ${currency(kalkulasi.sisa_pokok)}`);
                    lines.push(`Jumlah Periode     : ${kalkulasi.jumlah_periode} ${kalkulasi.periode_label}`);
                    lines.push(`Estimasi Angsuran  : ${currency(kalkulasi.nominal_angsuran)} / ${kalkulasi.periode_label}`);
                }
                if (estTotal) estTotal.textContent = kalkulasi ? currency(kalkulasi.total_harga_kredit) : '-';
                if (estDp) estDp.textContent = kalkulasi ? currency(kalkulasi.uang_muka) : '-';
                if (estSisa) estSisa.textContent = kalkulasi ? currency(kalkulasi.sisa_pokok) : '-';
                if (estAngsuran) estAngsuran.textContent = kalkulasi ? `${currency(kalkulasi.nominal_angsuran)} / ${kalkulasi.periode_label}` : '-';
            } else {
                lines.push(`Harga Pokok : ${currency(currentHargaPokok)}`);
                lines.push('Pembayaran dilakukan sekaligus saat transaksi.');
            }
            previewEl.value = lines.join('\n');
        };

        const updatePreview = async () => {
            if (!produkIdInput.value) return;
            if (getMetode() !== 'kredit') { buildSummary(null); return; }
            try {
                const params = new URLSearchParams({
                    produk_id: produkIdInput.value,
                    tenor_bulan: tenorInput.value,
                    periode_angsuran: periodeInput.value,
                    uang_muka: (uangMukaInput && uangMukaInput.value) || 0,
                });
                const response = await fetch(`${window.location.origin}/simulasi?${params.toString()}`);
                const data = await response.json();
                buildSummary(data.kalkulasi || null);
            } catch (e) {
                buildSummary(null);
            }
        };

        const applyMetodePembayaran = () => {
            const isKredit = getMetode() === 'kredit';
            kreditFields.forEach((el) => { el.style.display = isKredit ? '' : 'none'; });
            if (ktpInput) ktpInput.required = isKredit;
            if (buktiDpInput) buktiDpInput.required = isKredit;
            syncMetodeCards();
            updatePreview();
        };

        waModal.querySelectorAll('input[name="metode_pembayaran"]').forEach((radio) => {
            radio.addEventListener('change', applyMetodePembayaran);
        });

        document.querySelectorAll('.js-open-wa-modal').forEach((button) => {
            button.addEventListener('click', () => {
                produkIdInput.value = button.dataset.produkId || '';
                produkLabel.value = `${button.dataset.kode || ''} - ${button.dataset.nama || ''}`.trim();
                currentHargaPokok = parseFloat(button.dataset.hargaPokok || '0');
                // Reset ke kredit saat buka modal
                const kreditRadio = waModal.querySelector('#metode_kredit');
                if (kreditRadio) kreditRadio.checked = true;
                applyMetodePembayaran();
            });
        });

        // Perbarui token CSRF tersembunyi dari respons server agar submit
        // berikutnya tidak ditolak (Config\Security::$regenerate = true).
        const refreshCsrf = (token) => {
            if (!token) return;
            const name = form.dataset.csrfName;
            if (!name) return;
            const input = form.querySelector(`input[name="${name}"]`);
            if (input) input.value = token;
        };

        // Chip cepat DP: isi field lalu refresh estimasi.
        waModal.querySelectorAll('.wa-dp-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                if (uangMukaInput) uangMukaInput.value = chip.dataset.dp || '0';
                updatePreview();
            });
        });

        [tenorInput, periodeInput, uangMukaInput].forEach((el) => el?.addEventListener('input', updatePreview));
        form?.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (noTeleponInput && noTeleponInput.value.trim().length < 8) {
                alert('Nomor telepon wajib diisi (minimal 8 digit).');
                noTeleponInput.focus();
                return;
            }
            if (getMetode() === 'kredit' && uangMukaInput) {
                const dpMin = parseFloat(uangMukaInput.min || '0') || 0;
                const dp = parseFloat(uangMukaInput.value || '0') || 0;
                const total = lastKalkulasi ? Number(lastKalkulasi.total_harga_kredit) : null;
                if (dp < dpMin) { alert('Uang muka minimal ' + currency(dpMin) + '.'); uangMukaInput.focus(); return; }
                if (total !== null && dp >= total) {
                    alert('Uang muka harus lebih kecil dari total harga kredit (' + currency(total) + ').');
                    uangMukaInput.focus();
                    return;
                }
            }
            if (getMetode() === 'kredit' && ktpInput && !ktpInput.files.length) {
                alert('Foto KTP wajib diunggah untuk pengajuan kredit.');
                ktpInput.focus();
                return;
            }
            if (getMetode() === 'kredit' && buktiDpInput && !buktiDpInput.files.length) {
                alert('Bukti pembayaran Uang Muka (DP) wajib diunggah untuk pengajuan kredit.');
                buktiDpInput.focus();
                return;
            }
            const submitBtn = document.querySelector('[form="waPengajuanForm"][type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
            try {
                const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                let data = {};
                try { data = await response.json(); } catch (e) { data = {}; }
                refreshCsrf(data.csrf);

                if (response.status === 401 && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                if (!response.ok) {
                    const messages = data.errors ? Object.values(data.errors) : [data.message || 'Terjadi kesalahan. Coba muat ulang halaman.'];
                    alert(messages.join('\n'));
                    return;
                }
                // Sukses: tutup modal & arahkan ke halaman pesanan (tanpa WhatsApp).
                if (window.bootstrap && bootstrap.Modal) {
                    (bootstrap.Modal.getInstance(waModal) || bootstrap.Modal.getOrCreateInstance(waModal)).hide();
                }
                window.location.href = data.redirect || '/akun/pesanan';
            } catch (e) {
                alert('Gagal menghubungi server. Periksa koneksi lalu coba lagi.');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });

        // Terapkan state awal
        applyMetodePembayaran();
    }

    const config = window.MahenGoldAdminPreview;
    if (config) {
        const form = document.getElementById(config.formId);
        const preview = document.getElementById('creditPreview');
        const run = async () => {
            const formData = new FormData(form);
            if (!formData.get('produk_emas_id')) {
                preview.innerHTML = 'Pilih produk untuk melihat estimasi kredit realtime.';
                return;
            }
            const response = await fetch(config.endpoint, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await response.json();
            if (!data.success) {
                preview.innerHTML = `<span class="text-danger">${data.message}</span>`;
                return;
            }
            const item = data.data;
            preview.innerHTML = `
                <div class="mini-stats">
                    <div><span>Margin</span><strong>${item.margin_persen}%</strong></div>
                    <div><span>Total Harga Kredit</span><strong>${currency(item.total_harga_kredit)}</strong></div>
                    <div><span>Jumlah Periode</span><strong>${item.jumlah_periode} ${item.periode_label}</strong></div>
                    <div><span>Nominal Angsuran</span><strong>${currency(item.nominal_angsuran)} / ${item.periode_label}</strong></div>
                </div>`;
        };
        form?.addEventListener('change', run);
        run();
    }

    const creditSelect = document.getElementById('payment_kredit_id');
    const jadwalSelect = document.getElementById('jadwal_angsuran_id');
    if (creditSelect && jadwalSelect) {
        const filterSchedules = () => {
            Array.from(jadwalSelect.options).forEach((option, index) => {
                if (index === 0) return;
                option.hidden = creditSelect.value !== '' && option.dataset.kreditId !== creditSelect.value;
            });
        };
        creditSelect.addEventListener('change', filterSchedules);
        filterSchedules();
    }
});
