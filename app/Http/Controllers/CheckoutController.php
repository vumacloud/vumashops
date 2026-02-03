<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CheckoutController extends Controller
{
    public function index(): View
    {
        return view('storefront.checkout');
    }

    public function process(Request $request): RedirectResponse
    {
        // Process checkout - implement based on cart and payment
        return redirect()->route('checkout.success', ['order' => 1]);
    }

    public function success($order): View
    {
        return view('storefront.checkout-success', compact('order'));
    }

    public function failed(): View
    {
        return view('storefront.checkout-failed');
    }
}
