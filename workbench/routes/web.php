<?php

use BradieTilley\Impersonation\Http\Middleware\ForbiddenUnlessImpersonating;
use BradieTilley\Impersonation\Http\Middleware\ForbiddenWhileImpersonating;
use BradieTilley\Impersonation\ImpersonationManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('example-page/{int}', function (Request $request, int $int) {
    ImpersonationManager::log('Something happened', [
        'int' => $int,
    ]);

    return response()->json([
        'int' => $int,
        'name' => $request->user()?->name,
    ]);
});

Route::get('forbidden-unless-impersonating', function () {
    return response()->json([
        'success' => true,
    ]);
})->middleware([
    ForbiddenUnlessImpersonating::class,
]);

Route::get('forbidden-when-impersonating', function () {
    return response()->json([
        'success' => true,
    ]);
})->middleware([
    ForbiddenWhileImpersonating::class,
]);
