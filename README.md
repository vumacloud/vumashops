# VumaShops - Bagisto Hosting Platform

A multi-tenant e-commerce hosting platform for African businesses. VumaShops provisions and manages [Bagisto](https://bagisto.com) stores with automatic SSL, WHMCS integration, and African payment gateways.

## Architecture Overview

```
                    ┌─────────────────────────────────────────────────────────────┐
                    │                 VumaShops Central Platform                  │
                    │               (shops.vumacloud.com/admin)                   │
                    ├─────────────────────────────────────────────────────────────┤
                    │  - Filament 3 Super Admin Panel                             │
                    │  - Tenant Management (CRUD)                                 │
                    │  - WHMCS Provisioning API                                   │
                    │  - BagistoProvisioner Service                               │
                    │  - SSL/Domain Management (Let's Encrypt)                    │
                    │  - Nginx Configuration Generator                            │
                    │  - Central MySQL Database (tenant metadata)                 │
                    └─────────────────────────────────────────────────────────────┘
                                                  │
                                                  │ Provisions per tenant
                                                  ▼
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│                           Per-Tenant Bagisto Installation                                 │
├──────────────────────────────────────────────────────────────────────────────────────────┤
│  Location: /var/www/tenants/{tenant-uuid}/                                               │
│                                                                                          │
│  Components:                                                                             │
│  - Full Bagisto e-commerce platform (2.3.0)                                              │
│  - GraphQL API via bagisto/headless-ecommerce                                            │
│  - Storefront: Bagisto default / Next.js (bagisto/nextjs-commerce) / Nuxt               │
│  - Dedicated MySQL database (bagisto_{tenant_id})                                        │
│  - Custom domain with Let's Encrypt SSL                                                  │
│  - Independent admin panel at /admin                                                     │
└──────────────────────────────────────────────────────────────────────────────────────────┘
```

## Features

### Central Platform
- **Filament 3 Admin Panel** - Manage all tenants, plans, and subscriptions
- **WHMCS Integration** - Automated provisioning from billing system
- **SSL Automation** - Let's Encrypt certificates with auto-renewal
- **Nginx Management** - Per-tenant virtual host configurations
- **Multi-database Architecture** - Uses stancl/tenancy for domain routing

### Each Tenant Gets
- **Full Bagisto Installation** - Complete open-source e-commerce platform
- **GraphQL API** - Via bagisto/headless-ecommerce for headless storefronts
- **Multiple Storefronts** - Bagisto default, Next.js, or Nuxt
- **Custom Domain** - With automatic Let's Encrypt SSL
- **Isolated Database** - Complete data separation
- **African Payment Gateways** - M-Pesa, Paystack, Flutterwave, MTN MoMo

---

## Server Requirements

| Component | Version | Notes |
|-----------|---------|-------|
| Ubuntu | 22.04 or 24.04 LTS | Recommended |
| PHP | 8.3+ | With required extensions |
| MySQL | 8.0+ | DigitalOcean Managed recommended |
| Redis | 6+ | DigitalOcean Managed recommended |
| Nginx | Latest | For reverse proxy |
| Composer | 2.x | PHP package manager |
| Node.js | 20+ | For Next.js/Nuxt storefronts |
| Certbot | Latest | For Let's Encrypt |
| Git | Latest | For deployments |

### Required PHP Extensions
```
bcmath, ctype, curl, dom, fileinfo, gd, iconv, intl, json, mbstring,
openssl, pdo, pdo_mysql, redis, tokenizer, xml, zip
```

---

## Step-by-Step Installation

### Step 1: Server Preparation

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.3 and extensions
sudo add-apt-repository ppa:ondrej/php -y
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common \
    php8.3-mysql php8.3-zip php8.3-gd php8.3-mbstring php8.3-curl \
    php8.3-xml php8.3-bcmath php8.3-intl php8.3-redis php8.3-soap

# Install Nginx
sudo apt install -y nginx

# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js 20 (for storefronts)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Create directories
sudo mkdir -p /var/www/vumashops
sudo mkdir -p /var/www/tenants
sudo mkdir -p /var/www/certbot/.well-known/acme-challenge
sudo chown -R www-data:www-data /var/www
```

### Step 2: Clone Repository

```bash
cd /var/www
sudo git clone https://github.com/vumacloud/vumashops.git
cd vumashops
sudo chown -R www-data:www-data .
```

### Step 3: Install PHP Dependencies

```bash
sudo -u www-data composer install --no-dev --optimize-autoloader
```

### Step 4: Configure Environment

```bash
# Copy environment file
sudo -u www-data cp .env.example .env

# Generate application key
sudo -u www-data php artisan key:generate

# Edit environment file with your settings
sudo nano .env
```

**Required Environment Variables:**

```env
# Application
APP_NAME=VumaShops
APP_ENV=production
APP_DEBUG=false
APP_URL=https://shops.vumacloud.com

# Database (DigitalOcean Managed MySQL)
DB_CONNECTION=central
DB_HOST=your-db-cluster.db.ondigitalocean.com
DB_PORT=25060
DB_DATABASE=vumashops_central
DB_USERNAME=doadmin
DB_PASSWORD=your-secure-password
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt

# Redis (DigitalOcean Managed Redis)
REDIS_HOST=your-redis.db.ondigitalocean.com
REDIS_PASSWORD=your-redis-password
REDIS_PORT=25061
REDIS_SCHEME=tls

# Session/Cache
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis

# WHMCS Integration
WHMCS_API_KEY=generate-a-secure-random-key

# Server Configuration
SERVER_IP=your-server-public-ip
LETSENCRYPT_EMAIL=admin@yourdomain.com
```

### Step 5: Create Database

Connect to your MySQL server and create the central database:

```sql
CREATE DATABASE vumashops_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Step 6: Run Platform Setup

```bash
# Run migrations and seed plans
sudo -u www-data php artisan vumashops:setup --seed
```

This command will:
1. Run all database migrations
2. Create default plans (Starter, Business, Enterprise)
3. Prompt you to create a super admin account

**Save your super admin credentials securely!**

### Step 7: Configure Nginx for Central Platform

Create the Nginx configuration:

```bash
sudo nano /etc/nginx/sites-available/shops.vumacloud.com.conf
```

Paste this configuration:

```nginx
# VumaShops Central Platform
server {
    listen 80;
    listen [::]:80;
    server_name shops.vumacloud.com;

    root /var/www/vumashops/public;
    index index.php index.html;

    # Let's Encrypt challenge
    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }

    # Laravel/Filament
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    client_max_body_size 50M;
}
```

Enable and test:

```bash
sudo ln -s /etc/nginx/sites-available/shops.vumacloud.com.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Step 8: Point DNS to Server

Add an A record for your domain pointing to your server's IP:

```
shops.vumacloud.com  A  164.92.184.13
```

Wait for DNS propagation (can take up to 48 hours, usually much faster).

### Step 9: Issue SSL Certificate for Central Platform

```bash
sudo certbot certonly --webroot -w /var/www/certbot -d shops.vumacloud.com
```

Update Nginx config to use HTTPS:

```bash
sudo nano /etc/nginx/sites-available/shops.vumacloud.com.conf
```

Replace with:

```nginx
# HTTP -> HTTPS redirect
server {
    listen 80;
    listen [::]:80;
    server_name shops.vumacloud.com;

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }

    location / {
        return 301 https://$host$request_uri;
    }
}

