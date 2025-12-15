<?php

use App\Exceptions\InsufficientAssetsException;
use App\Exceptions\InsufficientBalanceException;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
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
    })
    ->create();
