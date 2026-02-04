<?php

declare(strict_types=1);

use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| These routes are for tenant storefronts.
| They are only accessible via tenant domains (custom domains).
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    // Storefront routes
    Route::get('/', [StorefrontController::class, 'index'])->name('storefront.index');
    Route::get('/products', [StorefrontController::class, 'products'])->name('storefront.products');
    Route::get('/category/{slug}', [StorefrontController::class, 'category'])->name('storefront.category');
    Route::get('/product/{slug}', [StorefrontController::class, 'product'])->name('storefront.product');
    Route::get('/page/{slug}', [StorefrontController::class, 'page'])->name('storefront.page');
});
