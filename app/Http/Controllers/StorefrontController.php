<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function home(): View
    {
        return view('storefront.home');
    }

    public function shop(Request $request): View
    {
        return view('storefront.shop');
    }

    public function category($category): View
    {
        return view('storefront.category', compact('category'));
    }

    public function product($product): View
    {
        return view('storefront.product', compact('product'));
    }

    public function search(Request $request): View
    {
        return view('storefront.search');
    }

    public function about(): View
    {
        return view('storefront.about');
    }

    public function contact(): View
    {
        return view('storefront.contact');
    }

    public function sendContact(Request $request)
    {
        return redirect()->back()->with('success', 'Message sent successfully');
    }

    public function terms(): View
    {
        return view('storefront.terms');
    }

    public function privacy(): View
    {
        return view('storefront.privacy');
    }

    public function shipping(): View
    {
        return view('storefront.shipping');
    }

    public function returns(): View
    {
        return view('storefront.returns');
    }
}
