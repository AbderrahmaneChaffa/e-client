<?php

use App\Http\Middleware\RoleMiddleware;
use App\UserRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withCommands()
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo(function (Request $request): string {
            return $request->user()?->role === UserRole::ADMIN
                ? '/admin/dashboard'
                : '/client/dashboard';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