# HTTPS server
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name shops.vumacloud.com;

    root /var/www/vumashops/public;
    index index.php index.html;

    # SSL certificates
    ssl_certificate /etc/letsencrypt/live/shops.vumacloud.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/shops.vumacloud.com/privkey.pem;

    # SSL settings
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 1d;

    # Security headers
    add_header Strict-Transport-Security "max-age=63072000" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    # Laravel/Filament
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    client_max_body_size 50M;
}
```

Reload Nginx:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

### Step 10: Set Up Cron Jobs

```bash
sudo crontab -e
```

Add these lines:

```cron
# Laravel scheduler (runs every minute)
* * * * * cd /var/www/vumashops && php artisan schedule:run >> /dev/null 2>&1

# SSL certificate renewal check (daily at 3am)
0 3 * * * cd /var/www/vumashops && php artisan ssl:renew >> /var/log/vumashops-ssl.log 2>&1

# Queue worker restart (weekly)
0 0 * * 0 cd /var/www/vumashops && php artisan queue:restart >> /dev/null 2>&1
```

### Step 11: Set Up Queue Worker (Supervisor)

Install Supervisor:

```bash
sudo apt install -y supervisor
```

Create worker configuration:

```bash
sudo nano /etc/supervisor/conf.d/vumashops-worker.conf
```

```ini
[program:vumashops-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/vumashops/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/vumashops-worker.log
stopwaitsecs=3600
```

Start the workers:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start vumashops-worker:*
```

### Step 12: Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/vumashops
sudo chown -R www-data:www-data /var/www/tenants
sudo chmod -R 775 /var/www/vumashops/storage
sudo chmod -R 775 /var/www/vumashops/bootstrap/cache
```

### Step 13: Optimize for Production

```bash
cd /var/www/vumashops
sudo -u www-data php artisan optimize
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan config:cache
```

---

## Accessing the Admin Panel

After installation, access the Filament admin panel at:

```
https://shops.vumacloud.com/admin
```

Login with the super admin credentials you created during setup.

---

## Manual Tenant Provisioning

To manually provision a tenant (for testing):

```bash
cd /var/www/vumashops

php artisan tenant:provision \
    --name="Demo Shop" \
    --email="demo@example.com" \
    --domain="demoshop.vumacloud.com" \
    --plan="business" \
    --password="SecurePassword123!"
```

This will:
1. Create the tenant record in central database
2. Generate Nginx configuration
3. Install Bagisto with GraphQL API
4. Attempt SSL certificate issuance (if DNS is ready)

---

## WHMCS Integration

### API Endpoints

All endpoints require `X-WHMCS-API-Key` header with the value from `WHMCS_API_KEY` env.

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/whmcs/create` | POST | Create new tenant with Bagisto |
| `/api/whmcs/suspend` | POST | Suspend tenant (maintenance mode) |
| `/api/whmcs/unsuspend` | POST | Unsuspend tenant |
| `/api/whmcs/terminate` | POST | Delete tenant completely |
| `/api/whmcs/change-plan` | POST | Change subscription plan |
| `/api/whmcs/status` | GET | Get tenant status and URLs |

### Create Tenant Request

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
    "country": "KE",
    "storefront_type": "bagisto_default"
  }'
