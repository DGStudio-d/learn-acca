<?php

namespace App\Exceptions;

use App\Services\MonitoringService;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Log critical errors with monitoring service
            if ($this->shouldReport($e)) {
                app(MonitoringService::class)->logCriticalError($e);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // Handle API requests with JSON responses
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->renderApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Render API exceptions as JSON responses
     */
    protected function renderApiException(Request $request, Throwable $e)
    {
        $status = 500;
        $code = 'INTERNAL_SERVER_ERROR';
        $message = 'An unexpected error occurred';
        $details = [];

        // Handle specific exception types
        if ($e instanceof ValidationException) {
            $status = 422;
            $code = 'VALIDATION_ERROR';
            $message = 'The given data was invalid';
            $details = ['errors' => $e->errors()];
        } elseif ($e instanceof NotFoundHttpException) {
            $status = 404;
            $code = 'RESOURCE_NOT_FOUND';
            $message = 'The requested resource was not found';
        } elseif ($e instanceof HttpException) {
            $status = $e->getStatusCode();
            $code = 'HTTP_ERROR';
            $message = $e->getMessage() ?: 'HTTP error occurred';
        } elseif (app()->environment(['local', 'testing'])) {
            // Show detailed error in development
            $message = $e->getMessage();
            $details = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(5)->toArray(),
            ];
        }

        // Log security events for certain status codes
        if (in_array($status, [401, 403, 429])) {
            app(MonitoringService::class)->logSecurityEvent('api.error', [
                'status_code' => $status,
                'message' => $message,
                'url' => $request->fullUrl(),
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
            'timestamp' => now()->toISOString(),
        ], $status);
    }

    /**
     * Determine if the exception should be reported
     */
    public function shouldReport(Throwable $e): bool
    {
        // Don't report validation exceptions
        if ($e instanceof ValidationException) {
            return false;
        }

        // Don't report 404 errors in production to avoid log spam
        if ($e instanceof NotFoundHttpException && app()->environment('production')) {
            return false;
        }

        return parent::shouldReport($e);
    }
}