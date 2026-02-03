<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function index(): View
    {
        return view('storefront.cart');
    }

    public function add(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Added to cart', 'cart_count' => 0]);
        }
        return redirect()->back()->with('success', 'Added to cart');
    }

    public function update($item, Request $request): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Cart updated']);
        }
        return redirect()->back()->with('success', 'Cart updated');
    }

    public function remove($item): JsonResponse|RedirectResponse
    {
        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Removed from cart']);
        }
        return redirect()->back()->with('success', 'Removed from cart');
    }

    public function clear(): RedirectResponse
    {
        return redirect()->back()->with('success', 'Cart cleared');
    }

    public function applyCoupon(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Coupon applied']);
        }
        return redirect()->back()->with('success', 'Coupon applied');
    }

    public function removeCoupon(): JsonResponse|RedirectResponse
    {
        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Coupon removed']);
        }
        return redirect()->back()->with('success', 'Coupon removed');
    }
}
