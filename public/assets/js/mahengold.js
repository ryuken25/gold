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
        const totalEl = document.getElementById('wa_total_kredit');
        const periodeEl = document.getElementById('wa_jumlah_periode');
        const angsuranEl = document.getElementById('wa_nominal_angsuran');
        const previewEl = document.getElementById('wa_preview');
        const hargaPokokCashEl = document.getElementById('wa_harga_pokok_cash');
        const kreditFields = waModal.querySelectorAll('.wa-kredit-field');
        const cashFields = waModal.querySelectorAll('.wa-cash-field');
        const ktpInput = document.getElementById('wa_foto_ktp');
        let currentHargaPokok = 0;

        const getMetode = () => (waModal.querySelector('input[name="metode_pembayaran"]:checked')?.value || 'kredit');

        const syncMetodeCards = () => {
            waModal.querySelectorAll('.metode-option').forEach((card) => {
                const input = card.querySelector('input[name="metode_pembayaran"]');
                card.classList.toggle('is-active', !!input?.checked);
            });
        };

        const applyMetodePembayaran = () => {
            const isKredit = getMetode() === 'kredit';
            kreditFields.forEach((el) => { el.style.display = isKredit ? '' : 'none'; });
            cashFields.forEach((el) => { el.style.display = isKredit ? 'none' : ''; });
            if (ktpInput) ktpInput.required = isKredit;
            if (!isKredit && currentHargaPokok && hargaPokokCashEl) {
                hargaPokokCashEl.textContent = currency(currentHargaPokok);
            }
            syncMetodeCards();
            updatePreview();
        };

        waModal.querySelectorAll('input[name="metode_pembayaran"]').forEach((radio) => {
            radio.addEventListener('change', applyMetodePembayaran);
        });

        const updatePreview = async () => {
            if (!produkIdInput.value) return;
            const params = new URLSearchParams({
                produk_id: produkIdInput.value,
                tenor_bulan: tenorInput.value,
                periode_angsuran: periodeInput.value,
                nama: namaInput.value,
                alamat: alamatInput.value,
                metode_pembayaran: getMetode(),
            });
            const response = await fetch(`${window.location.origin}/simulasi?${params.toString()}`);
            const data = await response.json();
            if (!data.kalkulasi) return;
            totalEl.textContent = currency(data.kalkulasi.total_harga_kredit);
            periodeEl.textContent = `${data.kalkulasi.jumlah_periode} ${data.kalkulasi.periode_label}`;
            angsuranEl.textContent = `${currency(data.kalkulasi.nominal_angsuran)} / ${data.kalkulasi.periode_label}`;
            previewEl.value = data.preview_message || 'Isi nama dan alamat untuk melihat preview pesan WhatsApp.';
        };

        document.querySelectorAll('.js-open-wa-modal').forEach((button) => {
            button.addEventListener('click', () => {
                produkIdInput.value = button.dataset.produkId || '';
                produkLabel.value = `${button.dataset.kode || ''} - ${button.dataset.nama || ''}`.trim();
                currentHargaPokok = parseFloat(button.dataset.hargaPokok || '0');
                namaInput.value = namaInput.value || '';
                alamatInput.value = alamatInput.value || '';
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

        [tenorInput, periodeInput, namaInput, alamatInput].forEach((el) => el?.addEventListener('input', updatePreview));
        form?.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (getMetode() === 'kredit' && ktpInput && !ktpInput.files.length) {
                alert('Foto KTP wajib diunggah untuk pengajuan kredit.');
                ktpInput.focus();
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
                window.open(data.wa_url, '_blank', 'noopener');
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
