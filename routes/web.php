<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/up', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]);
});

// Landing page (central domain)
Route::get('/', function () {
    if (is_tenant()) {
        return app(StorefrontController::class)->home();
    }

    return view('welcome');
})->name('home');

// Registration for new tenants
Route::get('/register', function () {
    return view('register');
})->name('register');

Route::post('/register', [App\Http\Controllers\TenantRegistrationController::class, 'store'])
    ->name('register.store');

// Storefront routes (tenant context)
Route::middleware(['tenant'])->group(function () {
    // Shop pages
    Route::get('/shop', [StorefrontController::class, 'shop'])->name('shop');
    Route::get('/shop/{category:slug}', [StorefrontController::class, 'category'])->name('shop.category');
    Route::get('/product/{product:slug}', [StorefrontController::class, 'product'])->name('product.show');
    Route::get('/search', [StorefrontController::class, 'search'])->name('search');

    // Cart
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{item}', [CartController::class, 'remove'])->name('cart.remove');
    Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
    Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon');
    Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'process'])->name('checkout.process');
    Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/failed', [CheckoutController::class, 'failed'])->name('checkout.failed');

    // Customer area
    Route::middleware(['auth:customer'])->prefix('account')->name('customer.')->group(function () {
        Route::get('/', [CustomerController::class, 'dashboard'])->name('dashboard');
        Route::get('/orders', [CustomerController::class, 'orders'])->name('orders');
        Route::get('/orders/{order}', [CustomerController::class, 'orderDetails'])->name('orders.show');
        Route::get('/addresses', [CustomerController::class, 'addresses'])->name('addresses');
        Route::post('/addresses', [CustomerController::class, 'storeAddress'])->name('addresses.store');
        Route::put('/addresses/{address}', [CustomerController::class, 'updateAddress'])->name('addresses.update');
        Route::delete('/addresses/{address}', [CustomerController::class, 'deleteAddress'])->name('addresses.destroy');
        Route::get('/wishlist', [CustomerController::class, 'wishlist'])->name('wishlist');
        Route::get('/profile', [CustomerController::class, 'profile'])->name('profile');
        Route::put('/profile', [CustomerController::class, 'updateProfile'])->name('profile.update');
    });

    // Customer auth
    Route::get('/login', [CustomerController::class, 'showLogin'])->name('customer.login');
    Route::post('/login', [CustomerController::class, 'login'])->name('customer.login.submit');
    Route::get('/signup', [CustomerController::class, 'showSignup'])->name('customer.signup');
    Route::post('/signup', [CustomerController::class, 'signup'])->name('customer.signup.submit');
    Route::post('/logout', [CustomerController::class, 'logout'])->name('customer.logout');
    Route::get('/forgot-password', [CustomerController::class, 'showForgotPassword'])->name('customer.password.request');
    Route::post('/forgot-password', [CustomerController::class, 'sendResetLink'])->name('customer.password.email');
    Route::get('/reset-password/{token}', [CustomerController::class, 'showResetPassword'])->name('customer.password.reset');
    Route::post('/reset-password', [CustomerController::class, 'resetPassword'])->name('customer.password.update');

    // Wishlist
    Route::post('/wishlist/add', [CustomerController::class, 'addToWishlist'])->name('wishlist.add');
    Route::delete('/wishlist/{item}', [CustomerController::class, 'removeFromWishlist'])->name('wishlist.remove');

    // Static pages
    Route::get('/about', [StorefrontController::class, 'about'])->name('about');
    Route::get('/contact', [StorefrontController::class, 'contact'])->name('contact');
    Route::post('/contact', [StorefrontController::class, 'sendContact'])->name('contact.send');
    Route::get('/terms', [StorefrontController::class, 'terms'])->name('terms');
    Route::get('/privacy', [StorefrontController::class, 'privacy'])->name('privacy');
    Route::get('/shipping', [StorefrontController::class, 'shipping'])->name('shipping');
    Route::get('/returns', [StorefrontController::class, 'returns'])->name('returns');
});

// Payment callbacks (no tenant middleware as they may come from external sources)
Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/callback/{gateway}', [PaymentController::class, 'callback'])->name('callback');
    Route::post('/webhook/{gateway}', [PaymentController::class, 'webhook'])->name('webhook');
});
