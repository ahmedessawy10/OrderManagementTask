<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        // لو الويبهوك ضاع، بنسأل الجيتواي عن الدفعات اللي لسه pending
        $schedule->command('payments:check-pending')->everyFiveMinutes()->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $isApi = fn (Request $request): bool => $request->is('api/*') || $request->expectsJson();

        /**
         * Project error envelope: {status: false, message, errors}.
         *
         * @param  array<string, string>  $headers
         */
        $envelope = fn (string $message, int $status, mixed $errors = null, array $headers = []): JsonResponse => response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status, $headers);

        // Framework exceptions (validation, auth, 403/404/405/429, ...) -> same envelope.
        // Callbacks run after Laravel's prepareException(), so ModelNotFoundException arrives as a
        // NotFoundHttpException and AuthorizationException (incl. denyAsNotFound) as an HttpException.
        $exceptions->render(function (Throwable $e, Request $request) use ($isApi, $envelope) {
            if (! $isApi($request) || $e instanceof HttpResponseException) {
                return null;
            }

            if ($e instanceof ValidationException) {
                if ($e->response !== null) {
                    return null;
                }

                return $envelope(
                    $e->validator->errors()->first() ?: __('messages.errors.validation'),
                    $e->status,
                    $e->errors(),
                );
            }

            if ($e instanceof AuthenticationException || $e instanceof JWTException) {
                return $envelope(__('messages.errors.unauthenticated'), 401);
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();

                $message = match (true) {
                    $status === 401 => __('messages.errors.unauthenticated'),
                    $status === 403 => __('messages.errors.forbidden'),
                    $status === 404 => __('messages.errors.not_found'),
                    $status === 405 => __('messages.errors.method_not_allowed'),
                    $status === 429 => __('messages.errors.too_many_requests'),
                    $status >= 500 => __('messages.errors.server_error'),
                    $e->getMessage() !== '' => $e->getMessage(),
                    $status === 400 => __('messages.errors.bad_request'),
                    default => __('messages.errors.http'),
                };

                return $envelope($message, $status, null, $e->getHeaders());
            }

            if (config('app.debug')) {
                return $envelope($e->getMessage() ?: __('messages.errors.server_error'), 500, [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }

            return $envelope(__('messages.errors.server_error'), 500);
        });
    })->create();
