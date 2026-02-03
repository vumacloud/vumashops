# VumaShops by VumaCloud

**VumaShops** is a powerful multi-tenant e-commerce platform built specifically for African businesses by **VumaCloud**. It provides a complete SaaS solution similar to Shopify, enabling merchants to create and manage their own online stores with integrated African payment gateways and notification services.

**Platform:** [https://vumacloud.com](https://vumacloud.com)
**Demo Store:** [https://demoshop.vumacloud.com](https://demoshop.vumacloud.com)

## Features

### Multi-Tenant Architecture
- **Single Database Multi-Tenancy**: Efficient resource utilization with complete tenant isolation
- **Custom Domains ONLY**: All vendor stores run on their own custom domains (e.g., `mystore.co.ke`, `fashionhub.com`)
- **NO Subdomains for Vendors**: vumacloud.com subdomains are reserved for platform use only
- **Automated DNS via Cloudflare**: Automatic domain configuration and SSL provisioning

### Store Themes
Merchants can choose from professionally designed themes:

| Theme | Description | Plans |
|-------|-------------|-------|
| **Starter** | Clean and simple, perfect for getting started | All plans |
| **Minimal** | Minimalist design, products first | All plans |
| **WhatsApp Commerce** | Optimized for WhatsApp-first businesses | All plans |
| **Modern** | Contemporary with bold visuals and animations | Growth+ |
| **Boutique** | Elegant for fashion, jewelry, luxury goods | Growth+ |
| **TechStore** | Perfect for electronics and gadgets | Growth+ |
| **FreshMart** | Designed for grocery and food delivery | Growth+ |
| **AfroStyle** | Vibrant African-inspired design | Growth+ |
| **Marketplace** | Multi-category marketplace design | Professional+ |

### E-Commerce Features
- **Product Types**: Simple, Configurable, Virtual, Downloadable, Grouped, Bundle, Booking
- **Category Management**: Unlimited nested categories
- **Attribute Management**: Custom attributes and attribute families
- **Inventory Management**: Stock tracking with low stock alerts (SMS & Email)
- **Order Management**: Complete lifecycle management
- **Customer Management**: Customer accounts with order history
- **Cart & Checkout**: Seamless shopping experience
- **WhatsApp Integration**: Order via WhatsApp, share products, chat support

### African Payment Gateway Integrations

#### Card Payments
- **Paystack** - Nigeria, Ghana, South Africa, Kenya
- **Flutterwave** - 10+ African countries

#### Mobile Money
- **M-Pesa Kenya** - Safaricom Daraja API (STK Push, C2B, B2C)
- **M-Pesa Tanzania** - Vodacom M-Pesa
- **MTN Mobile Money** - Uganda, Ghana, Cameroon, Rwanda, Zambia
- **Airtel Money** - Uganda, Kenya, Tanzania, Rwanda, 10+ countries

### Notification Services
- **Email via Brevo** (formerly Sendinblue)
  - Transactional emails with templates
  - Order confirmations, shipping updates, password reset
- **SMS via Africa's Talking**
  - Order confirmations & payment receipts
  - Delivery updates & OTP verification
  - Low stock alerts for merchants

### Subscription Plans (7-Day Free Trial)

| Plan | Price | Products | Orders | Staff | Themes |
|------|-------|----------|--------|-------|--------|
| **Starter** | $29/mo | 50 | 100 | 2 | Basic |
| **Growth** | $79/mo | 500 | 1,000 | 5 | All |
| **Professional** | $199/mo | 5,000 | 10,000 | 15 | All + Custom |
| **Enterprise** | $499/mo | Unlimited | Unlimited | Unlimited | All + Dev |

All plans include: Custom domain (required), SSL certificate, M-Pesa integration, Email & SMS notifications

### Admin Panels (Filament 3)
- **Super Admin Panel** (`admin.vumacloud.com`): Platform management
- **Tenant Admin Panel** (`yourdomain.com/admin`): Store management

## Tech Stack

- **Framework**: Laravel 11
- **Admin Panel**: Filament 3
- **Database**: MySQL 8.0+ (DigitalOcean Managed Database supported)
- **Queue**: Redis
- **Cache**: Redis
- **DNS**: Cloudflare (automated)
- **File Storage**: DigitalOcean Spaces / S3

---

## Deployment on DigitalOcean

### Recommended Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Cloudflare                            │
│                    (DNS + CDN + SSL)                         │
└─────────────────────────┬───────────────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────────────┐
│                    App Droplet(s)                            │
│              Ubuntu 22.04 + LEMP Stack                       │
│                   4GB RAM minimum                            │
└─────────────────────────┬───────────────────────────────────┘
                          │
          ┌───────────────┴───────────────┐
          │                               │
┌─────────▼─────────┐         ┌──────────▼──────────┐
│  DO Managed DB    │         │   Redis Droplet     │
│     (MySQL 8)     │         │   (or DO Managed)   │
└───────────────────┘         └─────────────────────┘
```

### Step 1: Create DigitalOcean Managed Database

1. DigitalOcean Dashboard → Databases → Create Database Cluster
2. Engine: **MySQL 8**
3. Region: Select closest to your users (e.g., `lon1` for Africa)
4. Plan: **Basic ($15/mo)** for dev, **General Purpose** for production
5. Name: `vumashops-db`

After creation, note the connection details:
- Host: `your-db-xxxxx.db.ondigitalocean.com`
- Port: `25060`
- Username: `doadmin`
- Password: (from dashboard)

Create the database:
```sql
CREATE DATABASE vumashops CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Step 2: Create App Droplet

1. Image: **Ubuntu 22.04 LTS**
2. Size: **4GB RAM / 2 vCPUs** minimum ($24/mo)
3. Region: Same as database
4. VPC: Same network as database
5. Add SSH key

### Step 3: Install LEMP Stack

```bash
ssh root@your_droplet_ip

# Update system
apt update && apt upgrade -y

# Add PHP repository
add-apt-repository ppa:ondrej/php -y && apt update

# Install PHP 8.2
apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-xml php8.2-mbstring \
    php8.2-curl php8.2-zip php8.2-gd php8.2-intl php8.2-bcmath php8.2-redis

# Install other packages
apt install -y nginx redis-server supervisor

# Install Node.js 18
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs

# Install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

### Step 4: Deploy Application

```bash
mkdir -p /var/www/vumashops && cd /var/www/vumashops
git clone https://github.com/vumacloud/vumashops.git .

composer install --optimize-autoloader --no-dev
npm install && npm run build

cp .env.example .env
```

### Step 5: Configure Environment

Edit `.env` for DigitalOcean Managed Database:

```env
APP_NAME=VumaShops
APP_ENV=production
APP_DEBUG=false
APP_URL=https://vumacloud.com

# DigitalOcean Managed Database
DB_CONNECTION=mysql
DB_HOST=your-db-xxxxx.db.ondigitalocean.com
DB_PORT=25060
DB_DATABASE=vumashops
DB_USERNAME=doadmin
DB_PASSWORD=your-password

# Required for DO Managed Database SSL
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt

# Redis
REDIS_HOST=127.0.0.1
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Cloudflare (for automated DNS)
CLOUDFLARE_API_TOKEN=your-token
CLOUDFLARE_ZONE_ID=your-zone-id
CLOUDFLARE_SERVER_IP=your-droplet-ip

# Brevo (Email)
BREVO_API_KEY=your-key
MAIL_FROM_ADDRESS=noreply@vumacloud.com

# Africa's Talking (SMS)
AFRICASTALKING_USERNAME=your-username
AFRICASTALKING_API_KEY=your-key
AFRICASTALKING_SANDBOX=false

# Payment Gateways
PAYSTACK_SECRET_KEY=sk_live_xxx
FLUTTERWAVE_SECRET_KEY=FLWSECK_xxx
MPESA_KENYA_CONSUMER_KEY=xxx
MPESA_KENYA_CONSUMER_SECRET=xxx
```

### Step 6: Run Migrations

```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan optimize
```

### Step 7: Configure Nginx

```bash
cp deployment/nginx/vumashops.conf /etc/nginx/sites-available/vumashops
ln -s /etc/nginx/sites-available/vumashops /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default
nginx -t && systemctl restart nginx
```

### Step 8: SSL & Domain Setup

For vumacloud.com:
```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d vumacloud.com -d www.vumacloud.com -d admin.vumacloud.com -d demoshop.vumacloud.com
```

For vendor custom domains, use Cloudflare Origin Certificates.

### Step 9: Queue Workers

```bash
nano /etc/supervisor/conf.d/vumashops.conf
```

```ini
[program:vumashops-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/vumashops/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/vumashops/storage/logs/worker.log
```

```bash
supervisorctl reread && supervisorctl update
```

### Step 10: Cron & Permissions

```bash
# Cron
(crontab -l; echo "* * * * * cd /var/www/vumashops && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# Permissions
chown -R www-data:www-data /var/www/vumashops
chmod -R 755 /var/www/vumashops
chmod -R 775 /var/www/vumashops/storage /var/www/vumashops/bootstrap/cache
```

---

## Custom Domain Setup (Vendors)

All vendor stores **MUST** use their own custom domain. No vumacloud.com subdomains for vendors.

### Vendor Flow:
1. Vendor signs up and provides their domain (e.g., `fashionstore.co.ke`)
2. System adds domain to Cloudflare via API
3. Vendor updates nameservers at their registrar
4. SSL is automatically provisioned
5. Store is activated on their custom domain

### DNS Instructions for Vendors:
```
Option 1: Use Cloudflare (Recommended)
- Add domain to Cloudflare
- Point A record to our server IP
- Enable SSL (Full mode)

Option 2: Use Your Registrar
- Add A record: @ → [server IP]
- Add A record: www → [server IP]
- Wait for DNS propagation
```

---

## Default Credentials

**Super Admin**: `https://admin.vumacloud.com`
- Email: `admin@vumacloud.com`
- Password: `password`

**Demo Store**: `https://demoshop.vumacloud.com/admin`
- Email: `demo@vumacloud.com`
- Password: `demo123`

**⚠️ CHANGE THESE IMMEDIATELY IN PRODUCTION!**

---

## Supported Countries

| Country | Currency | Payment Methods |
|---------|----------|-----------------|
| Kenya | KES | M-Pesa, Paystack, Flutterwave |
| Tanzania | TZS | M-Pesa TZ, Flutterwave, Airtel Money |
| Uganda | UGX | MTN MoMo, Airtel Money, Flutterwave |
| Nigeria | NGN | Paystack, Flutterwave |
| Ghana | GHS | Paystack, MTN MoMo, Flutterwave |
| South Africa | ZAR | Paystack, Flutterwave |
| Rwanda | RWF | MTN MoMo, Flutterwave |
| Zambia | ZMW | MTN MoMo, Airtel Money |

---

## Webhook Endpoints

Configure in payment provider dashboards:
- Paystack: `POST https://vumacloud.com/api/webhooks/paystack`
- Flutterwave: `POST https://vumacloud.com/api/webhooks/flutterwave`
- M-Pesa Kenya: `POST https://vumacloud.com/api/webhooks/mpesa/kenya`
- M-Pesa Tanzania: `POST https://vumacloud.com/api/webhooks/mpesa/tanzania`
- MTN MoMo: `POST https://vumacloud.com/api/webhooks/mtn-momo`
- Airtel Money: `POST https://vumacloud.com/api/webhooks/airtel-money`

---

## License

Proprietary software by VumaCloud. All rights reserved.

---

Built with ❤️ for African businesses by **VumaCloud**.
