<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });

        $this->renderable(function (AuthorizationException $e, $request) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to perform this action.',
                ], 403);
            }
        });

        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                ], 404);
            }
        });

        $this->renderable(function (ValidationException $e, $request) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });
    }

    public function render($request, Throwable $e)
    {
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $this->getErrorMessage($statusCode),
                ], $statusCode);
            }
            
            if (view()->exists("errors.{$statusCode}")) {
                return response()->view("errors.{$statusCode}", [], $statusCode);
            }
        }

        return parent::render($request, $e);
    }

    protected function getErrorMessage(int $statusCode): string
    {
        return match($statusCode) {
            400 => 'Bad request.',
            401 => 'Unauthorized.',
            403 => 'Forbidden.',
            404 => 'Not found.',
            405 => 'Method not allowed.',
            429 => 'Too many requests.',
            500 => 'Internal server error.',
            503 => 'Service unavailable.',
            default => 'An error occurred.',
        };
    }
}