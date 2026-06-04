<?php

namespace App\Services;

use App\Models\WhatsAppLogModel;
use Config\Services;
use Throwable;

/**
 * Backup notifikasi via gateway WhatsApp (generik endpoint + token dari .env).
 *
 * Graceful no-op bila WA_GATEWAY_URL kosong — tidak pernah melempar exception,
 * dan selalu mencatat hasil ke whatsapp_logs. Tidak boleh menggagalkan alur.
 */
class WhatsAppGatewayService
{
    public function __construct(protected ?WhatsAppLogModel $logModel = null)
    {
        helper('mahen');
        $this->logModel ??= new WhatsAppLogModel();
    }

    public function send(string $nomor, string $pesan): bool
    {
        $nomor = wa_number_normalize($nomor);
        $url   = (string) env('WA_GATEWAY_URL', '');
        $token = (string) env('WA_GATEWAY_TOKEN', '');

        if ($nomor === '') {
            $this->log($nomor, $pesan, 'gagal', 'Nomor tujuan kosong.');
            return false;
        }
        if ($url === '') {
            $this->log($nomor, $pesan, 'gagal', 'WA gateway belum dikonfigurasi (WA_GATEWAY_URL kosong).');
            return false;
        }

        try {
            $client  = Services::curlrequest(['timeout' => 8, 'http_errors' => false]);
            $headers = ['Accept' => 'application/json'];
            if ($token !== '') {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            $resp = $client->post($url, [
                'headers'     => $headers,
                'form_params' => ['target' => $nomor, 'message' => $pesan],
            ]);

            $code = $resp->getStatusCode();
            $ok   = $code >= 200 && $code < 300;
            $this->log($nomor, $pesan, $ok ? 'dikirim_manual' : 'gagal', $ok ? null : ('HTTP ' . $code));

            return $ok;
        } catch (Throwable $e) {
            $this->log($nomor, $pesan, 'gagal', $e->getMessage());
            return false;
        }
    }

    protected function log(string $nomor, string $pesan, string $status, ?string $error): void
    {
        $this->logModel->insert([
            'tipe'         => 'konfirmasi_pesanan',
            'target'       => 'pelanggan',
            'tujuan_nomor' => $nomor ?: null,
            'pesan'        => $pesan . ($error ? "\n\n[GATEWAY] " . $error : ''),
            'status'       => $status === 'dikirim_manual' ? 'dikirim_manual' : 'gagal',
            'related_type' => 'wa_gateway',
            'created_by'   => null,
        ]);
    }
}
