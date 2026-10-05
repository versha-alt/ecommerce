<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\PaymentEventController;
use App\Http\Middleware\AdminSession;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['service' => 'Olive Commerce Laravel API', 'admin' => 'http://localhost:5173']));
Route::prefix('api/v1')->group(function () {
    Route::post('payment-events', PaymentEventController::class)->middleware('throttle:120,1');
    Route::get('health', fn () => ['status' => 'ok', 'framework' => 'Laravel', 'database' => 'mysql']);
    Route::post('auth/login', [AdminController::class, 'login'])->middleware('throttle:10,1');
    Route::get('media/{file}', [AdminController::class, 'media']);
    Route::middleware(AdminSession::class)->group(function () {
        Route::post('auth/logout', [AdminController::class, 'logout']);
        Route::get('workspace', [AdminController::class, 'workspace']);
        Route::post('products/import', [AdminController::class, 'importProducts']);
        Route::post('inventory/adjust', [AdminController::class, 'inventory']);
        Route::post('orders', [AdminController::class, 'order']);
        Route::post('order-actions/{id}', [AdminController::class, 'orderAction']);
        Route::post('returns/{id}/refund', [AdminController::class, 'refund']);
        Route::patch('settings', [AdminController::class, 'settings']);
        Route::post('uploads', [AdminController::class, 'upload']);
        Route::post('{resource}/{id}/retire', [AdminController::class, 'retire']);
        Route::patch('{resource}/{id}', [AdminController::class, 'save']);
        Route::post('{resource}', [AdminController::class, 'save']);
    });
});
