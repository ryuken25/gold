<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
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
        ], $data));
    }
}
