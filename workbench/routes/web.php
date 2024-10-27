<?php

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
