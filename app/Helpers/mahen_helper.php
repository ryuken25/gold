<?php

if (!function_exists('format_rupiah')) {
    function format_rupiah($nominal): string
    {
        return 'Rp ' . number_format((float) $nominal, 0, ',', '.');
    }
}

if (!function_exists('format_angka')) {
    function format_angka($angka, int $desimal = 0): string
    {
        return number_format((float) $angka, $desimal, ',', '.');
    }
}

if (!function_exists('format_tanggal')) {
    function format_tanggal(?string $tanggal, string $format = 'd M Y'): string
    {
        if (empty($tanggal)) {
            return '-';
        }

        return date($format, strtotime($tanggal));
    }
}

if (!function_exists('periode_label')) {
    function periode_label(string $periode): string
    {
        return $periode === 'mingguan' ? 'minggu' : 'bulan';
    }
}

if (!function_exists('status_badge_class')) {
    function status_badge_class(?string $status): string
    {
        return match ($status) {
            'aktif', 'dibayar', 'dikirim_manual', 'disetujui', 'selesai' => 'success',
            'lunas' => 'primary',
            'terlambat', 'gagal', 'dibatalkan', 'ditolak' => 'danger',
            'sebagian' => 'warning',
            'dibuka', 'diproses' => 'info',
            'baru' => 'secondary',
            default => 'secondary',
        };
    }
}

if (!function_exists('flash_class')) {
    function flash_class(string $key): string
    {
        return match ($key) {
            'success' => 'success',
            'error' => 'danger',
            'warning' => 'warning',
            default => 'info',
        };
    }
}

if (!function_exists('current_admin')) {
    function current_admin(): ?array
    {
        return session()->get('admin_user');
    }
}

if (!function_exists('is_admin_logged_in')) {
    function is_admin_logged_in(): bool
    {
        return current_admin() !== null;
    }
}

if (!function_exists('is_active_menu')) {
    function is_active_menu(string $segment): bool
    {
        $currentPath = trim(service('request')->getUri()->getPath(), '/');

        return $segment === '' ? $currentPath === '' : str_starts_with($currentPath, trim($segment, '/'));
    }
}

if (!function_exists('wa_number_normalize')) {
    function wa_number_normalize(?string $nomor): string
    {
        $nomor = preg_replace('/[^0-9]/', '', (string) $nomor);

        if ($nomor === null || $nomor === '') {
            return '';
        }

        if (str_starts_with($nomor, '0')) {
            return '62' . substr($nomor, 1);
        }

        return $nomor;
    }
}

