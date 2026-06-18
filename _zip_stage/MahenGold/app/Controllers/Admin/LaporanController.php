<?php

namespace App\Controllers\Admin;

use Config\Database;

class LaporanController extends BaseAdminController
{
    public function kredit()
    {
        $rows = $this->creditRows();
        if ($this->request->getGet('export') === 'csv') {
            return $this->csv('laporan-kredit.csv', ['Kode Kredit', 'Nasabah', 'Produk', 'Tanggal', 'Total Kredit', 'Terbayar', 'Sisa', 'Status'], array_map(static fn($row) => [
                $row['kode_kredit'],
                $row['nama_nasabah'],
                $row['nama_produk'],
                $row['tanggal_kredit'],
                $row['total_harga_kredit'],
                $row['total_terbayar'],
                $row['sisa_piutang'],
                $row['status'],
            ], $rows));
        }

        return $this->render('admin/laporan/kredit', ['pageTitle' => 'Laporan Kredit', 'rows' => $rows]);
    }

    public function pembayaran()
    {
        $rows = $this->paymentRows();
        if ($this->request->getGet('export') === 'csv') {
            return $this->csv('laporan-pembayaran.csv', ['Kode Pembayaran', 'Kode Kredit', 'Nasabah', 'Tanggal', 'Nominal', 'Metode'], array_map(static fn($row) => [
                $row['kode_pembayaran'],
                $row['kode_kredit'],
                $row['nama_nasabah'],
                $row['tanggal_bayar'],
                $row['nominal_bayar'],
                $row['metode_pembayaran'],
            ], $rows));
        }

        return $this->render('admin/laporan/pembayaran', ['pageTitle' => 'Laporan Pembayaran', 'rows' => $rows]);
    }

    public function piutang()
    {
        $rows = $this->creditRows();
        if ($this->request->getGet('export') === 'csv') {
            return $this->csv('laporan-piutang.csv', ['Kode Kredit', 'Nasabah', 'Sisa Piutang', 'Status'], array_map(static fn($row) => [
                $row['kode_kredit'],
                $row['nama_nasabah'],
                $row['sisa_piutang'],
                $row['status'],
            ], $rows));
        }

        return $this->render('admin/laporan/piutang', ['pageTitle' => 'Laporan Piutang', 'rows' => $rows]);
    }

    protected function creditRows(): array
    {
        $start = $this->request->getGet('start_date');
        $end = $this->request->getGet('end_date');
        $status = $this->request->getGet('status');

        $builder = Database::connect()->table('kredit k')
            ->select('k.*, n.nama as nama_nasabah, p.nama_produk')
            ->join('nasabah n', 'n.id = k.nasabah_id')
            ->join('produk_emas p', 'p.id = k.produk_emas_id')
            ->orderBy('k.tanggal_kredit', 'DESC');
        if ($start) {
            $builder->where('k.tanggal_kredit >=', $start);
        }
        if ($end) {
            $builder->where('k.tanggal_kredit <=', $end);
        }
        if ($status) {
            $builder->where('k.status', $status);
        }

        return $builder->get()->getResultArray();
    }

    protected function paymentRows(): array
    {
        $start = $this->request->getGet('start_date');
        $end = $this->request->getGet('end_date');
        $builder = Database::connect()->table('pembayaran_angsuran pb')
            ->select('pb.*, k.kode_kredit, n.nama as nama_nasabah')
            ->join('kredit k', 'k.id = pb.kredit_id')
            ->join('nasabah n', 'n.id = k.nasabah_id')
            ->orderBy('pb.tanggal_bayar', 'DESC');
        if ($start) {
            $builder->where('pb.tanggal_bayar >=', $start);
        }
        if ($end) {
            $builder->where('pb.tanggal_bayar <=', $end);
        }

        return $builder->get()->getResultArray();
    }

    protected function csv(string $filename, array $headers, array $rows)
    {
        $output = fopen('php://temp', 'w+');
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $this->response
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($content ?: '');
    }
}
