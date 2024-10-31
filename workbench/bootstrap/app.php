<?php

use BradieTilley\Impersonation\Exceptions\ForbiddenUnlessImpersonatingException;
use BradieTilley\Impersonation\Exceptions\ForbiddenWhileImpersonatingException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use function Orchestra\Testbench\default_skeleton_path;

return Application::configure(basePath: $APP_BASE_PATH ?? default_skeleton_path())
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions
            ->render(function (ForbiddenWhileImpersonatingException $e) {
                return response()->json([
                    'success' => false,
                ], status: 403);
            })
            ->render(function (ForbiddenUnlessImpersonatingException $e) {
                return response()->json([
                    'success' => false,
                ], status: 403);
            });
    })->create();
