<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? 'Nota Pembayaran - MahenGold'); ?></title>
    <!-- Use Outfit & Playfair Display for premium look -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&family=Playfair+Display:ital,wght@0,600;0,800;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --mg-gold: #C9A24B;
            --mg-gold-dark: #A67D2D;
            --mg-cream: #F4EEE1;
            --mg-dark: #1C1A17;
            --mg-gray: #7A7263;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--mg-cream);
            color: var(--mg-dark);
            line-height: 1.5;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .no-print {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 30px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-gold {
            background-color: var(--mg-gold);
            color: var(--mg-dark);
        }

        .btn-gold:hover {
            background-color: var(--mg-gold-dark);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--mg-dark);
            border: 2px solid var(--mg-gold);
        }

        .btn-outline:hover {
            background-color: var(--mg-gold);
        }

        .receipt-card {
            background: #ffffff;
            border: 1px solid #ECE3D2;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 580px;
            box-shadow: 0 8px 30px rgba(28, 26, 23, 0.05);
            position: relative;
            overflow: hidden;
        }

        /* Decorative top bar */
        .receipt-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--mg-gold), var(--mg-gold-dark));
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px dashed #ECE3D2;
        }

        .brand-logo {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 1px;
            color: var(--mg-dark);
        }

        .brand-sub {
            color: var(--mg-gray);
            font-size: 11px;
            letter-spacing: 2px;
            margin-top: 2px;
            text-transform: uppercase;
        }

        .receipt-title {
            margin-top: 20px;
            font-size: 16px;
            font-weight: 800;
            color: var(--mg-gold-dark);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .info-table td {
            padding: 8px 0;
            font-size: 14px;
            vertical-align: top;
        }

        .info-table .label {
            color: var(--mg-gray);
            width: 45%;
        }

        .info-table .value {
            font-weight: 600;
            text-align: right;
            color: var(--mg-dark);
        }

        .amount-highlight {
            background-color: var(--mg-cream);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            margin-bottom: 30px;
        }

        .amount-label {
            font-size: 12px;
            color: var(--mg-gray);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .amount-val {
            font-size: 26px;
            font-weight: 800;
            color: var(--mg-gold-dark);
        }

        .stamp-lunas {
            position: absolute;
            bottom: 40px;
            right: 40px;
            border: 4px double #1F8A4C;
            color: #1F8A4C;
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 800;
            text-transform: uppercase;
            padding: 6px 16px;
            transform: rotate(-12deg);
            border-radius: 4px;
            opacity: 0.85;
            letter-spacing: 1px;
            background-color: rgba(255, 255, 255, 0.9);
            box-shadow: 0 0 10px rgba(0,0,0,0.02);
            pointer-events: none;
        }

        .stamp-lunas::after {
            content: '✓';
            margin-left: 4px;
            font-size: 18px;
        }

        .footer-note {
            text-align: center;
            font-size: 12px;
            color: var(--mg-gray);
            margin-top: 20px;
            line-height: 1.6;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .receipt-card {
                border: none !important;
                box-shadow: none !important;
                padding: 20px !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <?php if (!empty($backUrl)): ?>
            <a href="<?= esc($backUrl); ?>" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Kembali</a>
        <?php endif; ?>
        <button onclick="window.print();" class="btn btn-gold"><i class="bi bi-printer"></i> Cetak / Print</button>
    </div>

    <div class="receipt-card">
        <div class="receipt-header">
            <div class="brand-logo"><?= esc($pengaturan['nama_toko'] ?? 'MahenGold'); ?></div>
            <div class="brand-sub">Penjualan &amp; Kredit Emas</div>
            <div class="receipt-title">
                <?php if ($tipe === 'dp'): ?>
                    Nota Pembayaran Uang Muka (DP)
                <?php else: ?>
                    Nota Pembayaran Angsuran
                <?php endif; ?>
            </div>
        </div>

        <?php if ($tipe === 'dp'): ?>
            <div class="amount-highlight">
                <div class="amount-label">Nominal Uang Muka (DP)</div>
                <div class="amount-val"><?= esc(format_rupiah($kredit['uang_muka'] ?? 0)); ?></div>
            </div>

            <table class="info-table">
                <tr>
                    <td class="label">Kode Kredit</td>
                    <td class="value"><?= esc($kredit['kode_kredit'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td class="label">Nomor Pesanan</td>
                    <td class="value"><?= esc($pengajuan['kode_pesanan'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td class="label">Nama Pelanggan</td>
                    <td class="value"><?= esc($kredit['nama_nasabah'] ?? $pengajuan['nama'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td class="label">Produk Emas</td>
                    <td class="value"><?= esc($kredit['nama_produk'] ?? $pengajuan['nama_produk'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td class="label">Tanggal Bayar DP</td>
                    <td class="value"><?= esc($tanggal_bayar); ?></td>
                </tr>
                <tr>
                    <td class="label">Bulan Pembayaran DP</td>
                    <td class="value"><?= esc($bulan_bayar); ?></td>
                </tr>
                <tr>
                    <td class="label">Total Harga Kredit</td>
                    <td class="value"><?= esc(format_rupiah($kredit['total_harga_kredit'] ?? 0)); ?></td>
                </tr>
                <tr>
                    <td class="label">Sisa Pokok (dicicil)</td>
                    <td class="value"><?= esc(format_rupiah($kredit['sisa_pokok_kredit'] ?? 0)); ?></td>
                </tr>
                <tr>
                    <td class="label">Sisa Piutang</td>
                    <td class="value"><?= esc(format_rupiah($kredit['sisa_piutang'] ?? 0)); ?></td>
                </tr>
                <tr>
                    <td class="label">Status Verifikasi DP</td>
                    <td class="value" style="color: #1F8A4C;">Terverifikasi</td>
                </tr>
            </table>
        <?php else: ?>
            <div class="amount-highlight">
                <div class="amount-label">Nominal Dibayar</div>
                <div class="amount-val"><?= esc(format_rupiah($nominal_bayar)); ?></div>
            </div>

            <table class="info-table">
                <tr>
                    <td class="label">Kode Pembayaran</td>
                    <td class="value"><?= esc($kode_pembayaran); ?></td>
                </tr>
                <tr>
                    <td class="label">Kode Kredit</td>
                    <td class="value"><?= esc($kredit['kode_kredit'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td class="label">Nama Pelanggan</td>
                    <td class="value"><?= esc($kredit['nama_nasabah'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td class="label">Produk Emas</td>
                    <td class="value"><?= esc($kredit['nama_produk'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td class="label">Angsuran Ke</td>
                    <td class="value">Ke-<?= esc($angsuran_ke); ?></td>
                </tr>
                <tr>
                    <td class="label">Nominal Tagihan</td>
                    <td class="value"><?= esc(format_rupiah($nominal_tagihan)); ?></td>
                </tr>
                <tr>
                    <td class="label">Tanggal Jatuh Tempo</td>
                    <td class="value"><?= esc($tanggal_jatuh_tempo); ?></td>
                </tr>
                <tr>
                    <td class="label">Tanggal Pembayaran</td>
                    <td class="value"><?= esc($tanggal_bayar); ?></td>
                </tr>
                <tr>
                    <td class="label">Metode Pembayaran</td>
                    <td class="value" style="text-transform: capitalize;"><?= esc($metode_pembayaran); ?></td>
                </tr>
                <tr>
                    <td class="label">Sisa Piutang</td>
                    <td class="value"><?= esc(format_rupiah($kredit['sisa_piutang'] ?? 0)); ?></td>
                </tr>
                <tr>
                    <td class="label">Status Kredit</td>
                    <td class="value"><?= ($kredit['status'] ?? 'aktif') === 'lunas' ? '<span style="color: #1F8A4C; font-weight: bold;">Lunas</span>' : 'Aktif'; ?></td>
                </tr>
            </table>

            <?php if (($kredit['status'] ?? 'aktif') === 'lunas' || ($kredit['sisa_piutang'] ?? 0) <= 0): ?>
                <div class="stamp-lunas">Lunas</div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="footer-note">
            <p>Terima kasih atas pembayaran Anda.</p>
            <p>Simpan nota ini sebagai bukti pembayaran resmi <?= esc($pengaturan['nama_toko'] ?? 'MahenGold'); ?>.</p>
        </div>
    </div>

    <?php if ($print): ?>
        <script>
            window.onload = function() {
                window.print();
            }
        </script>
    <?php endif; ?>
</body>
</html>
