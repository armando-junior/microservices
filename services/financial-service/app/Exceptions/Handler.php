<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Src\Application\Exceptions\AccountPayableNotFoundException;
use Src\Application\Exceptions\AccountReceivableNotFoundException;
use Src\Application\Exceptions\CategoryNotFoundException;
use Src\Application\Exceptions\SupplierAlreadyExistsException;
use Src\Application\Exceptions\SupplierNotFoundException;
use Src\Domain\Exceptions\InvalidAccountPayableException;
use Src\Domain\Exceptions\InvalidAccountReceivableException;
use Src\Domain\Exceptions\InvalidSupplierException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
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
            //
        });

        // Application Exceptions
        $this->renderable(function (SupplierNotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        });

        $this->renderable(function (SupplierAlreadyExistsException $e) {
            return $this->jsonError($e->getMessage(), 409);
        });

        $this->renderable(function (CategoryNotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        });

        $this->renderable(function (AccountPayableNotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        });

        $this->renderable(function (AccountReceivableNotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        });

        // Domain Exceptions
        $this->renderable(function (InvalidSupplierException $e) {
            return $this->jsonError($e->getMessage(), 400);
        });

        $this->renderable(function (InvalidAccountPayableException $e) {
            return $this->jsonError($e->getMessage(), 400);
        });

        $this->renderable(function (InvalidAccountReceivableException $e) {
            return $this->jsonError($e->getMessage(), 400);
        });

        // Validation Exception
        $this->renderable(function (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        });
    }

    /**
     * Helper para retornar erro JSON padronizado
     */
    private function jsonError(string $message, int $status): JsonResponse
    {
        return response()->json([
            'error' => class_basename(debug_backtrace()[1]['args'][0]),
            'message' => $message,
        ], $status);
    }
}


