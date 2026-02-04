<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', tenant('name') ?? 'Store')</title>
    <meta name="description" content="@yield('meta_description', '')">

    @if(tenant('favicon'))
    <link rel="icon" href="{{ tenant('favicon') }}" type="image/x-icon">
    @endif

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{ $themeColors["primary"] ?? "#3B82F6" }}',
                        secondary: '{{ $themeColors["secondary"] ?? "#6366F1" }}',
                        accent: '{{ $themeColors["accent"] ?? "#F59E0B" }}',
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('styles')
</head>
<body class="bg-gray-50 min-h-screen flex flex-col" x-data="{ cartOpen: false, mobileMenuOpen: false }">
    <!-- Announcement Bar -->
    @if($announcement ?? false)
    <div class="bg-primary text-white text-center py-2 text-sm">
        {{ $announcement->message }}
        @if($announcement->link)
        <a href="{{ $announcement->link }}" class="underline ml-2">{{ $announcement->link_text ?? 'Learn more' }}</a>
        @endif
    </div>
    @endif

    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="{{ route('storefront.index') }}" class="flex items-center">
                        @if(tenant('logo'))
                        <img src="{{ tenant('logo') }}" alt="{{ tenant('name') }}" class="h-10 w-auto">
                        @else
                        <span class="text-2xl font-bold text-primary">{{ tenant('name') ?? 'Store' }}</span>
                        @endif
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <nav class="hidden md:flex space-x-8">
                    <a href="{{ route('storefront.index') }}" class="text-gray-700 hover:text-primary transition">Home</a>
                    <a href="{{ route('storefront.products') }}" class="text-gray-700 hover:text-primary transition">Shop</a>
                    @foreach($categories ?? [] as $category)
                    <a href="{{ route('storefront.category', $category->slug) }}" class="text-gray-700 hover:text-primary transition">{{ $category->name }}</a>
                    @endforeach
                </nav>

                <!-- Right Icons -->
                <div class="flex items-center space-x-4">
                    <!-- Search -->
                    <button class="text-gray-500 hover:text-primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>

                    <!-- Account -->
                    <a href="#" class="text-gray-500 hover:text-primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </a>

                    <!-- Cart -->
                    <button @click="cartOpen = true" class="text-gray-500 hover:text-primary relative">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span class="absolute -top-2 -right-2 bg-primary text-white text-xs rounded-full w-5 h-5 flex items-center justify-center" x-show="$store.cart?.count > 0" x-text="$store.cart?.count ?? 0"></span>
                    </button>

                    <!-- Mobile menu button -->
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-gray-500 hover:text-primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div x-show="mobileMenuOpen" x-transition class="md:hidden bg-white border-t">
            <div class="px-4 py-4 space-y-2">
                <a href="{{ route('storefront.index') }}" class="block py-2 text-gray-700">Home</a>
                <a href="{{ route('storefront.products') }}" class="block py-2 text-gray-700">Shop</a>
                @foreach($categories ?? [] as $category)
                <a href="{{ route('storefront.category', $category->slug) }}" class="block py-2 text-gray-700">{{ $category->name }}</a>
                @endforeach
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- About -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">{{ tenant('name') ?? 'Store' }}</h3>
                    <p class="text-gray-400 text-sm">Quality products at great prices. Shop with confidence.</p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="{{ route('storefront.products') }}" class="hover:text-white">Shop</a></li>
                        <li><a href="{{ route('storefront.page', 'about') }}" class="hover:text-white">About Us</a></li>
                        <li><a href="{{ route('storefront.page', 'contact') }}" class="hover:text-white">Contact</a></li>
                    </ul>
                </div>

                <!-- Customer Service -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Customer Service</h3>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="{{ route('storefront.page', 'shipping') }}" class="hover:text-white">Shipping Info</a></li>
                        <li><a href="{{ route('storefront.page', 'returns') }}" class="hover:text-white">Returns Policy</a></li>
                        <li><a href="{{ route('storefront.page', 'faq') }}" class="hover:text-white">FAQ</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact Us</h3>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li>{{ tenant('email') }}</li>
                        <li>{{ tenant('phone') }}</li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm">&copy; {{ date('Y') }} {{ tenant('name') ?? 'Store' }}. All rights reserved.</p>
                <div class="flex space-x-4 mt-4 md:mt-0">
                    <a href="{{ route('storefront.page', 'privacy') }}" class="text-gray-400 text-sm hover:text-white">Privacy Policy</a>
                    <a href="{{ route('storefront.page', 'terms') }}" class="text-gray-400 text-sm hover:text-white">Terms of Service</a>
                </div>
            </div>

            <p class="text-center text-gray-500 text-xs mt-8">Powered by VumaShops</p>
        </div>
    </footer>

    <!-- Cart Sidebar -->
    <div x-show="cartOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black bg-opacity-50" @click="cartOpen = false"></div>
        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-xl" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
            <div class="flex flex-col h-full">
                <div class="flex justify-between items-center p-4 border-b">
                    <h2 class="text-lg font-semibold">Shopping Cart</h2>
                    <button @click="cartOpen = false" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="flex-grow overflow-y-auto p-4">
                    <p class="text-gray-500 text-center py-8">Your cart is empty</p>
                </div>
                <div class="border-t p-4">
                    <div class="flex justify-between mb-4">
                        <span class="font-semibold">Subtotal</span>
                        <span class="font-semibold">{{ tenant('currency') ?? 'KES' }} 0.00</span>
                    </div>
                    <a href="#" class="block w-full bg-primary text-white text-center py-3 rounded-lg font-semibold hover:bg-opacity-90 transition">
                        Checkout
                    </a>
                </div>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
