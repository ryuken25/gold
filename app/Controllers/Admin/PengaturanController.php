<?php

namespace App\Controllers\Admin;

class PengaturanController extends BaseAdminController
{
    public function index(): string
    {
        return $this->render('admin/pengaturan/index', [
            'pageTitle' => 'Pengaturan Sistem',
            'setting' => $this->pengaturanModel->getPengaturan(),
        ]);
    }

    public function update()
    {
        $rules = [
            'nama_toko' => 'required',
            'nomor_whatsapp_toko' => 'required',
            'margin_default' => 'required|decimal',
            'logo_text' => 'required|max_length[20]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $setting = $this->pengaturanModel->getPengaturan();
        $this->pengaturanModel->update($setting['id'], [
            'nama_toko' => $this->request->getPost('nama_toko'),
            'nomor_whatsapp_toko' => wa_number_normalize((string) $this->request->getPost('nomor_whatsapp_toko')),
            'margin_default' => $this->request->getPost('margin_default'),
            'logo_text' => strtoupper((string) $this->request->getPost('logo_text')),
            'alamat_toko' => $this->request->getPost('alamat_toko') ?: null,
        ]);

        return redirect()->to('/admin/pengaturan')->with('success', 'Pengaturan berhasil diperbarui.');
    }
}
