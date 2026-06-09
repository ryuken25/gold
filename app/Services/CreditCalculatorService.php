<?php

namespace App\Services;

use DateInterval;
use DateTimeImmutable;

class CreditCalculatorService
{
    public function calculate($hargaPokok, $marginPersen, int $tenorBulan, string $periodeAngsuran, $uangMuka = 0): array
    {
        $hargaPokok = (int) round((float) $hargaPokok);
        $marginPersen = round((float) $marginPersen, 2);
        $jumlahPeriode = $periodeAngsuran === 'mingguan' ? max(1, $tenorBulan * 4) : max(1, $tenorBulan);
        $marginNominal = (int) round($hargaPokok * $marginPersen / 100);
        $totalHargaKredit = $hargaPokok + $marginNominal;

        // Uang muka (DP) = nominal tetap (BUKAN persen), dibatasi 0..total.
        $uangMuka  = max(0, min((int) round((float) $uangMuka), $totalHargaKredit));
        $sisaPokok = $totalHargaKredit - $uangMuka;

        // Angsuran dihitung dari sisa setelah DP.
        $nominalAngsuran = (int) ceil($sisaPokok / $jumlahPeriode);

        return [
            'harga_pokok' => $hargaPokok,
            'margin_persen' => $marginPersen,
            'margin_nominal' => $marginNominal,
            'total_harga_kredit' => $totalHargaKredit,
            'uang_muka' => $uangMuka,
            'sisa_pokok' => $sisaPokok,
            'tenor_bulan' => $tenorBulan,
            'periode_angsuran' => $periodeAngsuran,
            'jumlah_periode' => $jumlahPeriode,
            'nominal_angsuran' => $nominalAngsuran,
            'periode_label' => periode_label($periodeAngsuran),
        ];
    }

    public function generateSchedule(string $tanggalJatuhTempoPertama, array $kalkulasi): array
    {
        $tanggal = new DateTimeImmutable($tanggalJatuhTempoPertama);
        $jadwal = [];
        // Jadwal mengangsur SISA setelah DP (fallback ke total bila tak ada).
        $sisa = (int) ($kalkulasi['sisa_pokok'] ?? $kalkulasi['total_harga_kredit']);

        for ($i = 1; $i <= (int) $kalkulasi['jumlah_periode']; $i++) {
            if ($i > 1) {
                $tanggal = $kalkulasi['periode_angsuran'] === 'mingguan'
                    ? $tanggal->add(new DateInterval('P7D'))
                    : $tanggal->add(new DateInterval('P1M'));
            }

            $nominalTagihan = $i === (int) $kalkulasi['jumlah_periode']
                ? $sisa
                : min((int) $kalkulasi['nominal_angsuran'], $sisa);

            $sisa -= $nominalTagihan;

            $jadwal[] = [
                'angsuran_ke' => $i,
                'tanggal_jatuh_tempo' => $tanggal->format('Y-m-d'),
                'nominal_tagihan' => $nominalTagihan,
                'nominal_dibayar' => 0,
                'status' => 'belum_dibayar',
                'tanggal_dibayar' => null,
            ];
        }

        return $jadwal;
    }

    public function applyPaymentsToSchedule(array $schedules, int $totalPembayaran, string $today = 'now'): array
    {
        $todayDate = new DateTimeImmutable($today);
        $remaining = $totalPembayaran;
        $result = [];

        foreach ($schedules as $schedule) {
            $nominalTagihan = (int) round((float) $schedule['nominal_tagihan']);
            $dibayar = min($remaining, $nominalTagihan);
            $remaining -= $dibayar;

            $status = 'belum_dibayar';
            if ($dibayar >= $nominalTagihan) {
                $status = 'dibayar';
            } elseif ($dibayar > 0) {
                $status = 'sebagian';
            } elseif ($todayDate->format('Y-m-d') > $schedule['tanggal_jatuh_tempo']) {
                $status = 'terlambat';
            }

            $result[] = [
                'id' => $schedule['id'] ?? null,
                'nominal_dibayar' => $dibayar,
                'status' => $status,
                'tanggal_dibayar' => $dibayar > 0 ? $todayDate->format('Y-m-d') : null,
                'nominal_tagihan' => $schedule['nominal_tagihan'],
                'tanggal_jatuh_tempo' => $schedule['tanggal_jatuh_tempo'],
            ];
        }

        return $result;
    }
}
