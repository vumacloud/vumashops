<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Cart contents - implement based on session/user']);
    }

    public function addItem(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Add to cart - implement']);
    }

    public function updateItem($item, Request $request): JsonResponse
    {
        return response()->json(['message' => 'Update cart item - implement']);
    }

    public function removeItem($item): JsonResponse
    {
        return response()->json(['message' => 'Remove from cart - implement']);
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Apply coupon - implement']);
    }

    public function removeCoupon(): JsonResponse
    {
        return response()->json(['message' => 'Remove coupon - implement']);
    }
}
