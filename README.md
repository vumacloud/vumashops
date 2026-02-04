# VumaShops by VumaCloud

**VumaShops** is a multi-tenant e-commerce platform built for African businesses by **VumaCloud**. Simple, affordable, and designed for WhatsApp sellers and small businesses ready to go online.

**Platform:** [https://shops.vumacloud.com](https://shops.vumacloud.com)
**Demo Store:** [https://demoshop.vumacloud.com](https://demoshop.vumacloud.com)
**Corporate Site:** [https://vumacloud.com](https://vumacloud.com) (separate)

---

## Features

### Payment Gateways (Africa-focused)
Each vendor configures their own payment credentials in their dashboard:
- **M-Pesa Kenya** - Safaricom Daraja API
- **M-Pesa Tanzania** - Vodacom
- **MTN Mobile Money** - Uganda, Ghana, Rwanda, Zambia
- **Airtel Money** - Uganda, Kenya, Tanzania, Rwanda
- **Paystack** - Cards (Nigeria, Ghana, South Africa, Kenya)
- **Flutterwave** - Cards (10+ African countries)

### Notifications
- **Email via Brevo SMTP** - Order confirmations, shipping updates
- **SMS via Africa's Talking** - Payment receipts, delivery alerts

### Store Themes
Vendors choose their theme from the dashboard:
- Starter, Minimal, WhatsApp Commerce
- Modern, Boutique, TechStore, FreshMart, AfroStyle
- Custom CSS (Pro plans)

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
│              164.92.184.13                           │
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

## Server Deployment

### Prerequisites

```bash
# Update system
apt update && apt upgrade -y

# Install required packages
# Note: No redis-server needed - we use DigitalOcean Managed Redis
apt install -y nginx mysql-client supervisor certbot python3-certbot-nginx \
    php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
    php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath php8.3-redis

# Install Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# Install Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
```

### Step 1: Clone and Setup Application

```bash
# Create web directory
mkdir -p /var/www
cd /var/www

# Clone repository
git clone https://github.com/vumacloud/vumashops.git
cd vumashops

# Create required directories FIRST
mkdir -p storage/framework/{cache/data,sessions,views}
mkdir -p storage/{app/public,logs}
mkdir -p bootstrap/cache

# Set permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Create log files
touch storage/logs/laravel.log
chown www-data:www-data storage/logs/laravel.log

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Setup environment
cp .env.example .env
nano .env  # Edit with your credentials (see Environment Variables section)

# Generate key and run migrations
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan optimize
```

### Step 2: Configure Nginx (WITHOUT SSL first)

Create a temporary nginx config without SSL:

```bash
cat > /etc/nginx/sites-available/vumashops.conf << 'EOF'
# Temporary config - HTTP only (for certbot)
server {
    listen 80;
    server_name shops.vumacloud.com demoshop.vumacloud.com;
    root /var/www/vumashops/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

ln -sf /etc/nginx/sites-available/vumashops.conf /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl restart nginx
```

### Step 3: Obtain SSL Certificates

```bash
# Get certificates for platform domains
certbot --nginx -d shops.vumacloud.com -d demoshop.vumacloud.com

# Certbot will automatically update nginx config with SSL
```

### Step 4: Update Nginx for Production + Vendor Domains

After SSL is configured, update nginx for full production setup:

```bash
# Copy the full production config
cp /var/www/vumashops/deployment/nginx/vumashops.conf /etc/nginx/sites-available/

# Test and reload
nginx -t && systemctl reload nginx
```

### Step 5: Setup Queue Workers

```bash
cat > /etc/supervisor/conf.d/vumashops.conf << 'EOF'
[program:vumashops-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/vumashops/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/vumashops/storage/logs/worker.log
stopwaitsecs=3600
EOF

supervisorctl reread && supervisorctl update && supervisorctl start vumashops-worker:*
```

### Step 6: Setup Cron

```bash
(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/vumashops && php artisan schedule:run >> /dev/null 2>&1") | crontab -
```

### Step 7: Verify Installation

```bash
# Check Laravel
php artisan --version

# Check routes
php artisan route:list --path=whmcs

# Test health endpoint
curl -s http://localhost/api/health | jq
```

---

## Environment Variables

Key variables in `.env`:

```env
APP_NAME=VumaShops
APP_ENV=production
APP_DEBUG=false
APP_URL=https://shops.vumacloud.com

# DigitalOcean Managed MySQL
DB_CONNECTION=mysql
DB_HOST=your-db-cluster-do-user-xxxxx-0.db.ondigitalocean.com
DB_PORT=25060
DB_DATABASE=vumashops
DB_USERNAME=doadmin
DB_PASSWORD=your-password
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt

# DigitalOcean Managed Redis (TLS on port 25061)
REDIS_CLIENT=phpredis
REDIS_HOST=your-redis-cluster-do-user-xxxxx-0.db.ondigitalocean.com
REDIS_PASSWORD=your-redis-password
REDIS_PORT=25061
REDIS_SCHEME=tls

# Use Redis for session, cache, and queue in production
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis

# DigitalOcean Spaces (for vendor product images)
FILESYSTEM_DISK=do_spaces
DO_SPACES_KEY=your-spaces-access-key
DO_SPACES_SECRET=your-spaces-secret-key
DO_SPACES_REGION=fra1
DO_SPACES_BUCKET=vumashops
DO_SPACES_ENDPOINT=https://fra1.digitaloceanspaces.com
DO_SPACES_CDN_ENDPOINT=https://vumashops.fra1.cdn.digitaloceanspaces.com

# Domain config
VUMASHOPS_PLATFORM_DOMAIN=shops.vumacloud.com
VUMASHOPS_DEMO_DOMAIN=demoshop.vumacloud.com
VUMASHOPS_SERVER_IP=164.92.184.13
TENANCY_CENTRAL_DOMAINS=shops.vumacloud.com,demoshop.vumacloud.com

# Cloudflare (for vendor domain automation)
CLOUDFLARE_API_TOKEN=your-cloudflare-api-token
CLOUDFLARE_ZONE_ID=your-zone-id
CLOUDFLARE_SERVER_IP=164.92.184.13

# Brevo SMTP (platform emails)
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=your-brevo-smtp-login
MAIL_PASSWORD=your-brevo-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@vumacloud.com
MAIL_FROM_NAME=VumaShops

# Africa's Talking (platform SMS)
AFRICASTALKING_USERNAME=your-username
AFRICASTALKING_API_KEY=your-api-key
AFRICASTALKING_FROM=VumaShops

# WHMCS Integration
WHMCS_API_KEY=your-secure-random-api-key
WHMCS_URL=https://billing.vumacloud.com
```

**Note:** Payment gateway credentials (Paystack, Flutterwave, M-Pesa, etc.) are configured per-vendor in their dashboard, NOT in .env.

---

## DigitalOcean Infrastructure Setup

### 1. Managed MySQL Database
- Create Database Cluster → MySQL 8
- Get connection details from cluster overview
- Use port `25060` with SSL

### 2. Managed Redis
- Create Database Cluster → Redis 7
- Use port `25061` with TLS (`REDIS_SCHEME=tls`)
- Provides HA, auto-failover, managed backups

### 3. Spaces (Object Storage)
- Create Space: `vumashops`
- Enable CDN for faster asset delivery
- Generate Spaces access keys (API → Spaces Keys)
- All vendor product images stored here (scalable, no disk limits)

---

## WHMCS Integration

WHMCS handles billing and provisioning. Configure these endpoints in your WHMCS module:

| Action | Endpoint | Method |
|--------|----------|--------|
| Create Account | `/api/whmcs/create` | POST |
| Suspend | `/api/whmcs/suspend` | POST |
| Unsuspend | `/api/whmcs/unsuspend` | POST |
| Terminate | `/api/whmcs/terminate` | POST |
| Change Plan | `/api/whmcs/change-plan` | POST |
| Renew | `/api/whmcs/renew` | POST |
| Get Status | `/api/whmcs/status` | POST/GET |

All endpoints require `Authorization: Bearer {WHMCS_API_KEY}` header.

### Create Account Parameters
```json
{
    "serviceid": "12345",
    "clientid": "67890",
    "domain": "mystore.com",
    "email": "owner@mystore.com",
    "password": "optional-or-generated",
    "firstname": "John",
    "lastname": "Doe",
    "plan": "starter"
}
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

## Vendor Dashboard

Each vendor configures in their dashboard (`https://their-domain.com/admin`):

- **Store Settings:** Name, logo, favicon, description
- **Theme:** Select from available themes
- **Payment Gateways:** Their own Paystack/Flutterwave/M-Pesa credentials
- **Notifications:** Email templates, SMS settings
- **Products:** Add/manage products
- **Orders:** View and manage orders

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

## Troubleshooting

### Permission Issues
```bash
chown -R www-data:www-data /var/www/vumashops/storage /var/www/vumashops/bootstrap/cache
chmod -R 775 /var/www/vumashops/storage /var/www/vumashops/bootstrap/cache
```

### Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan optimize
```

### Check Logs
```bash
tail -f /var/www/vumashops/storage/logs/laravel.log
tail -f /var/log/nginx/error.log
```

### Queue Issues
```bash
supervisorctl status
supervisorctl restart vumashops-worker:*
```

---

## License

Proprietary software by VumaCloud. All rights reserved.

---

Built for African businesses by **VumaCloud**.
