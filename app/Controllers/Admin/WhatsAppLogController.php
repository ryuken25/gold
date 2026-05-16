<?php

namespace App\Controllers\Admin;

use App\Models\WhatsAppLogModel;

class WhatsAppLogController extends BaseAdminController
{
    public function index(): string
    {
        $model = new WhatsAppLogModel();
        $tipe = (string) $this->request->getGet('tipe');
        $status = (string) $this->request->getGet('status');

        if ($tipe !== '') {
            $model->where('tipe', $tipe);
        }
        if ($status !== '') {
            $model->where('status', $status);
        }

        return $this->render('admin/whatsapp_logs/index', [
            'pageTitle' => 'WhatsApp Logs',
            'rows' => $model->orderBy('created_at', 'DESC')->paginate(20),
            'pager' => $model->pager,
            'tipe' => $tipe,
            'status' => $status,
        ]);
    }
}
