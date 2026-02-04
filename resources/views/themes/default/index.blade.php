@extends('themes.default.layouts.app')

@section('title', tenant('name') ?? 'Store')

@section('content')
<!-- Hero Section -->
<section class="relative bg-gradient-to-r from-primary to-secondary text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="max-w-2xl">
            <h1 class="text-4xl md:text-5xl font-bold mb-6">Welcome to {{ tenant('name') ?? 'Our Store' }}</h1>
            <p class="text-xl mb-8 text-white/90">Discover amazing products at great prices. Shop with confidence.</p>
            <a href="{{ route('storefront.products') }}" class="inline-block bg-white text-primary px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                Shop Now
            </a>
        </div>
    </div>
</section>

<!-- Categories Section -->
@if($categories->count() > 0)
<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Shop by Category</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
            @foreach($categories as $category)
            <a href="{{ route('storefront.category', $category->slug) }}" class="group">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-lg transition">
                    <div class="aspect-square bg-gray-100 flex items-center justify-center">
                        @if($category->image)
                        <img src="{{ $category->image }}" alt="{{ $category->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        @else
                        <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        @endif
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-semibold text-gray-900 group-hover:text-primary transition">{{ $category->name }}</h3>
                        <p class="text-sm text-gray-500">{{ $category->product_count }} products</p>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Featured Products -->
@if($featuredProducts->count() > 0)
<section class="py-16 bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Featured Products</h2>
            <a href="{{ route('storefront.products') }}" class="text-primary hover:underline font-medium">View All</a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($featuredProducts as $product)
            @include('themes.default.components.product-card', ['product' => $product])
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Features Section -->
<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold mb-2">Fast Delivery</h3>
                <p class="text-gray-600">Quick and reliable shipping across the country</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold mb-2">Secure Payments</h3>
                <p class="text-gray-600">Multiple secure payment options including M-Pesa</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold mb-2">24/7 Support</h3>
                <p class="text-gray-600">Friendly customer support whenever you need help</p>
            </div>
        </div>
    </div>
</section>
@endsection
