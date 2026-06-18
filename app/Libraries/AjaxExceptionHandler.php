<?php

namespace App\Libraries;

use CodeIgniter\Debug\ExceptionHandler;
use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\Security\Exceptions\SecurityException;
use Throwable;

/**
 * Custom exception handler that returns JSON for AJAX CSRF errors
 * instead of HTML debug page.
 */
class AjaxExceptionHandler implements ExceptionHandlerInterface
{
    public function handle(Throwable $exception, int $statusCode, array $trace = []): void
    {
        // For AJAX requests with SecurityException (CSRF 403), return JSON
        if ($exception instanceof SecurityException && $statusCode === 403) {
            $request = service('request');
            $isAjax = $request->isAJAX()
                || ($request->getHeaderLine('Accept') ?? '') === 'application/json'
                || ($request->getHeaderLine('X-Requested-With') ?? '') === 'XMLHttpRequest';

            if ($isAjax) {
                $response = service('response');
                $response->setStatusCode(403)
                    ->setHeader('Content-Type', 'application/json')
                    ->setBody(json_encode([
                        'success' => false,
                        'message' => 'Sesi keamanan sudah kedaluwarsa. Halaman akan dimuat ulang.',
                        'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
                    ]));
                $response->send();
                exit;
            }
        }

        // For all other cases, use default CI4 handler
        $handler = new ExceptionHandler(config('Exceptions'));
        $handler->handle($exception, $statusCode, $trace);
    }
}
