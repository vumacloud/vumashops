# VumaShops - Multi-Tenant E-Commerce Platform for Africa

VumaShops is a powerful multi-tenant e-commerce platform built specifically for African businesses. It provides a complete SaaS solution similar to Shopify, enabling merchants to create and manage their own online stores with integrated African payment gateways and notification services.

## Features

### Multi-Tenant Architecture
- **Single Database Multi-Tenancy**: Efficient resource utilization with tenant isolation
- **Custom Domain Support**: Each merchant can use their own domain
- **Subdomain Support**: Quick setup with `store.vumashops.com` format
- **Tenant Isolation**: Complete data separation between stores

### E-Commerce Features
- **Product Management**: Support for all product types
  - Simple Products
  - Configurable Products (with variants)
  - Virtual Products
  - Downloadable Products
  - Grouped Products
  - Bundle Products
  - Booking Products
- **Category Management**: Unlimited nested categories
- **Attribute Management**: Custom attributes and attribute families
- **Inventory Management**: Stock tracking with low stock alerts
- **Order Management**: Complete order lifecycle management
- **Customer Management**: Customer accounts with order history
- **Cart & Checkout**: Seamless shopping experience
- **Wishlist**: Save products for later
- **Reviews & Ratings**: Customer feedback system
- **Coupons & Discounts**: Promotional tools
- **Tax Management**: Flexible tax configuration
- **Shipping Methods**: Multiple shipping options

### African Payment Gateway Integrations

#### Card Payments
- **Paystack** - Nigeria, Ghana, South Africa, Kenya
- **Flutterwave** - 10+ African countries

#### Mobile Money
- **M-Pesa Kenya** - Safaricom Daraja API
- **M-Pesa Tanzania** - Vodacom M-Pesa
- **MTN Mobile Money** - Uganda, Ghana, Cameroon, Rwanda, Zambia, and more
- **Airtel Money** - Uganda, Kenya, Tanzania, Rwanda, and 10+ more countries

### Notification Services
- **Email via Brevo** (formerly Sendinblue)
  - Transactional emails
  - Template-based emails
  - Contact management
- **SMS via Africa's Talking**
  - Transactional SMS
  - Bulk SMS
  - OTP verification
  - Delivery reports

### Subscription & Billing
- **Flexible Plans**: Starter, Growth, Professional, Enterprise
- **Trial Periods**: Configurable trial days per plan
- **Feature Limits**: Products, categories, orders, staff accounts
- **Auto-Renewal**: Automatic subscription renewals

### Admin Panels (Filament)
- **Super Admin Panel**: Platform management at `/super-admin`
- **Tenant Admin Panel**: Store management at `/admin`

## Tech Stack

- **Framework**: Laravel 11
- **Admin Panel**: Filament 3
- **Database**: MySQL/MariaDB
- **Queue**: Database/Redis
- **Cache**: Redis/Database
- **File Storage**: Local/S3

## Requirements

- PHP 8.2+
- Composer 2.x
- MySQL 8.0+ / MariaDB 10.6+
- Node.js 18+ & NPM
- Redis (optional but recommended)
- Nginx with PHP-FPM

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/your-org/vumashops.git
cd vumashops
```

### 2. Install Dependencies

```bash
composer install --optimize-autoloader --no-dev
npm install && npm run build
```

### 3. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Environment Variables

Edit `.env` file with your settings (see `.env.example` for all options).

### 5. Database Setup

```bash
php artisan migrate --force
php artisan db:seed --force
```

### 6. Storage & Cache

```bash
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## DigitalOcean LEMP Droplet Deployment

### Prerequisites

Create a DigitalOcean Droplet with:
- Ubuntu 22.04 LTS
- Minimum 2GB RAM / 2 vCPUs (recommended: 4GB RAM)
- LEMP stack (Linux, Nginx, MySQL, PHP)

### Step 1: Initial Server Setup

```bash
# SSH into your droplet
ssh root@your_server_ip

# Create a non-root user
adduser vumashops
usermod -aG sudo vumashops

# Switch to the new user
su - vumashops
```

### Step 2: Install Required Packages

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2 and extensions
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-xml php8.2-mbstring \
    php8.2-curl php8.2-zip php8.2-gd php8.2-intl php8.2-bcmath php8.2-redis \
    php8.2-imagick php8.2-soap

# Install MySQL 8
sudo apt install -y mysql-server

# Install Nginx
sudo apt install -y nginx

# Install Redis
sudo apt install -y redis-server

# Install Node.js 18
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Supervisor (for queue workers)
sudo apt install -y supervisor

# Install Certbot for SSL
sudo apt install -y certbot python3-certbot-nginx
```

### Step 3: Configure MySQL

```bash
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE vumashops CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'vumashops'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON vumashops.* TO 'vumashops'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 4: Configure PHP-FPM

```bash
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
```

Update these settings:
```ini
user = vumashops
group = vumashops
listen.owner = vumashops
listen.group = vumashops
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.2-fpm
```

### Step 5: Deploy Application

