<?php

use App\Http\Controllers\Api\WhmcsProvisioningController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// WHMCS Provisioning API
Route::prefix('whmcs')->group(function () {
    Route::post('/create', [WhmcsProvisioningController::class, 'create']);
    Route::post('/suspend', [WhmcsProvisioningController::class, 'suspend']);
    Route::post('/unsuspend', [WhmcsProvisioningController::class, 'unsuspend']);
    Route::post('/terminate', [WhmcsProvisioningController::class, 'terminate']);
    Route::post('/change-plan', [WhmcsProvisioningController::class, 'changePlan']);
    Route::get('/status', [WhmcsProvisioningController::class, 'status']);
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
    ]);
});
