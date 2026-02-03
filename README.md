# VumaShops by VumaCloud

**VumaShops** is a multi-tenant e-commerce platform built for African businesses by **VumaCloud**. Simple, affordable, and designed for WhatsApp sellers and small businesses ready to go online.

**Platform:** [https://shops.vumacloud.com](https://shops.vumacloud.com)
**Demo Store:** [https://demoshop.vumacloud.com](https://demoshop.vumacloud.com)
**Corporate Site:** [https://vumacloud.com](https://vumacloud.com) (separate)

---

## Simple Yearly Pricing

All plans include: **Free domain + 3 email addresses + SSL certificate + 7-day free trial**

| Plan | Price | Products | What's Included |
|------|-------|----------|-----------------|
| **Starter** | **$59/year** | 50 | Domain, 3 emails, M-Pesa, WhatsApp button, SMS notifications |
| **Growth** | **$89/year** | 500 | Everything in Starter + all themes, discount codes, API access |
| **Pro** | **$129/year** | Unlimited | Everything in Growth + unlimited orders, multi-currency, priority support |

### What's Included in Every Plan
- Free domain (.com, .co.ke, .co.ug, .co.tz, etc.)
- 3 professional email addresses (you@yourdomain.com)
- Free SSL certificate
- M-Pesa & mobile money payments
- WhatsApp order button
- SMS & email notifications
- Mobile-friendly store
- 7-day free trial

---

## Features

### Payment Gateways (Africa-focused)
- **M-Pesa Kenya** - Safaricom Daraja API
- **M-Pesa Tanzania** - Vodacom
- **MTN Mobile Money** - Uganda, Ghana, Rwanda, Zambia
- **Airtel Money** - Uganda, Kenya, Tanzania, Rwanda
- **Paystack** - Cards (Nigeria, Ghana, South Africa, Kenya)
- **Flutterwave** - Cards (10+ African countries)

### Notifications
- **Email via Brevo** - Order confirmations, shipping updates
- **SMS via Africa's Talking** - Payment receipts, delivery alerts

### Store Themes
- Starter, Minimal, WhatsApp Commerce (all plans)
- Modern, Boutique, TechStore, FreshMart, AfroStyle (Growth+)
- Custom CSS/themes (Pro)

---

## Architecture

```
┌─────────────────────────────────────────────────────┐
│                    Cloudflare                        │
│                 (DNS + CDN + SSL)                    │
└─────────────────────────┬───────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────┐
│           DigitalOcean Droplet                       │
│              164.92.184.13                            │
│         shops.vumacloud.com                          │
│         demoshop.vumacloud.com                       │
│         + all vendor custom domains                  │
└─────────────────────────┬───────────────────────────┘
                          │
          ┌───────────────┴───────────────┐
          │                               │
┌─────────▼─────────┐         ┌──────────▼──────────┐
│  DO Managed DB    │         │      Redis          │
│     (MySQL 8)     │         │                     │
└───────────────────┘         └─────────────────────┘
```

### Domain Structure
| Domain | Purpose |
|--------|---------|
| `shops.vumacloud.com` | Platform, API, Dashboard, Admin Panel |
| `demoshop.vumacloud.com` | Demo store showcase |
| `vumacloud.com` | Corporate website (separate, not this app) |
| `vendor-domain.com` | Vendor stores (custom domains only) |

**Important:** Vendors always use their own custom domains. No vumacloud.com subdomains for vendor stores.

---

## Deployment

### Server: 164.92.184.13

### Quick Setup

```bash
# SSH into server
ssh root@164.92.184.13

# Clone and setup
cd /var/www
git clone https://github.com/vumacloud/vumashops.git
cd vumashops

composer install --optimize-autoloader --no-dev
npm install && npm run build

cp .env.example .env
# Edit .env with your credentials

php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan optimize
```

### Nginx Setup

```bash
cp deployment/nginx/vumashops.conf /etc/nginx/sites-available/
ln -s /etc/nginx/sites-available/vumashops.conf /etc/nginx/sites-enabled/
nginx -t && systemctl restart nginx
```

### SSL Certificates

```bash
# For platform domains
certbot --nginx -d shops.vumacloud.com -d demoshop.vumacloud.com

# For vendor domains - use Cloudflare Origin Certificate
```

### Queue Workers

```bash
# Create supervisor config
cat > /etc/supervisor/conf.d/vumashops.conf << 'EOF'
[program:vumashops-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/vumashops/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/vumashops/storage/logs/worker.log
EOF

supervisorctl reread && supervisorctl update
```

### Cron

```bash
(crontab -l; echo "* * * * * cd /var/www/vumashops && php artisan schedule:run >> /dev/null 2>&1") | crontab -
```

---

## Environment Variables

Key variables in `.env`:

```env
APP_URL=https://shops.vumacloud.com

# DigitalOcean Managed Database
DB_HOST=your-db.db.ondigitalocean.com
DB_PORT=25060
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt

# Domain config
VUMASHOPS_PLATFORM_DOMAIN=shops.vumacloud.com
VUMASHOPS_DEMO_DOMAIN=demoshop.vumacloud.com
VUMASHOPS_SERVER_IP=164.92.184.13
TENANCY_CENTRAL_DOMAINS=shops.vumacloud.com,demoshop.vumacloud.com

# Cloudflare (for vendor domain automation)
CLOUDFLARE_API_TOKEN=xxx
CLOUDFLARE_SERVER_IP=164.92.184.13

# Brevo (email)
BREVO_API_KEY=xxx

# Africa's Talking (SMS)
AFRICASTALKING_USERNAME=xxx
AFRICASTALKING_API_KEY=xxx

# Payment gateways
PAYSTACK_SECRET_KEY=xxx
FLUTTERWAVE_SECRET_KEY=xxx
MPESA_KENYA_CONSUMER_KEY=xxx
MPESA_KENYA_CONSUMER_SECRET=xxx
```

---

## Default Credentials

**Super Admin:** `https://shops.vumacloud.com/super-admin`
- Email: `admin@vumacloud.com`
- Password: `password`

**Demo Store:** `https://demoshop.vumacloud.com/admin`
- Email: `demo@vumacloud.com`
- Password: `demo123`

⚠️ **Change these immediately in production!**

---

## Webhook Endpoints

Configure in payment provider dashboards:

| Provider | Endpoint |
|----------|----------|
| Paystack | `https://shops.vumacloud.com/api/webhooks/paystack` |
| Flutterwave | `https://shops.vumacloud.com/api/webhooks/flutterwave` |
| M-Pesa Kenya | `https://shops.vumacloud.com/api/webhooks/mpesa/kenya` |
| M-Pesa Tanzania | `https://shops.vumacloud.com/api/webhooks/mpesa/tanzania` |
| MTN MoMo | `https://shops.vumacloud.com/api/webhooks/mtn-momo` |
| Airtel Money | `https://shops.vumacloud.com/api/webhooks/airtel-money` |

---

## Supported Countries

| Country | Currency | Payment Methods |
|---------|----------|-----------------|
| Kenya | KES | M-Pesa, Paystack, Flutterwave |
| Tanzania | TZS | M-Pesa TZ, Airtel Money, Flutterwave |
| Uganda | UGX | MTN MoMo, Airtel Money, Flutterwave |
| Nigeria | NGN | Paystack, Flutterwave |
| Ghana | GHS | Paystack, MTN MoMo, Flutterwave |
| South Africa | ZAR | Paystack, Flutterwave |
| Rwanda | RWF | MTN MoMo, Flutterwave |
| Zambia | ZMW | MTN MoMo, Airtel Money |

---

## License

Proprietary software by VumaCloud. All rights reserved.

---

Built for African businesses by **VumaCloud**.
