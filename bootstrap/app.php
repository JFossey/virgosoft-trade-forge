<?php

use App\Exceptions\InsufficientAssetsException;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\OrderCannotBeCancelledException;
use App\Exceptions\OrderNotFoundException;
use App\Exceptions\UnauthorizedOrderAccessException;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        $middleware->alias([
            'guest' => RedirectIfAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Convert InsufficientBalanceException to ValidationException
        $exceptions->render(function (InsufficientBalanceException $e) {
            throw ValidationException::withMessages([
                'balance' => [
                    sprintf(
                        'Insufficient balance. Required: %s, Available: %s',
                        $e->getRequired(),
                        $e->getAvailable()
                    ),
                ],
            ]);
        });

        // Convert InsufficientAssetsException to ValidationException
        $exceptions->render(function (InsufficientAssetsException $e) {
            throw ValidationException::withMessages([
                'assets' => [
                    sprintf(
                        'Insufficient assets. Required: %s, Available: %s',
                        $e->getRequired(),
                        $e->getAvailable()
                    ),
                ],
            ]);
        });

        // Convert OrderNotFoundException to 404 response
        $exceptions->render(function (OrderNotFoundException $e) {
            return new JsonResponse([
                'message' => 'Order not found',
            ], 404);
        });

        // Convert UnauthorizedOrderAccessException to 403 response
        $exceptions->render(function (UnauthorizedOrderAccessException $e) {
            return new JsonResponse([
                'message' => 'Unauthorized to cancel this order',
            ], 403);
        });

        // Convert OrderCannotBeCancelledException to ValidationException
        $exceptions->render(function (OrderCannotBeCancelledException $e) {
            throw ValidationException::withMessages([
                'order' => [$e->getMessage()],
            ]);
        });
    })
    ->create();
