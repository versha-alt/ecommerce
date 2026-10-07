<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\OrderEventController;
use App\Http\Controllers\PaymentEventController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\StorefrontController;
use App\Http\Middleware\AdminSession;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['service' => 'Olive Commerce Laravel API', 'admin' => 'http://localhost:5173']));
Route::prefix('api/v1')->group(function () {
    Route::prefix('store')->middleware('throttle:120,1,store:')->group(function () {
        Route::get('catalog', [StorefrontController::class, 'catalog']);
        Route::post('register', [StorefrontController::class, 'register'])->middleware('throttle:10,1,store-auth:');
        Route::post('login', [StorefrontController::class, 'login'])->middleware('throttle:10,1,store-auth:');
        Route::post('logout', [StorefrontController::class, 'logout']);
        Route::get('account', [StorefrontController::class, 'account']);
        Route::patch('profile', [StorefrontController::class, 'profile']);
        Route::post('checkout', [StorefrontController::class, 'checkout'])->middleware('throttle:20,1,checkout:');
        Route::post('quote', [StorefrontController::class, 'quote'])->middleware('throttle:30,1,quote:');
        Route::get('orders/{id}', [StorefrontController::class, 'order']);
        Route::post('orders/{id}/cancel', [StorefrontController::class, 'cancel']);
        Route::get('review-eligibility/{product}', [StorefrontController::class, 'reviewEligibility']);
        Route::post('reviews', [StorefrontController::class, 'review']);
        Route::post('returns', [StorefrontController::class, 'returns']);
        Route::post('enquiries', [StorefrontController::class, 'enquiry'])->middleware('throttle:10,1,enquiry:');
        Route::post('newsletter', [StorefrontController::class, 'newsletter'])->middleware('throttle:10,1,newsletter:');
    });

    Route::post('review-submissions', [ProductReviewController::class, 'submit'])->middleware('throttle:30,1');
    Route::get('products/{product}/reviews', [ProductReviewController::class, 'published'])->middleware('throttle:120,1');
    Route::post('order-events', OrderEventController::class)->middleware('throttle:120,1');
    Route::post('payment-events', PaymentEventController::class)->middleware('throttle:120,1');
    Route::get('health', fn () => ['status' => 'ok', 'framework' => 'Laravel', 'database' => 'mysql']);
    Route::post('auth/login', [AdminController::class, 'login'])->middleware('throttle:10,1');
    Route::get('media/{file}', [AdminController::class, 'media']);
    Route::middleware(AdminSession::class)->group(function () {
        Route::post('auth/logout', [AdminController::class, 'logout']);
        Route::patch('auth/profile', [AdminController::class, 'profile']);
        Route::get('workspace', [AdminController::class, 'workspace']);
        Route::post('products/import', [AdminController::class, 'importProducts']);
        Route::post('inventory/import', [AdminController::class, 'importInventory']);
        Route::post('inventory/adjust', [AdminController::class, 'inventory']);
        Route::post('orders', [AdminController::class, 'order']);
        Route::post('order-status/{id}', [AdminController::class, 'orderStatuses']);
        Route::post('order-actions/{id}', [AdminController::class, 'orderAction']);
        Route::post('returns/{id}/refund', [AdminController::class, 'refund']);
        Route::post('settings/test-email', [AdminController::class, 'testEmail'])->middleware('throttle:5,1,test-mail:');
        Route::patch('settings', [AdminController::class, 'settings']);
        Route::post('uploads', [AdminController::class, 'upload']);
        Route::post('{resource}/{id}/retire', [AdminController::class, 'retire']);
        Route::patch('{resource}/{id}', [AdminController::class, 'save']);
        Route::post('{resource}', [AdminController::class, 'save']);
    });
});
