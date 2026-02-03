<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Order listing - implement based on authenticated customer']);
    }

    public function show($order): JsonResponse
    {
        return response()->json(['message' => 'Order details - implement']);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create order - implement']);
    }

    public function cancel($order): JsonResponse
    {
        return response()->json(['message' => 'Cancel order - implement']);
    }

    public function invoice($order): JsonResponse
    {
        return response()->json(['message' => 'Order invoice - implement']);
    }
}
