<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\WhmcsProvisioningController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'version' => '1.0.0',
    ]);
});

// Public API routes (tenant context required)
Route::middleware(['api'])->group(function () {
    // Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::get('/products/{product}/reviews', [ProductController::class, 'reviews']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::get('/categories/{category}/products', [CategoryController::class, 'products']);

    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::put('/cart/items/{item}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{item}', [CartController::class, 'removeItem']);
    Route::post('/cart/coupon', [CartController::class, 'applyCoupon']);
    Route::delete('/cart/coupon', [CartController::class, 'removeCoupon']);

    // Payments
    Route::get('/payment-methods', [PaymentController::class, 'methods']);
    Route::post('/payments/initialize', [PaymentController::class, 'initialize']);
    Route::get('/payments/{reference}/verify', [PaymentController::class, 'verify']);

    // Customer authentication
    Route::post('/auth/register', [CustomerController::class, 'register']);
    Route::post('/auth/login', [CustomerController::class, 'login']);
    Route::post('/auth/forgot-password', [CustomerController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [CustomerController::class, 'resetPassword']);
    Route::post('/auth/verify-otp', [CustomerController::class, 'verifyOtp']);
});

// Protected customer routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth
    Route::post('/auth/logout', [CustomerController::class, 'logout']);
    Route::get('/auth/user', [CustomerController::class, 'user']);

    // Profile
    Route::get('/profile', [CustomerController::class, 'profile']);
    Route::put('/profile', [CustomerController::class, 'updateProfile']);
    Route::post('/profile/avatar', [CustomerController::class, 'updateAvatar']);

    // Addresses
    Route::get('/addresses', [CustomerController::class, 'addresses']);
    Route::post('/addresses', [CustomerController::class, 'storeAddress']);
    Route::put('/addresses/{address}', [CustomerController::class, 'updateAddress']);
    Route::delete('/addresses/{address}', [CustomerController::class, 'deleteAddress']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice']);

    // Wishlist
    Route::get('/wishlist', [CustomerController::class, 'wishlist']);
    Route::post('/wishlist', [CustomerController::class, 'addToWishlist']);
    Route::delete('/wishlist/{item}', [CustomerController::class, 'removeFromWishlist']);

    // Reviews
    Route::post('/products/{product}/reviews', [ProductController::class, 'addReview']);
});

// Payment webhooks (no auth required)
Route::prefix('webhooks')->group(function () {
    Route::post('/paystack', [PaymentController::class, 'paystackWebhook']);
    Route::post('/flutterwave', [PaymentController::class, 'flutterwaveWebhook']);
    Route::post('/mpesa/kenya', [PaymentController::class, 'mpesaKenyaWebhook']);
    Route::post('/mpesa/tanzania', [PaymentController::class, 'mpesaTanzaniaWebhook']);
    Route::post('/mtn-momo', [PaymentController::class, 'mtnMomoWebhook']);
    Route::post('/airtel-money', [PaymentController::class, 'airtelMoneyWebhook']);
});

/*
|--------------------------------------------------------------------------
| WHMCS Provisioning API
|--------------------------------------------------------------------------
|
| These endpoints are called by WHMCS to provision, suspend, unsuspend,
| and terminate VumaShops stores. Protected by API key authentication.
|
| Configure WHMCS_API_KEY in .env
|
*/
Route::prefix('whmcs')->middleware(\App\Http\Middleware\VerifyWhmcsApiKey::class)->group(function () {
    // Create new store (on order activation)
    Route::post('/create', [WhmcsProvisioningController::class, 'create']);

    // Suspend store (non-payment, abuse, etc.)
    Route::post('/suspend', [WhmcsProvisioningController::class, 'suspend']);

    // Unsuspend store (payment received, etc.)
    Route::post('/unsuspend', [WhmcsProvisioningController::class, 'unsuspend']);

    // Terminate store (cancellation)
    Route::post('/terminate', [WhmcsProvisioningController::class, 'terminate']);

    // Change plan (upgrade/downgrade)
    Route::post('/change-plan', [WhmcsProvisioningController::class, 'changePlan']);

    // Renew subscription
    Route::post('/renew', [WhmcsProvisioningController::class, 'renew']);

    // Get store status
    Route::post('/status', [WhmcsProvisioningController::class, 'status']);
    Route::get('/status', [WhmcsProvisioningController::class, 'status']);

    // Update store details
    Route::post('/update', [WhmcsProvisioningController::class, 'update']);
});
