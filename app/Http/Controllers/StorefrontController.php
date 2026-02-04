<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    /**
     * Display the storefront homepage
     */
    public function index(): View
    {
        $tenant = tenant();

        // Get featured products
        $featuredProducts = \App\Models\Product::where('is_active', true)
            ->where('is_featured', true)
            ->take(8)
            ->get();

        // Get categories
        $categories = \App\Models\Category::where('is_active', true)
            ->whereNull('parent_id')
            ->take(6)
            ->get();

        return view('storefront.index', [
            'tenant' => $tenant,
            'featuredProducts' => $featuredProducts,
            'categories' => $categories,
        ]);
    }

    /**
     * Display category page
     */
    public function category(string $slug): View
    {
        $category = \App\Models\Category::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $products = \App\Models\Product::where('category_id', $category->id)
            ->where('is_active', true)
            ->paginate(12);

        return view('storefront.category', [
            'category' => $category,
            'products' => $products,
        ]);
    }

    /**
     * Display product page
     */
    public function product(string $slug): View
    {
        $product = \App\Models\Product::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $relatedProducts = \App\Models\Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->take(4)
            ->get();

        return view('storefront.product', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }

    /**
     * Display all products
     */
    public function products(Request $request): View
    {
        $query = \App\Models\Product::where('is_active', true);

        // Search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->has('category')) {
            $query->where('category_id', $request->input('category'));
        }

        // Sort
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $products = $query->paginate(12);
        $categories = \App\Models\Category::where('is_active', true)->get();

        return view('storefront.products', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    /**
     * Display static page
     */
    public function page(string $slug): View
    {
        $page = \App\Models\Page::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return view('storefront.page', [
            'page' => $page,
        ]);
    }
}
