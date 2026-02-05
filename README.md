# VumaShops - Bagisto Hosting Platform

A multi-tenant e-commerce hosting platform for African businesses, built on Laravel. VumaShops provisions and manages [Bagisto](https://bagisto.com) stores with automatic SSL, WHMCS integration, and African payment gateways.

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                 VumaShops Central Platform                  │
│               (shops.vumacloud.com/admin)                   │
├─────────────────────────────────────────────────────────────┤
│  - Super Admin Panel (Filament 3)                           │
│  - Tenant Management                                        │
│  - WHMCS Provisioning API                                   │
│  - Bagisto Auto-Provisioning                                │
│  - SSL/Domain Management (Let's Encrypt)                    │
│  - Nginx Configuration Generator                            │
└─────────────────────────────────────────────────────────────┘
                              │
                              │ Provisions per tenant
                              ▼
┌─────────────────────────────────────────────────────────────┐
│              Per-Tenant Bagisto Installation                │
├─────────────────────────────────────────────────────────────┤
│  - Full Bagisto e-commerce platform                         │
│  - GraphQL API (bagisto/headless-ecommerce)                 │
│  - Storefront: Bagisto default / Next.js / Nuxt             │
│  - Dedicated MySQL database                                 │
│  - Custom domain with SSL                                   │
└─────────────────────────────────────────────────────────────┘
```

## Features

### Central Platform
- **Filament 3 Admin Panel** - Manage all tenants
- **WHMCS Integration** - Auto-provision from billing system
- **SSL Automation** - Let's Encrypt certificates
- **Nginx Management** - Per-tenant configurations

### Each Tenant Gets
- **Full Bagisto Installation** - Complete e-commerce platform
- **GraphQL API** - Via bagisto/headless-ecommerce
- **Multiple Storefronts** - Bagisto default, Next.js, or Nuxt
- **Custom Domain** - With automatic SSL
- **Isolated Database** - Complete data separation

## Requirements

- Ubuntu 22.04 / 24.04 LTS
- PHP 8.3+
- MySQL 8.0+ (DigitalOcean Managed recommended)
- Redis 6+ (DigitalOcean Managed recommended)
- Nginx
- Composer 2.x
- Node.js 20+ (for storefronts)
- Certbot (for SSL)

## Installation

### 1. Clone and Install Dependencies

```bash
cd /var/www
git clone https://github.com/vumacloud/vumashops.git
cd vumashops
composer install --no-dev --optimize-autoloader
```

### 2. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database, Redis, and other settings.

### 3. Run Setup

```bash
php artisan vumashops:setup --seed
```

This will:
- Run migrations
- Create default plans (Starter, Business, Enterprise)
- Prompt you to create a super admin

### 4. Configure Nginx

```bash
# Generate central platform config
php artisan tinker --execute="echo app(App\Services\NginxConfigGenerator::class)->generateCentralConfig();"

# Copy output to /etc/nginx/sites-available/shops.vumacloud.com.conf
# Create symlink to sites-enabled
# Test and reload: nginx -t && systemctl reload nginx
```

### 5. Set Up SSL for Central Platform

```bash
certbot certonly --webroot -w /var/www/certbot -d shops.vumacloud.com
```

### 6. Configure Cron Jobs

```bash
# Add to crontab
* * * * * cd /var/www/vumashops && php artisan schedule:run >> /dev/null 2>&1
```

## WHMCS Integration

### API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/whmcs/create` | POST | Create new tenant |
| `/api/whmcs/suspend` | POST | Suspend tenant |
| `/api/whmcs/unsuspend` | POST | Unsuspend tenant |
| `/api/whmcs/terminate` | POST | Delete tenant |
| `/api/whmcs/change-plan` | POST | Change subscription |
| `/api/whmcs/status` | GET | Get tenant status |

### Authentication

Include `X-WHMCS-API-Key` header with your API key.

### Create Tenant Example

```bash
curl -X POST https://shops.vumacloud.com/api/whmcs/create \
  -H "X-WHMCS-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "service_id": 123,
    "client_id": 456,
    "domain": "mystore.com",
    "email": "owner@mystore.com",
    "name": "My Store",
    "plan": "business",
    "country": "KE"
  }'
```

## Manual Tenant Provisioning

```bash
php artisan tenant:provision \
  --name="Demo Shop" \
  --email="demo@example.com" \
  --domain="demoshop.vumacloud.com" \
  --plan="business" \
  --password="securepassword123"
```

## Directory Structure

```
vumashops/
├── app/
│   ├── Console/Commands/     # Artisan commands
│   ├── Filament/Admin/       # Admin panel resources
│   ├── Http/Controllers/Api/ # WHMCS API
│   ├── Models/               # Tenant, Plan, SuperAdmin
│   └── Services/             # BagistoProvisioner, SslManager, NginxConfigGenerator
├── config/
│   ├── tenancy.php           # stancl/tenancy config
│   └── services.php          # WHMCS, server config
├── database/
│   └── migrations/           # Central database only
└── /var/www/tenants/         # Tenant Bagisto installations
    └── {tenant-uuid}/        # Each tenant's Bagisto
```

## African Payment Gateways

VumaShops supports African payment gateways via Bagisto packages:

- **Paystack** - Nigeria, Ghana, Kenya, South Africa
- **Flutterwave** - 30+ African countries
- **M-Pesa Kenya** - Safaricom mobile money
- **MTN MoMo** - MTN mobile money (Uganda, Ghana, etc.)
- **Airtel Money** - Airtel mobile money

Payment gateway credentials are stored per-tenant in the central database.

## SSL Certificate Renewal

SSL certificates auto-renew via cron. Manual renewal:

```bash
php artisan ssl:renew
```

## Environment Variables

Key variables in `.env`:

```env
# Database
DB_CONNECTION=central
DB_HOST=your-db-cluster.db.ondigitalocean.com
DB_PORT=25060
DB_DATABASE=vumashops_central

# Redis
REDIS_HOST=your-redis.db.ondigitalocean.com
REDIS_PORT=25061
REDIS_SCHEME=tls

# WHMCS
WHMCS_API_KEY=your-secure-api-key

# Server
SERVER_IP=164.92.184.13

# Let's Encrypt
LETSENCRYPT_EMAIL=admin@vumacloud.com
```

## License

MIT License
