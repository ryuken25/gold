<?php

namespace App\Services;

use App\Models\JadwalAngsuranModel;
use App\Models\KreditModel;
use App\Models\PembayaranAlokasiModel;
use App\Models\PembayaranAngsuranModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use RuntimeException;

class PaymentService
{
    protected BaseConnection $db;

    public function __construct(
        protected ?PembayaranAngsuranModel $pembayaranModel = null,
        protected ?PembayaranAlokasiModel $alokasiModel = null,
        protected ?KreditModel $kreditModel = null,
        protected ?JadwalAngsuranModel $jadwalModel = null,
    ) {
        $this->pembayaranModel ??= new PembayaranAngsuranModel();
        $this->alokasiModel    ??= new PembayaranAlokasiModel();
        $this->kreditModel     ??= new KreditModel();
        $this->jadwalModel     ??= new JadwalAngsuranModel();
        $this->db = Database::connect();
    }

    /**
     * Catat pembayaran angsuran dengan alokasi FIFO.
     * Invariant: total_terbayar + sisa_piutang = sisa_pokok_kredit
     */
    public function record(array $input, int $adminId): array
    {
        $this->db->transStart();

        // Lock kredit
        $kredit = $this->db->table('kredit')
            ->where('id', (int) $input['kredit_id'])
            ->forUpdate()
            ->get()->getRowArray();

        if (!$kredit || $kredit['status'] !== 'aktif') {
            $this->db->transRollback();
            throw new RuntimeException('Kredit aktif tidak ditemukan.');
        }

        $nominalBayar = (int) round((float) $input['nominal_bayar']);
        if ($nominalBayar < 1) {
            $this->db->transRollback();
            throw new RuntimeException('Nominal pembayaran harus lebih dari nol.');
        }

        $sisaPiutang = (int) round((float) $kredit['sisa_piutang']);
        if ($nominalBayar > $sisaPiutang) {
            $this->db->transRollback();
            throw new RuntimeException('Nominal pembayaran (' . format_rupiah($nominalBayar) . ') melebihi sisa piutang (' . format_rupiah($sisaPiutang) . ').');
        }

        // Check duplicate bukti
        if (!empty($input['bukti_pembayaran_id'])) {
            $existingPayment = $this->pembayaranModel
                ->where('bukti_pembayaran_id', $input['bukti_pembayaran_id'])
                ->first();
            if ($existingPayment) {
                $this->db->transRollback();
                throw new RuntimeException('Bukti pembayaran ini sudah tercatat sebagai pembayaran lain.');
            }
        }

        // Get schedules
        $schedules = $this->jadwalModel
            ->where('kredit_id', $kredit['id'])
            ->orderBy('angsuran_ke', 'ASC')
            ->findAll();

        if (!$schedules) {
            $this->db->transRollback();
            throw new RuntimeException('Jadwal angsuran tidak ditemukan.');
        }

        // Validate selected jadwal if provided
        $selectedId = (int) ($input['jadwal_angsuran_id'] ?? 0);
        $startIndex = 0;
        if ($selectedId > 0) {
            $found = false;
            foreach ($schedules as $index => $schedule) {
                if ((int) $schedule['id'] === $selectedId) {
                    $startIndex = $index;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->db->transRollback();
                throw new RuntimeException('Jadwal angsuran tidak ditemukan atau bukan milik kredit ini.');
            }
            // Check if earlier unpaid schedules exist
            for ($i = 0; $i < $startIndex; $i++) {
                $tagihan = (int) round((float) $schedules[$i]['nominal_tagihan']);
                $dibayar = (int) round((float) $schedules[$i]['nominal_dibayar']);
                if ($dibayar < $tagihan) {
                    $this->db->transRollback();
                    throw new RuntimeException('Masih ada angsuran ke-' . $schedules[$i]['angsuran_ke'] . ' yang belum lunas. Pembayaran dialokasikan secara FIFO dari angsuran terlama.');
                }
            }
        } else {
            // Find first unpaid
            foreach ($schedules as $index => $schedule) {
                if ((float) $schedule['nominal_dibayar'] < (float) $schedule['nominal_tagihan']) {
                    $startIndex = $index;
                    break;
                }
            }
        }

        // Insert payment
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

        // Allocate FIFO
        $remaining = $nominalBayar;
        $today = $input['tanggal_bayar'];
        $allocations = [];

        for ($i = $startIndex; $i < count($schedules); $i++) {
            if ($remaining <= 0) break;

            $schedule = $schedules[$i];
            $tagihan = (int) round((float) $schedule['nominal_tagihan']);
            $sudahDibayar = (int) round((float) $schedule['nominal_dibayar']);
            $belumTerbayar = max(0, $tagihan - $sudahDibayar);

            if ($belumTerbayar <= 0) continue;

            $alokasi = min($remaining, $belumTerbayar);
            $remaining -= $alokasi;
            $baruDibayar = $sudahDibayar + $alokasi;

            $status = 'sebagian';
            if ($baruDibayar >= $tagihan) {
                $status = 'dibayar';
            } elseif ($baruDibayar > 0) {
                $status = $today > $schedule['tanggal_jatuh_tempo'] ? 'terlambat' : 'belum_dibayar';
            }

            $this->jadwalModel->update($schedule['id'], [
                'nominal_dibayar' => $baruDibayar,
                'status'          => $status,
                'tanggal_dibayar' => $baruDibayar > 0 ? $today : null,
            ]);

            // Insert allocation
            $allocId = $this->alokasiModel->insert([
                'pembayaran_angsuran_id' => $paymentId,
                'jadwal_angsuran_id'     => $schedule['id'],
                'nominal_alokasi'        => $alokasi,
            ], true);
            $allocations[] = ['id' => $allocId, 'nominal' => $alokasi];
        }

        // Verify allocation sum
        $totalAlokasi = array_sum(array_column($allocations, 'nominal'));
        if ($totalAlokasi !== $nominalBayar) {
            $this->db->transRollback();
            throw new RuntimeException('Alokasi pembayaran tidak sesuai. Diharapkan ' . $nominalBayar . ', diterima ' . $totalAlokasi);
        }

        // Update kredit — invariant: total_terbayar + sisa_piutang = sisa_pokok_kredit
        $totalTerbayar = (int) round((float) $kredit['total_terbayar']) + $nominalBayar;
        $sisaPokok = (int) round((float) ($kredit['sisa_pokok_kredit'] ?? $kredit['total_harga_kredit']));
        $sisaPiutangBaru = max(0, $sisaPokok - $totalTerbayar);
        $statusKredit = $sisaPiutangBaru <= 0 ? 'lunas' : 'aktif';

        $this->kreditModel->update($kredit['id'], [
            'total_terbayar' => $totalTerbayar,
            'sisa_piutang'   => $sisaPiutangBaru,
            'status'         => $statusKredit,
        ]);

        // Refresh schedule statuses
        $this->refreshScheduleStatuses($kredit['id'], $today);

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            throw new RuntimeException('Gagal mencatat pembayaran.');
        }

        return [
            'payment'     => $this->pembayaranModel->find($paymentId),
            'credit'      => $this->kreditModel->find($kredit['id']),
            'allocations' => $allocations,
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

            if ($status !== $schedule['status']) {
                $this->jadwalModel->update($schedule['id'], [
                    'status'         => $status,
                    'tanggal_dibayar' => $dibayar > 0 ? ($schedule['tanggal_dibayar'] ?: $tanggalAcuan) : null,
                ]);
            }
        }
    }
}
