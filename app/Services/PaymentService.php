<?php

namespace App\Services;

use App\Models\JadwalAngsuranModel;
use App\Models\KreditModel;
use App\Models\PembayaranAngsuranModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use RuntimeException;

class PaymentService
{
    protected BaseConnection $db;

    public function __construct(
        protected ?PembayaranAngsuranModel $pembayaranModel = null,
        protected ?KreditModel $kreditModel = null,
        protected ?JadwalAngsuranModel $jadwalModel = null,
    ) {
        $this->pembayaranModel ??= new PembayaranAngsuranModel();
        $this->kreditModel ??= new KreditModel();
        $this->jadwalModel ??= new JadwalAngsuranModel();
        $this->db = Database::connect();
    }

    public function record(array $input, int $adminId): array
    {
        $kredit = $this->kreditModel->find((int) $input['kredit_id']);
        if (!$kredit || $kredit['status'] !== 'aktif') {
            throw new RuntimeException('Kredit aktif tidak ditemukan.');
        }

        $nominalBayar = (int) round((float) $input['nominal_bayar']);
        if ($nominalBayar < 1) {
            throw new RuntimeException('Nominal pembayaran harus lebih dari nol.');
        }

        $sisaPiutang = (int) round((float) $kredit['sisa_piutang']);
        if ($nominalBayar > $sisaPiutang) {
            throw new RuntimeException('Nominal pembayaran melebihi sisa piutang.');
        }

        $schedules = $this->jadwalModel->where('kredit_id', $kredit['id'])->orderBy('angsuran_ke', 'ASC')->findAll();
        if (!$schedules) {
            throw new RuntimeException('Jadwal angsuran tidak ditemukan.');
        }

        $selectedId = (int) ($input['jadwal_angsuran_id'] ?? 0);
        $startIndex = 0;
        if ($selectedId > 0) {
            foreach ($schedules as $index => $schedule) {
                if ((int) $schedule['id'] === $selectedId) {
                    $startIndex = $index;
                    break;
                }
            }
        } else {
            foreach ($schedules as $index => $schedule) {
                if ((float) $schedule['nominal_dibayar'] < (float) $schedule['nominal_tagihan']) {
                    $startIndex = $index;
                    break;
                }
            }
        }

        $this->db->transStart();

        $paymentId = $this->pembayaranModel->insert([
            'kode_pembayaran'      => 'PENDING',
            'kredit_id'            => $kredit['id'],
            'jadwal_angsuran_id'   => $selectedId ?: null,
            'bukti_pembayaran_id'  => $input['bukti_pembayaran_id'] ?? null,
            'tanggal_bayar'        => $input['tanggal_bayar'],
            'nominal_bayar'        => $nominalBayar,
            'metode_pembayaran'    => $input['metode_pembayaran'],
            'keterangan'           => $input['keterangan'] ?? null,
            'dicatat_oleh'         => $adminId,
        ], true);

        $this->pembayaranModel->update($paymentId, ['kode_pembayaran' => generate_kode('BYR', $paymentId)]);

        $remaining = $nominalBayar;
        $today = $input['tanggal_bayar'];

        for ($i = $startIndex; $i < count($schedules); $i++) {
            if ($remaining <= 0) {
                break;
            }

            $schedule = $schedules[$i];
            $tagihan = (int) round((float) $schedule['nominal_tagihan']);
            $sudahDibayar = (int) round((float) $schedule['nominal_dibayar']);
            $belumTerbayar = max(0, $tagihan - $sudahDibayar);
            if ($belumTerbayar <= 0) {
                continue;
            }

            $alokasi = min($remaining, $belumTerbayar);
            $remaining -= $alokasi;
            $baruDibayar = $sudahDibayar + $alokasi;

            $status = 'sebagian';
            if ($baruDibayar >= $tagihan) {
                $status = 'dibayar';
            } elseif ($baruDibayar <= 0) {
                $status = $today > $schedule['tanggal_jatuh_tempo'] ? 'terlambat' : 'belum_dibayar';
            }

            $this->jadwalModel->update($schedule['id'], [
                'nominal_dibayar' => $baruDibayar,
                'status' => $status,
                'tanggal_dibayar' => $baruDibayar > 0 ? $today : null,
            ]);
        }

        $totalTerbayar = (int) round((float) $kredit['total_terbayar']) + $nominalBayar;
        // UPDATED: Gunakan sisa_pokok_kredit (bukan total_harga_kredit) agar DP tidak dihitung sebagai utang
        $sisaPokok = (int) round((float) ($kredit['sisa_pokok_kredit'] ?? $kredit['total_harga_kredit']));
        $sisaPiutangBaru = max(0, $sisaPokok - $totalTerbayar);
        $statusKredit = $sisaPiutangBaru <= 0 ? 'lunas' : 'aktif';

        $this->refreshScheduleStatuses($kredit['id'], $today);

        $this->kreditModel->update($kredit['id'], [
            'total_terbayar' => $totalTerbayar,
            'sisa_piutang' => $sisaPiutangBaru,
            'status' => $statusKredit,
        ]);

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            throw new RuntimeException('Gagal mencatat pembayaran.');
        }

        return [
            'payment' => $this->pembayaranModel->find($paymentId),
            'credit' => $this->kreditModel->find($kredit['id']),
        ];
    }

    protected function refreshScheduleStatuses(int $kreditId, string $tanggalAcuan): void
    {
        $schedules = $this->jadwalModel->where('kredit_id', $kreditId)->findAll();
        foreach ($schedules as $schedule) {
            $tagihan = (float) $schedule['nominal_tagihan'];
            $dibayar = (float) $schedule['nominal_dibayar'];

            $status = 'belum_dibayar';
            if ($dibayar >= $tagihan) {
                $status = 'dibayar';
            } elseif ($dibayar > 0) {
                $status = 'sebagian';
            } elseif ($tanggalAcuan > $schedule['tanggal_jatuh_tempo']) {
                $status = 'terlambat';
            }

            $this->jadwalModel->update($schedule['id'], [
                'status' => $status,
                'tanggal_dibayar' => $dibayar > 0 ? ($schedule['tanggal_dibayar'] ?: $tanggalAcuan) : null,
            ]);
        }
    }
}