```

### Response

```json
{
  "result": "success",
  "message": "Store created successfully",
  "tenant_id": "550e8400-e29b-41d4-a716-446655440000",
  "domain": "mystore.com",
  "admin_url": "https://mystore.com/admin",
  "api_url": "https://mystore.com/graphql",
  "storefront_url": "https://mystore.com"
}
```

### Suspend Tenant

```bash
curl -X POST https://shops.vumacloud.com/api/whmcs/suspend \
  -H "X-WHMCS-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{"service_id": 123, "reason": "Payment overdue"}'
```

### Terminate Tenant

```bash
curl -X POST https://shops.vumacloud.com/api/whmcs/terminate \
  -H "X-WHMCS-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{"service_id": 123}'
```

---

## Directory Structure

```
/var/www/vumashops/                 # Central platform
├── app/
│   ├── Console/Commands/           # Artisan commands
│   │   ├── SetupPlatform.php       # php artisan vumashops:setup
│   │   ├── ProvisionTenant.php     # php artisan tenant:provision
│   │   └── RenewSslCertificates.php # php artisan ssl:renew
│   ├── Filament/Admin/             # Filament admin panel
│   │   └── Resources/
│   │       ├── TenantResource.php  # Tenant CRUD
│   │       └── PlanResource.php    # Plan CRUD
│   ├── Http/Controllers/Api/
│   │   └── WhmcsProvisioningController.php
│   ├── Models/
│   │   ├── Tenant.php              # Tenant model
│   │   ├── Plan.php                # Subscription plans
│   │   └── SuperAdmin.php          # Admin users
│   └── Services/
│       ├── BagistoProvisioner.php  # Installs Bagisto
│       ├── SslManager.php          # Let's Encrypt automation
│       └── NginxConfigGenerator.php # Nginx configs
├── config/
│   ├── tenancy.php                 # stancl/tenancy config
│   ├── database.php                # Central & tenant connections
│   └── services.php                # WHMCS, server, SSL config
├── database/
│   └── migrations/                 # Central database only
└── routes/
    └── api.php                     # WHMCS API routes

