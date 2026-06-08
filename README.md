# Warehouse Management System Installation Guide (Nginx on Ubuntu)

This guide provides step-by-step instructions to deploy this Laravel-based Warehouse Management System on an **Ubuntu** server using **Nginx**, **PHP 8.3-FPM**, and **MySQL**.

---

## Prerequisites

Ensure you have a fresh Ubuntu server (22.04 LTS or 24.04 LTS is recommended) with a non-root user that has `sudo` privileges.

---

## Step 1: Update System Packages

First, update the system package index and upgrade existing packages:

```bash
sudo apt update && sudo apt upgrade -y
```

---

## Step 2: Install PHP 8.3 and Extensions

This project requires PHP 8.3. Install PHP 8.3 along with PHP-FPM and the necessary extensions:

```bash
# Add ondrej/php PPA for up-to-date PHP packages if needed
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.3 and required extensions
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-common php8.3-xml php8.3-bcmath php8.3-curl php8.3-mbstring php8.3-zip php8.3-gd php8.3-intl php8.3-sqlite3 php8.3-redis
```

Verify that PHP is installed correctly:
```bash
php -v
```

---

## Step 3: Install Nginx

Install the Nginx web server:

```bash
sudo apt install -y nginx
```

Ensure Nginx is running and starts on boot:
```bash
sudo systemctl enable nginx
sudo systemctl start nginx
```

---

## Step 4: Install MySQL Server

Install MySQL Server to host the `warehouse` database:

```bash
sudo apt install -y mysql-server
```

Start and enable MySQL:
```bash
sudo systemctl enable mysql
sudo systemctl start mysql
```

Secure the MySQL installation:
```bash
sudo mysql_secure_installation
```

Log in to MySQL and create the database and database user:
```bash
sudo mysql
```

In the MySQL prompt, execute the following SQL:
```sql
CREATE DATABASE warehouse CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'warehouse_user'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD';
GRANT ALL PRIVILEGES ON warehouse.* TO 'warehouse_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## Step 5: Install Composer & Node.js

### 1. Install Composer
Download and install Composer globally:

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

Verify Composer:
```bash
composer -v
```

### 2. Install Node.js & npm
To compile the front-end assets via Vite, Node.js (version 20+) is required:

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

Verify Node.js and npm:
```bash
node -v
npm -v
```

---

## Step 6: Deploy and Configure the Application

### 1. Clone the Repository
Clone your repository to the web root directory (e.g., `/var/www/warehouse`):

```bash
sudo git clone https://github.com/your-username/warehouse.git /var/www/warehouse
```

### 2. Configure Permissions
Nginx runs as the `www-data` user on Ubuntu. Assign directory ownership and configure appropriate permissions so Laravel can write to `storage` and `bootstrap/cache`:

```bash
# Change ownership to current user and www-data group
sudo chown -R $USER:www-data /var/www/warehouse

# Change permissions of storage and bootstrap/cache directories
sudo chmod -R 775 /var/www/warehouse/storage
sudo chmod -R 775 /var/www/warehouse/bootstrap/cache
```

### 3. Install Dependencies
Navigate into the project directory and install Composer dependencies:

```bash
cd /var/www/warehouse
composer install --no-dev --optimize-autoloader
```

Install npm packages and compile frontend assets:
```bash
npm install
npm run build
```

### 4. Environment Configuration
Copy the template `.env` file and generate the application encryption key:

```bash
cp .env.example .env
```

Open the `.env` file with your preferred text editor (e.g., `nano`):
```bash
nano .env
```

Modify the environment settings for production:
```ini
APP_NAME="Warehouse Management System"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=warehouse
DB_USERNAME=warehouse_user
DB_PASSWORD=YOUR_SECURE_PASSWORD

QUEUE_CONNECTION=database
```

Save and exit (in Nano, press `Ctrl+O`, `Enter`, then `Ctrl+X`).

Generate the application encryption key:
```bash
php artisan key:generate
```

### 5. Run Migrations & Seeders
Run migrations to set up the database schema and optionally run seeds if needed:

```bash
php artisan migrate --force
# If initializing with demo data:
# php artisan db:seed --class=InitialSeeder --force
```

---

## Step 7: Configure Nginx

Create a new Nginx server block configuration for the application:

```bash
sudo nano /etc/nginx/sites-available/warehouse
```

Paste the following configuration, adjusting `server_name` to your domain or server IP address, and double-checking the PHP-FPM socket path (usually `php8.3-fpm.sock`):

```nginx
server {
    listen 80;
    server_name your-domain.com; # Replace with your domain or IP address
    root /var/www/warehouse/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the newly created site by creating a symbolic link to the `sites-enabled` directory:
```bash
sudo ln -s /etc/nginx/sites-available/warehouse /etc/nginx/sites-enabled/
```

Remove or disable the default Nginx site configuration to avoid conflicts:
```bash
sudo rm /etc/nginx/sites-enabled/default
```

Test the Nginx configuration for syntax errors:
```bash
sudo nginx -t
```

If the test is successful, reload Nginx to apply the changes:
```bash
sudo systemctl reload nginx
```

---

## Step 8: Configure Production Optimizations

To maximize performance in production, run the optimization commands:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

*(Note: If you change `.env` values or route configurations later, you must run `php artisan optimize:clear` or rerun these cache commands to apply them.)*

---

## Step 9: Configure Task Scheduling & Queue Worker

### 1. Cron Job for Task Scheduling
Set up a cron job to run the scheduler every minute:

```bash
crontab -e
```

Add the following line to the bottom of the crontab file (replacing `/var/www/warehouse` with your actual path):

```cron
* * * * * cd /var/www/warehouse && php artisan schedule:run >> /dev/null 2>&1
```

### 2. Supervisor for Queue Worker
To run background queue jobs automatically, install and configure **Supervisor**:

```bash
sudo apt install -y supervisor
```

Create a new Supervisor configuration file:
```bash
sudo nano /etc/supervisor/conf.d/warehouse-worker.conf
```

Paste the following configuration:
```ini
[program:warehouse-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/warehouse/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/warehouse/storage/logs/worker.log
stopwaitsecs=3600
```

Apply the Supervisor configuration:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start warehouse-worker:*
```

---

## Step 10: Secure with SSL (Let's Encrypt) - Recommended

If you mapped a domain to your server, secure the traffic using a free SSL certificate from Let's Encrypt via Certbot:

```bash
sudo apt install -y certbot python3-certbot-nginx
```

Run the Certbot command to automatically configure SSL for Nginx:
```bash
sudo certbot --nginx -d your-domain.com
```

Follow the on-screen prompts to complete the SSL setup. Certbot will automatically handle certificate renewal and redirect HTTP requests to HTTPS.
