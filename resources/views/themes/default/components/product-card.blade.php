<div class="group bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-lg transition">
    <a href="{{ route('storefront.product', $product->slug) }}" class="block">
        <div class="relative aspect-square bg-gray-100">
            @if($product->main_image)
            <img src="{{ $product->main_image }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
            @else
            <div class="w-full h-full flex items-center justify-center">
                <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            @endif

            @if($product->discount_percent)
            <span class="absolute top-2 left-2 bg-red-500 text-white text-xs font-semibold px-2 py-1 rounded">
                -{{ $product->discount_percent }}%
            </span>
            @endif

            @if(!$product->isInStock())
            <span class="absolute top-2 right-2 bg-gray-700 text-white text-xs font-semibold px-2 py-1 rounded">
                Out of Stock
            </span>
            @endif
        </div>
    </a>

    <div class="p-4">
        <a href="{{ route('storefront.product', $product->slug) }}" class="block">
            <h3 class="font-semibold text-gray-900 truncate group-hover:text-primary transition">{{ $product->name }}</h3>
        </a>

        @if($product->category)
        <p class="text-sm text-gray-500 mt-1">{{ $product->category->name }}</p>
        @endif

        <div class="mt-2 flex items-center justify-between">
            <div>
                <span class="text-lg font-bold text-gray-900">{{ tenant('currency') ?? 'KES' }} {{ number_format($product->price, 2) }}</span>
                @if($product->compare_at_price && $product->compare_at_price > $product->price)
                <span class="text-sm text-gray-500 line-through ml-2">{{ tenant('currency') ?? 'KES' }} {{ number_format($product->compare_at_price, 2) }}</span>
                @endif
            </div>
        </div>

        <button class="mt-3 w-full bg-primary text-white py-2 rounded-lg font-medium hover:bg-opacity-90 transition {{ !$product->isInStock() ? 'opacity-50 cursor-not-allowed' : '' }}" {{ !$product->isInStock() ? 'disabled' : '' }}>
            Add to Cart
        </button>
    </div>
</div>
