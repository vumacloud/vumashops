<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CustomerController extends Controller
{
    public function dashboard(): View
    {
        return view('customer.dashboard');
    }

    public function orders(): View
    {
        return view('customer.orders');
    }

    public function orderDetails($order): View
    {
        return view('customer.order-details', compact('order'));
    }

    public function addresses(): View
    {
        return view('customer.addresses');
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        return redirect()->back()->with('success', 'Address added');
    }

    public function updateAddress($address, Request $request): RedirectResponse
    {
        return redirect()->back()->with('success', 'Address updated');
    }

    public function deleteAddress($address): RedirectResponse
    {
        return redirect()->back()->with('success', 'Address deleted');
    }

    public function wishlist(): View
    {
        return view('customer.wishlist');
    }

    public function profile(): View
    {
        return view('customer.profile');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        return redirect()->back()->with('success', 'Profile updated');
    }

    // Auth
    public function showLogin(): View
    {
        return view('auth.customer-login');
    }

    public function login(Request $request): RedirectResponse
    {
        return redirect()->route('customer.dashboard');
    }

    public function showSignup(): View
    {
        return view('auth.customer-signup');
    }

    public function signup(Request $request): RedirectResponse
    {
        return redirect()->route('customer.dashboard');
    }

    public function logout(): RedirectResponse
    {
        return redirect()->route('home');
    }

    public function showForgotPassword(): View
    {
        return view('auth.customer-forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        return redirect()->back()->with('success', 'Reset link sent');
    }

    public function showResetPassword($token): View
    {
        return view('auth.customer-reset-password', compact('token'));
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        return redirect()->route('customer.login')->with('success', 'Password reset');
    }

    // Wishlist
    public function addToWishlist(Request $request): RedirectResponse
    {
        return redirect()->back()->with('success', 'Added to wishlist');
    }

    public function removeFromWishlist($item): RedirectResponse
    {
        return redirect()->back()->with('success', 'Removed from wishlist');
    }
}
