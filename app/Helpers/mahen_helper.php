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
            'aktif', 'dibayar', 'dikirim_manual' => 'success',
            'lunas' => 'primary',
            'terlambat', 'gagal', 'dibatalkan' => 'danger',
            'sebagian' => 'warning',
            'dibuka' => 'info',
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
