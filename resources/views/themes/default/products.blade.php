@extends('themes.default.layouts.app')

@section('title', 'Products - ' . (tenant('name') ?? 'Store'))

@section('content')
<div class="bg-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="text-sm mb-4">
            <ol class="flex items-center space-x-2">
                <li><a href="{{ route('storefront.index') }}" class="text-gray-500 hover:text-primary">Home</a></li>
                <li class="text-gray-400">/</li>
                <li class="text-gray-900 font-medium">Products</li>
            </ol>
        </nav>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filters Sidebar -->
            <aside class="lg:w-64 flex-shrink-0">
                <div class="bg-white rounded-xl shadow-sm p-6 sticky top-24">
                    <h3 class="font-semibold text-lg mb-4">Filters</h3>

                    <!-- Categories -->
                    <div class="mb-6">
                        <h4 class="font-medium text-gray-900 mb-3">Categories</h4>
                        <div class="space-y-2">
                            <a href="{{ route('storefront.products') }}" class="block text-sm {{ !request('category') ? 'text-primary font-medium' : 'text-gray-600 hover:text-primary' }}">
                                All Products
                            </a>
                            @foreach($categories as $category)
                            <a href="{{ route('storefront.products', ['category' => $category->id]) }}" class="block text-sm {{ request('category') == $category->id ? 'text-primary font-medium' : 'text-gray-600 hover:text-primary' }}">
                                {{ $category->name }} ({{ $category->product_count }})
                            </a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Price Range -->
                    <div class="mb-6">
                        <h4 class="font-medium text-gray-900 mb-3">Price Range</h4>
                        <div class="flex items-center space-x-2">
                            <input type="number" placeholder="Min" class="w-full px-3 py-2 border rounded-lg text-sm">
                            <span class="text-gray-400">-</span>
                            <input type="number" placeholder="Max" class="w-full px-3 py-2 border rounded-lg text-sm">
                        </div>
                    </div>

                    <button class="w-full bg-primary text-white py-2 rounded-lg font-medium hover:bg-opacity-90 transition">
                        Apply Filters
                    </button>
                </div>
            </aside>

            <!-- Products Grid -->
            <div class="flex-grow">
                <!-- Sort & View Options -->
                <div class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <p class="text-gray-600">
                        Showing <span class="font-medium">{{ $products->firstItem() ?? 0 }}</span> - <span class="font-medium">{{ $products->lastItem() ?? 0 }}</span> of <span class="font-medium">{{ $products->total() }}</span> products
                    </p>

                    <div class="flex items-center space-x-4">
                        <label class="text-sm text-gray-600">Sort by:</label>
                        <select onchange="window.location.href = this.value" class="border rounded-lg px-3 py-2 text-sm">
                            <option value="{{ route('storefront.products', array_merge(request()->query(), ['sort' => 'newest'])) }}" {{ request('sort', 'newest') == 'newest' ? 'selected' : '' }}>Newest</option>
                            <option value="{{ route('storefront.products', array_merge(request()->query(), ['sort' => 'price_low'])) }}" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="{{ route('storefront.products', array_merge(request()->query(), ['sort' => 'price_high'])) }}" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                            <option value="{{ route('storefront.products', array_merge(request()->query(), ['sort' => 'name'])) }}" {{ request('sort') == 'name' ? 'selected' : '' }}>Name: A-Z</option>
                        </select>
                    </div>
                </div>

                @if($products->count() > 0)
                <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                    @foreach($products as $product)
                    @include('themes.default.components.product-card', ['product' => $product])
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $products->links() }}
                </div>
                @else
                <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No products found</h3>
                    <p class="text-gray-500">Try adjusting your filters or search criteria.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