if (!function_exists('generate_kode')) {
    function generate_kode(string $prefix, int $id, int $padding = 4): string
    {
        return $prefix . '-' . str_pad((string) $id, $padding, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('current_pelanggan')) {
    function current_pelanggan(): ?array
    {
        return session()->get('pelanggan_user');
    }
}

if (!function_exists('is_pelanggan_logged_in')) {
    function is_pelanggan_logged_in(): bool
    {
        return current_pelanggan() !== null;
    }
}

// ============================================================
// UPDATED: pesanan_status_label — label tampilan dari status DB
// ============================================================
if (!function_exists('pesanan_status_label')) {
    function pesanan_status_label(?string $status, ?string $metode = null, int $uangMuka = 0, ?string $payStatus = null): string
    {
        if ($status === 'disetujui' && $metode === 'kredit' && $uangMuka > 0 && $payStatus !== 'terverifikasi') {
            return 'Menunggu Verifikasi DP';
        }
        return match ($status) {
            'baru'       => 'Menunggu Verifikasi',
            'diproses'   => 'Menunggu Verifikasi',
            'disetujui'  => 'Menunggu Dikirim',
            'dikirim'    => 'Dikirim',
            'selesai'    => 'Selesai',
            'ditolak'    => 'Ditolak',
            'dibatalkan' => 'Dibatalkan',
            default      => 'Menunggu Verifikasi',
        };
    }
}

// ============================================================
// UPDATED: pesanan_badge_class — badge color untuk status pesanan
// ============================================================
if (!function_exists('pesanan_badge_class')) {
    function pesanan_badge_class(?string $status, ?string $metode = null, int $uangMuka = 0, ?string $payStatus = null): string
    {
        if ($status === 'disetujui' && $metode === 'kredit' && $uangMuka > 0 && $payStatus !== 'terverifikasi') {
            return 'warning';
        }
        return match ($status) {
            'baru'       => 'warning',
            'diproses'   => 'warning',
            'disetujui'  => 'info',
            'dikirim'    => 'primary',
            'selesai'    => 'success',
            'ditolak'    => 'danger',
            'dibatalkan' => 'secondary',
            default      => 'warning',
        };
    }
}

// ============================================================
// UPDATED: pesanan_status_step — nomor step (1-4) untuk stepper
// ============================================================
if (!function_exists('pesanan_status_step')) {
    function pesanan_status_step(?string $status): int
    {
        return match ($status) {
            'baru', 'diproses' => 1,  // Menunggu Verifikasi
            'disetujui'        => 2,  // Menunggu Dikirim
            'dikirim'          => 3,  // Dikirim
            'selesai'          => 4,  // Selesai
            'ditolak', 'dibatalkan' => 0,
            default            => 1,
        };
    }
}

// ============================================================
// UPDATED: pesanan_status_steps — definisi lengkap stepper Shopee-like
// ============================================================
if (!function_exists('pesanan_status_steps')) {
    function pesanan_status_steps(): array
    {
        return [
            ['label' => 'Menunggu Verifikasi', 'icon' => 'bi-clock-history'],
            ['label' => 'Menunggu Dikirim',    'icon' => 'bi-check2-circle'],
            ['label' => 'Dikirim',             'icon' => 'bi-truck'],
            ['label' => 'Selesai',             'icon' => 'bi-check-circle-fill'],
        ];
    }
}

// ============================================================
// UPDATED: kredit_state — return array untuk color coding baris tabel kredit
// ============================================================
if (!function_exists('kredit_state')) {
    function kredit_state(array $jadwal): array
    {
        $status = $jadwal['status'] ?? '';
        if ($status === 'dibayar') {
            return ['class' => 'row-lunas',      'label' => 'Lunas',  'icon' => ''];
        }

        $jatuhTempo = $jadwal['tanggal_jatuh_tempo'] ?? '';
        if ($jatuhTempo !== '' && $status !== 'dibayar') {
            $today   = new \DateTime('today');
            $dueDate = new \DateTime($jatuhTempo);
            $diff    = (int) $today->diff($dueDate)->format('%r%a');

            if ($diff < 0) {
                // Overdue — merah + jumlah hari telat
                return [
                    'class' => 'row-overdue',
                    'label' => abs($diff) . ' hari telat',
                    'icon'  => '🔴',
                ];
            }
            if ($diff <= 3) {
                // H-3 — warning/oranye
                return [
                    'class' => 'row-h3',
                    'label' => 'H-' . $diff . ' jatuh tempo',
                    'icon'  => '⚠️',
                ];
            }
        }

        return ['class' => '', 'label' => '', 'icon' => ''];
    }
}

// ============================================================
// UPDATED: email_status_label — label untuk email notifikasi status
// ============================================================
if (!function_exists('email_status_label')) {
    function email_status_label(?string $status): string
    {
        return match ($status) {
            'baru'       => 'Menunggu Verifikasi',
            'diproses'   => 'Menunggu Verifikasi',
            'disetujui'  => 'Menunggu Dikirim',
            'dikirim'    => 'Sedang Dikirim ke Alamat Anda',
            'selesai'    => 'Selesai',
            'ditolak'    => 'Ditolak',
            'dibatalkan' => 'Dibatalkan',
            default      => 'Diperbarui',
        };
    }
}
