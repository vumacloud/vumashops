<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ tenant('name') ?? 'Store' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">{{ tenant('name') ?? 'Store' }}</h1>
                <nav class="flex space-x-4">
                    <a href="{{ route('storefront.products') }}" class="text-gray-600 hover:text-gray-900">Products</a>
                    <a href="#" class="text-gray-600 hover:text-gray-900">Cart</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold mb-4">Welcome to {{ tenant('name') ?? 'Our Store' }}</h2>
            <p class="text-xl mb-8">Discover amazing products at great prices</p>
            <a href="{{ route('storefront.products') }}" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                Shop Now
            </a>
        </div>
    </section>

    <!-- Categories -->
    @if($categories->count() > 0)
    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Shop by Category</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach($categories as $category)
                <a href="{{ route('storefront.category', $category->slug) }}" class="bg-white rounded-lg shadow p-4 text-center hover:shadow-lg transition">
                    @if($category->image)
                    <img src="{{ $category->image }}" alt="{{ $category->name }}" class="w-16 h-16 mx-auto mb-2 object-cover rounded">
                    @else
                    <div class="w-16 h-16 mx-auto mb-2 bg-gray-200 rounded flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    @endif
                    <h3 class="font-medium text-gray-900">{{ $category->name }}</h3>
                </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Featured Products -->
    @if($featuredProducts->count() > 0)
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Featured Products</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($featuredProducts as $product)
                <a href="{{ route('storefront.product', $product->slug) }}" class="bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden">
                    @if($product->main_image)
                    <img src="{{ $product->main_image }}" alt="{{ $product->name }}" class="w-full h-48 object-cover">
                    @else
                    <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    @endif
                    <div class="p-4">
                        <h3 class="font-medium text-gray-900 truncate">{{ $product->name }}</h3>
                        <div class="mt-2 flex items-center">
                            <span class="text-lg font-bold text-gray-900">{{ tenant('currency') ?? 'KES' }} {{ number_format($product->price, 2) }}</span>
                            @if($product->discount_percent)
                            <span class="ml-2 text-sm text-gray-500 line-through">{{ tenant('currency') ?? 'KES' }} {{ number_format($product->compare_at_price, 2) }}</span>
                            <span class="ml-2 text-sm text-red-600">-{{ $product->discount_percent }}%</span>
                            @endif
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-gray-400">&copy; {{ date('Y') }} {{ tenant('name') ?? 'Store' }}. All rights reserved.</p>
                <p class="text-gray-500 text-sm mt-2">Powered by VumaShops</p>
            </div>
        </div>
    </footer>
</body>
</html>
