<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function callback($gateway, Request $request): RedirectResponse
    {
        // Handle payment callback and redirect to success/failed page
        return redirect()->route('checkout.success', ['order' => 1]);
    }

    public function webhook($gateway, Request $request): JsonResponse
    {
        // Handle payment webhook
        return response()->json(['status' => 'received']);
    }
}
