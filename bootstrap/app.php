<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\LoggerMiddleware;
use App\Http\Middleware\SupervisorMiddleware;
use App\Http\Middleware\ViewerAccessMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            ForcePasswordChange::class,
        ]);

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'supervisor' => SupervisorMiddleware::class,
            'logger' => LoggerMiddleware::class,
            'viewer_access' => ViewerAccessMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
