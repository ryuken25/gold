<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PengajuanModel;
use App\Models\PengaturanSistemModel;

abstract class BaseAdminController extends BaseController
{
    protected PengaturanSistemModel $pengaturanModel;

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->pengaturanModel = new PengaturanSistemModel();
    }

    protected function render(string $view, array $data = []): string
    {
        return view($view, array_merge([
            'admin' => current_admin(),
            'pengaturan' => $this->pengaturanModel->getPengaturan(),
            'pengajuanBaru' => (new PengajuanModel())->where('status', 'baru')->countAllResults(),
        ], $data));
    }

    /**
     * Success response — JSON untuk AJAX, redirect untuk non-AJAX.
     */
    protected function respondSuccess(string $message, ?string $redirect = null)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success'  => true,
                'message'  => $message,
                'redirect' => $redirect,
                'csrf'     => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }
        return redirect()->to($redirect ?? current_url())->with('success', $message);
    }

    /**
     * Error response — JSON 422/400/500 untuk AJAX, redirect dengan error untuk non-AJAX.
     */
    protected function respondError(string $message, int $statusCode = 400, array $errors = [])
    {
        if ($this->request->isAJAX()) {
            $payload = [
                'success' => false,
                'message' => $message,
                'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ];
            if ($errors) {
                $payload['errors'] = $errors;
            }
            return $this->response->setStatusCode($statusCode)->setJSON($payload);
        }
        return redirect()->back()->withInput()->with('error', $message);
    }
}
