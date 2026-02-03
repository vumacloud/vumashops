<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Customer registration - implement']);
    }

    public function login(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Customer login - implement']);
    }

    public function logout(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Customer logout - implement']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Current user - implement']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Forgot password - implement']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Reset password - implement']);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Verify OTP - implement']);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Customer profile - implement']);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Update profile - implement']);
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Update avatar - implement']);
    }

    public function addresses(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Customer addresses - implement']);
    }

    public function storeAddress(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Store address - implement']);
    }

    public function updateAddress($address, Request $request): JsonResponse
    {
        return response()->json(['message' => 'Update address - implement']);
    }

    public function deleteAddress($address): JsonResponse
    {
        return response()->json(['message' => 'Delete address - implement']);
    }

    public function wishlist(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Customer wishlist - implement']);
    }

    public function addToWishlist(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Add to wishlist - implement']);
    }

    public function removeFromWishlist($item): JsonResponse
    {
        return response()->json(['message' => 'Remove from wishlist - implement']);
    }
}