```bash
# Create web directory
sudo mkdir -p /var/www/vumashops
sudo chown -R vumashops:vumashops /var/www/vumashops

# Clone repository
cd /var/www/vumashops
git clone https://github.com/your-org/vumashops.git .

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Setup environment
cp .env.example .env
nano .env  # Configure your environment variables

# Generate key and run migrations
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force

# Set permissions
sudo chown -R vumashops:vumashops /var/www/vumashops
sudo chmod -R 755 /var/www/vumashops
sudo chmod -R 775 /var/www/vumashops/storage
sudo chmod -R 775 /var/www/vumashops/bootstrap/cache

# Create storage link
php artisan storage:link

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 6: Configure Nginx

```bash
sudo nano /etc/nginx/sites-available/vumashops
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name vumashops.com *.vumashops.com;
    root /var/www/vumashops/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    # Handle main domain and subdomains
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript application/rss+xml application/atom+xml image/svg+xml;

    # Client body size for file uploads
    client_max_body_size 100M;
}
```

Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/vumashops /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx
```

### Step 7: SSL Certificate (Let's Encrypt)

```bash
sudo certbot --nginx -d vumashops.com -d www.vumashops.com -d *.vumashops.com
```

For wildcard certificates, you'll need DNS verification:
```bash
sudo certbot certonly --manual --preferred-challenges=dns -d vumashops.com -d *.vumashops.com
```

### Step 8: Configure Queue Worker (Supervisor)

```bash
sudo nano /etc/supervisor/conf.d/vumashops-worker.conf
```

```ini
[program:vumashops-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/vumashops/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=vumashops
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/vumashops/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start vumashops-worker:*
```

### Step 9: Configure Cron for Scheduler

```bash
crontab -e
```

Add:
```
* * * * * cd /var/www/vumashops && php artisan schedule:run >> /dev/null 2>&1
```

### Step 10: Firewall Configuration

```bash
sudo ufw allow 'Nginx Full'
sudo ufw allow OpenSSH
sudo ufw enable
```

### Step 11: Configure Redis

```bash
sudo nano /etc/redis/redis.conf
```

Set:
```
supervised systemd
maxmemory 256mb
maxmemory-policy allkeys-lru
```

```bash
sudo systemctl restart redis
```

Update `.env`:
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

---

## Production Environment Variables

```env
APP_NAME=VumaShops
APP_ENV=production
APP_DEBUG=false
APP_URL=https://vumashops.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vumashops
DB_USERNAME=vumashops
DB_PASSWORD=your_secure_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Brevo (Email)
MAIL_MAILER=brevo
BREVO_API_KEY=your-brevo-api-key
MAIL_FROM_ADDRESS=noreply@vumashops.com
MAIL_FROM_NAME="VumaShops"

# Africa's Talking (SMS)
AFRICASTALKING_USERNAME=your-username
AFRICASTALKING_API_KEY=your-api-key
AFRICASTALKING_SANDBOX=false

# Paystack
PAYSTACK_PUBLIC_KEY=pk_live_xxx
PAYSTACK_SECRET_KEY=sk_live_xxx
PAYSTACK_ENVIRONMENT=live

# Flutterwave
FLUTTERWAVE_PUBLIC_KEY=FLWPUBK_xxx
FLUTTERWAVE_SECRET_KEY=FLWSECK_xxx
FLUTTERWAVE_ENVIRONMENT=live

# M-Pesa Kenya
MPESA_KENYA_CONSUMER_KEY=xxx
MPESA_KENYA_CONSUMER_SECRET=xxx
MPESA_KENYA_SHORTCODE=xxx
MPESA_KENYA_PASSKEY=xxx
MPESA_KENYA_ENVIRONMENT=live

# MTN MoMo
MTN_MOMO_ENVIRONMENT=live
MTN_MOMO_COLLECTION_SUBSCRIPTION_KEY=xxx

# Airtel Money
AIRTEL_MONEY_CLIENT_ID=xxx
AIRTEL_MONEY_CLIENT_SECRET=xxx
AIRTEL_MONEY_ENVIRONMENT=live
```

---

## Maintenance Commands

```bash
# Clear all caches
php artisan optimize:clear

# Rebuild caches
php artisan optimize

# Run migrations
php artisan migrate --force

# Restart queue workers
sudo supervisorctl restart vumashops-worker:*

# View logs
tail -f /var/www/vumashops/storage/logs/laravel.log

# Check queue status
php artisan queue:monitor
```

## Backup Strategy

```bash
# Database backup
mysqldump -u vumashops -p vumashops > backup_$(date +%Y%m%d).sql

# Files backup
tar -czvf storage_backup_$(date +%Y%m%d).tar.gz /var/www/vumashops/storage/app
```

## Scaling Considerations

For high traffic:
1. Use DigitalOcean Managed Database
2. Use DigitalOcean Spaces for file storage
3. Add load balancer with multiple droplets
4. Use Redis for sessions and cache
5. Configure CDN for static assets

---

## Default Credentials

**Super Admin Panel**: `/super-admin`
- Email: `admin@vumashops.com`
- Password: `password`

**Change these immediately in production!**

## Supported Countries & Currencies

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

## License

Proprietary software. All rights reserved.

---

Built with ❤️ for African businesses.
