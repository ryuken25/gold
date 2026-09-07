<?php

namespace App\Libraries;

use CodeIgniter\Debug\ExceptionHandler;
use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Security\Exceptions\SecurityException;
use Throwable;

/**
 * Custom exception handler that returns JSON for AJAX CSRF errors
 * instead of HTML debug page.
 *
 * The signature must match ExceptionHandlerInterface exactly — a mismatch
 * turns every handled exception into a fatal error, so the caller receives an
 * empty body instead of the JSON payload below.
 */
class AjaxExceptionHandler implements ExceptionHandlerInterface
{
    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode,
    ): void {
        // For AJAX requests with SecurityException (CSRF 403), return JSON
        if ($exception instanceof SecurityException && $statusCode === 403 && $this->isAjax($request)) {
            $response->setStatusCode(403)
                ->setHeader('Content-Type', 'application/json')
                ->setBody(json_encode([
                    'success' => false,
                    'message' => 'Sesi keamanan sudah kedaluwarsa. Halaman akan dimuat ulang.',
                    'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
                ]));
            $response->send();
            exit($exitCode);
        }

        // For all other cases, use default CI4 handler
        (new ExceptionHandler(config('Exceptions')))
            ->handle($exception, $request, $response, $statusCode, $exitCode);
    }

    protected function isAjax(RequestInterface $request): bool
    {
        if (!$request instanceof IncomingRequest) {
            return false;
        }

        return $request->isAJAX()
            || str_contains($request->getHeaderLine('Accept'), 'application/json')
            || $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }
}
