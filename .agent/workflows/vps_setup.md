---
description: Steps to setup the VPS server for the Isotank system
---

# VPS Setup Guide for Isotank System

This guide outlines the commands to run on your **remote VPS server** (after logging in via SSH) to prepare it for hosting the Laravel application.

## 1. Update & Upgrade System
```bash
apt update && apt upgrade -y
```

## 2. Install Essentials (Nginx, Git, Unzip, Supervisor)
```bash
apt install -y nginx git unzip supervisor curl
```

## 3. Install PHP 8.2 and Extensions
```bash
# Add PHP repository
add-apt-repository ppa:ondrej/php -y
apt update

# Install PHP and required extensions for Laravel
apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-curl php8.2-gd php8.2-mbstring php8.2-xml php8.2-zip php8.2-bcmath php8.2-intl
```

## 4. Install MySQL Database Server
```bash
apt install -y mysql-server
```

## 5. Install Composer
```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

## 6. Configure MySQL
Run standard security script (Optional but recommended):
```bash
mysql_secure_installation
```
*(Press Y for valid password plugin if asked, set a strong root password, remove anonymous users, disallow root login remotely, remove test db, reload privilege tables)*

Create Database and User for Isotank:
```bash
mysql -u root -p
```
*(Enter the root password you just set)*

Inside MySQL prompt:
```sql
CREATE DATABASE isotank_db;
CREATE USER 'isotank_user'@'localhost' IDENTIFIED BY 'IsotankStrongPassword123!';
GRANT ALL PRIVILEGES ON isotank_db.* TO 'isotank_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 7. Setup Nginx Virtual Host
Create the config file:
```bash
nano /etc/nginx/sites-available/isotank
```

Paste this content (Right-click to paste in terminal):
```nginx
server {
    listen 80;
    server_name vps.isotank.internal 202.10.44.146; # Replace with your domain later
    root /var/www/isotank-system/public;

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
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```
*Save with Ctrl+O, Enter, then Exit with Ctrl+X.*

Enable the site:
```bash
ln -s /etc/nginx/sites-available/isotank /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default
nginx -t
systemctl restart nginx
```

## 8. Prepare Directory & Git
```bash
mkdir -p /var/www/isotank-system
chown -R www-data:www-data /var/www/isotank-system
chmod -R 775 /var/www/isotank-system
```