/var/www/tenants/                   # Tenant installations
└── {tenant-uuid}/                  # Each tenant's Bagisto
    ├── app/
    ├── config/
    ├── public/                     # Nginx document root
    ├── storage/
    └── .env                        # Tenant-specific config
```

---

## Artisan Commands

| Command | Description |
|---------|-------------|
| `php artisan vumashops:setup --seed` | Initial platform setup |
| `php artisan tenant:provision` | Manually provision a tenant |
| `php artisan ssl:renew` | Renew expiring SSL certificates |
| `php artisan optimize` | Cache config/routes/views |
| `php artisan queue:work` | Process background jobs |

---

## Troubleshooting

### Bagisto Installation Fails

Check the Laravel log:
```bash
tail -f /var/www/vumashops/storage/logs/laravel.log
```

Common issues:
- **Composer timeout**: Increase timeout in BagistoProvisioner.php
- **Memory limit**: Edit `/etc/php/8.3/cli/php.ini` and set `memory_limit = 512M`
- **Disk space**: Ensure at least 2GB free per tenant

### SSL Certificate Fails

1. Verify DNS is pointing to your server:
```bash
dig +short mystore.com
```

2. Check certbot logs:
```bash
sudo tail -f /var/log/letsencrypt/letsencrypt.log
```

3. Manually issue certificate:
```bash
sudo certbot certonly --webroot -w /var/www/certbot -d mystore.com
```

### Nginx Config Issues

Test configuration:
```bash
sudo nginx -t
```

Check error log:
```bash
sudo tail -f /var/log/nginx/error.log
```

### Queue Jobs Not Processing

Restart supervisor:
```bash
sudo supervisorctl restart vumashops-worker:*
```

Check worker logs:
```bash
tail -f /var/log/vumashops-worker.log
```

---

## African Payment Gateways

Each Bagisto tenant can configure these payment gateways via their admin panel:

| Gateway | Countries | Package |
|---------|-----------|---------|
| Paystack | Nigeria, Ghana, Kenya, South Africa | bagisto/paystack |
| Flutterwave | 30+ African countries | bagisto/flutterwave |
| M-Pesa | Kenya, Tanzania | Custom integration |
| MTN MoMo | Uganda, Ghana, Cameroon | Custom integration |
| Airtel Money | Multiple African countries | Custom integration |

---

## Security Recommendations

1. **Firewall**: Only allow ports 22, 80, 443
2. **SSH**: Use key-based authentication, disable password login
3. **WHMCS API Key**: Generate a strong random key (32+ characters)
4. **Database**: Use managed database with SSL
5. **Backups**: Enable automatic database backups
6. **Updates**: Keep system and packages updated

---

## Support

For issues, please open a GitHub issue or contact support@vumacloud.com.

---

## License

MIT License - See LICENSE file for details.
