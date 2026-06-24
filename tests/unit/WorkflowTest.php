<?php

use CodeIgniter\Test\CIUnitTestCase;

class WorkflowTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('mahen');
    }

    // ============================================================
    // HELPER TESTS (no database needed)
    // ============================================================

    public function testPesananStatusLabel()
    {
        $this->assertEquals('Menunggu Verifikasi', pesanan_status_label('baru'));
        $this->assertEquals('Menunggu Verifikasi', pesanan_status_label('diproses'));
        $this->assertEquals('Menunggu Dikirim', pesanan_status_label('disetujui'));
        $this->assertEquals('Dikirim', pesanan_status_label('dikirim'));
        $this->assertEquals('Diterima', pesanan_status_label('diterima'));
        $this->assertEquals('Selesai', pesanan_status_label('selesai'));
        $this->assertEquals('Ditolak', pesanan_status_label('ditolak'));
        $this->assertEquals('Dibatalkan', pesanan_status_label('dibatalkan'));
    }

    public function testPesananStatusStep()
    {
        $this->assertEquals(1, pesanan_status_step('baru'));
        $this->assertEquals(1, pesanan_status_step('diproses'));
        $this->assertEquals(2, pesanan_status_step('disetujui'));
        $this->assertEquals(3, pesanan_status_step('dikirim'));
        $this->assertEquals(4, pesanan_status_step('diterima'));
        $this->assertEquals(5, pesanan_status_step('selesai'));
        $this->assertEquals(0, pesanan_status_step('ditolak'));
        $this->assertEquals(0, pesanan_status_step('dibatalkan'));
    }

    public function testPesananStatusStepsCount()
    {
        $steps = pesanan_status_steps();
        $this->assertCount(5, $steps);
        $this->assertEquals('Menunggu Verifikasi', $steps[0]['label']);
        $this->assertEquals('Menunggu Dikirim', $steps[1]['label']);
        $this->assertEquals('Dikirim', $steps[2]['label']);
        $this->assertEquals('Diterima', $steps[3]['label']);
        $this->assertEquals('Selesai', $steps[4]['label']);
    }

    public function testEmailStatusLabelMatchesPesanan()
    {
        // Email labels match for most statuses (dikirim is more descriptive in email)
        $this->assertEquals('Menunggu Verifikasi', email_status_label('baru'));
        $this->assertEquals('Menunggu Verifikasi', email_status_label('diproses'));
        $this->assertEquals('Menunggu Dikirim', email_status_label('disetujui'));
        $this->assertNotEmpty(email_status_label('dikirim'));
        $this->assertEquals('Diterima Pelanggan', email_status_label('diterima'));
        $this->assertEquals('Selesai', email_status_label('selesai'));
        $this->assertEquals('Ditolak', email_status_label('ditolak'));
        $this->assertEquals('Dibatalkan', email_status_label('dibatalkan'));
    }

    public function testPesananBadgeClassReturnsValid()
    {
        $validClasses = ['warning', 'info', 'primary', 'success', 'danger', 'secondary'];
        $statuses = ['baru', 'diproses', 'disetujui', 'dikirim', 'diterima', 'selesai', 'ditolak', 'dibatalkan'];
        foreach ($statuses as $status) {
            $class = pesanan_badge_class($status);
            $this->assertContains($class, $validClasses, "Badge class for '$status' invalid");
        }
    }

    public function testKreditStatePaid()
    {
        $jadwal = ['status' => 'dibayar', 'tanggal_jatuh_tempo' => '2026-01-01'];
        $state = kredit_state($jadwal);
        $this->assertEquals('Lunas', $state['label']);
        $this->assertEquals('row-lunas', $state['class']);
    }

    public function testKreditStateOverdue()
    {
        $jadwal = ['status' => 'belum_dibayar', 'tanggal_jatuh_tempo' => '2020-01-01'];
        $state = kredit_state($jadwal);
        $this->assertStringContainsString('hari telat', $state['label']);
        $this->assertEquals('row-overdue', $state['class']);
    }

    public function testKreditStateNormal()
    {
        $future = date('Y-m-d', strtotime('+10 days'));
        $jadwal = ['status' => 'belum_dibayar', 'tanggal_jatuh_tempo' => $future];
        $state = kredit_state($jadwal);
        $this->assertEquals('', $state['class']);
    }

    public function testFinalStatesHaveStepZero()
    {
        $this->assertEquals(0, pesanan_status_step('ditolak'));
        $this->assertEquals(0, pesanan_status_step('dibatalkan'));
    }

    public function testActiveStatesHavePositiveSteps()
    {
        foreach (['baru', 'diproses', 'disetujui', 'dikirim', 'diterima', 'selesai'] as $status) {
            $this->assertGreaterThan(0, pesanan_status_step($status), "Status '$status' should have positive step");
        }
    }

    public function testStepperLabelsAreUnique()
    {
        $steps = pesanan_status_steps();
        $labels = array_column($steps, 'label');
        $this->assertCount(count(array_unique($labels)), $labels, 'Stepper labels must be unique');
    }

    public function testBadgeClassForLunas()
    {
        $this->assertEquals('success', pesanan_badge_class('selesai'));
    }

    public function testBadgeClassForDitolak()
    {
        $this->assertEquals('danger', pesanan_badge_class('ditolak'));
    }

    public function testBadgeClassForDisetujui()
    {
        $this->assertEquals('info', pesanan_badge_class('disetujui'));
    }
}
