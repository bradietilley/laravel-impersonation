<?php

use BradieTilley\Impersonation\Http\Controllers\ImpersonationStartController;
use BradieTilley\Impersonation\Http\Controllers\ImpersonationStopController;
use BradieTilley\Impersonation\ImpersonationConfig;
use Illuminate\Support\Facades\Route;

if (ImpersonationConfig::getRoutingEnabled()) {
    Route::post('/api/impersonation/start', ImpersonationStartController::class);
    Route::post('/api/impersonation/stop', ImpersonationStopController::class);
}
