<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Category listing - implement based on tenant context']);
    }

    public function show($category): JsonResponse
    {
        return response()->json(['message' => 'Category details - implement based on tenant context']);
    }

    public function products($category): JsonResponse
    {
        return response()->json(['message' => 'Category products - implement based on tenant context']);
    }
}
