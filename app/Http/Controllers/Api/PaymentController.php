<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function methods(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Available payment methods - implement based on tenant']);
    }

    public function initialize(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Initialize payment - implement']);
    }

    public function verify($reference): JsonResponse
    {
        return response()->json(['message' => 'Verify payment - implement']);
    }

    // Webhooks
    public function paystackWebhook(Request $request): JsonResponse
    {
        return response()->json(['status' => 'received']);
    }

    public function flutterwaveWebhook(Request $request): JsonResponse
    {
        return response()->json(['status' => 'received']);
    }

    public function mpesaKenyaWebhook(Request $request): JsonResponse
    {
        return response()->json(['status' => 'received']);
    }

    public function mpesaTanzaniaWebhook(Request $request): JsonResponse
    {
        return response()->json(['status' => 'received']);
    }

    public function mtnMomoWebhook(Request $request): JsonResponse
    {
        return response()->json(['status' => 'received']);
    }

    public function airtelMoneyWebhook(Request $request): JsonResponse
    {
        return response()->json(['status' => 'received']);
    }
}
