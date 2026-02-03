<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Product listing - implement based on tenant context']);
    }

    public function show($product): JsonResponse
    {
        return response()->json(['message' => 'Product details - implement based on tenant context']);
    }

    public function reviews($product): JsonResponse
    {
        return response()->json(['message' => 'Product reviews - implement based on tenant context']);
    }

    public function addReview($product, Request $request): JsonResponse
    {
        return response()->json(['message' => 'Add review - implement based on tenant context']);
    }
}
