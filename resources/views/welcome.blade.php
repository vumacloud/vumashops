<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VumaShops - E-Commerce for African Businesses</title>
    <meta name="description" content="VumaShops is a multi-tenant e-commerce platform built for African businesses. Simple, affordable, and designed for WhatsApp sellers and small businesses ready to go online.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%);
            min-height: 100vh;
            color: #f8fafc;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fbbf24;
        }
        .nav-links a {
            color: #cbd5e1;
            text-decoration: none;
            margin-left: 2rem;
            transition: color 0.2s;
        }
        .nav-links a:hover { color: #fbbf24; }
        .hero {
            text-align: center;
            padding: 6rem 0;
        }
        .hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            background: linear-gradient(to right, #fbbf24, #f59e0b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero p {
            font-size: 1.25rem;
            color: #94a3b8;
            max-width: 600px;
            margin: 0 auto 2rem;
            line-height: 1.8;
        }
        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #fbbf24;
            color: #0f172a;
        }
        .btn-primary:hover {
            background: #f59e0b;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: transparent;
            color: #fbbf24;
            border: 2px solid #fbbf24;
            margin-left: 1rem;
        }
        .btn-secondary:hover {
            background: rgba(251, 191, 36, 0.1);
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            padding: 4rem 0;
        }
        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 2rem;
        }
        .feature-card h3 {
            font-size: 1.25rem;
            color: #fbbf24;
            margin-bottom: 1rem;
        }
        .feature-card p {
            color: #94a3b8;
            line-height: 1.6;
        }
        .payment-gateways {
            text-align: center;
            padding: 4rem 0;
        }
        .payment-gateways h2 {
            font-size: 2rem;
            margin-bottom: 2rem;
        }
        .gateway-list {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .gateway {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.75rem 1.5rem;
            border-radius: 2rem;
            font-size: 0.9rem;
        }
        footer {
            text-align: center;
            padding: 3rem 0;
            color: #64748b;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        footer a { color: #fbbf24; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">VumaShops</div>
            <nav class="nav-links">
                <a href="https://demoshop.vumacloud.com">Demo Store</a>
                <a href="/super-admin">Admin Login</a>
                <a href="https://vumacloud.com">VumaCloud</a>
            </nav>
        </header>

        <section class="hero">
            <h1>E-Commerce for African Businesses</h1>
            <p>
                Launch your online store in minutes. Accept M-Pesa, Paystack, Flutterwave, and more.
                Built specifically for African entrepreneurs and WhatsApp sellers.
            </p>
            <a href="https://demoshop.vumacloud.com" class="btn btn-primary">View Demo Store</a>
            <a href="https://billing.vumacloud.com" class="btn btn-secondary">Start Selling</a>
        </section>

        <section class="features">
            <div class="feature-card">
                <h3>African Payment Gateways</h3>
                <p>M-Pesa Kenya & Tanzania, MTN Mobile Money, Airtel Money, Paystack, and Flutterwave. Accept payments the way your customers want to pay.</p>
            </div>
            <div class="feature-card">
                <h3>Your Own Domain</h3>
                <p>Use your own custom domain for your store. No vumacloud.com subdomains - your brand, your identity.</p>
            </div>
            <div class="feature-card">
                <h3>WhatsApp Integration</h3>
                <p>Perfect for WhatsApp sellers moving online. Share product links directly to your customers and receive order notifications via WhatsApp.</p>
            </div>
            <div class="feature-card">
                <h3>Mobile-First Design</h3>
                <p>Beautiful store themes optimized for mobile devices. Because most of your customers will shop from their phones.</p>
            </div>
            <div class="feature-card">
                <h3>SMS Notifications</h3>
                <p>Automatic SMS notifications via Africa's Talking. Keep your customers informed about orders, shipping, and more.</p>
            </div>
            <div class="feature-card">
                <h3>Multi-Currency Support</h3>
                <p>Sell in KES, TZS, UGX, NGN, GHS, ZAR, and more. Automatic currency display based on customer location.</p>
            </div>
        </section>

        <section class="payment-gateways">
            <h2>Supported Payment Methods</h2>
            <div class="gateway-list">
                <span class="gateway">M-Pesa Kenya</span>
                <span class="gateway">M-Pesa Tanzania</span>
                <span class="gateway">MTN Mobile Money</span>
                <span class="gateway">Airtel Money</span>
                <span class="gateway">Paystack</span>
                <span class="gateway">Flutterwave</span>
            </div>
        </section>

        <footer>
            <p>&copy; {{ date('Y') }} VumaShops by <a href="https://vumacloud.com">VumaCloud</a>. Built for African businesses.</p>
        </footer>
    </div>
</body>
</html>
