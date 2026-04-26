#!/bin/bash
# ============================================================
#  CloudJobs - AWS LAMP Stack Setup Script
#  Run this on a fresh Ubuntu 22.04 EC2 instance
#  Usage: chmod +x aws-lamp-setup.sh && sudo ./aws-lamp-setup.sh
# ============================================================

set -e  # Exit on any error

# ---------- COLORS ----------
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}"
echo "=================================================="
echo "   CloudJobs - AWS LAMP Stack Installer"
echo "   Linux + Apache + MySQL + PHP 8.2"
echo "=================================================="
echo -e "${NC}"

# ---------- 1. SYSTEM UPDATE ----------
echo -e "${YELLOW}[1/8] Updating system packages...${NC}"
apt-get update -y && apt-get upgrade -y
apt-get install -y curl wget unzip git ufw software-properties-common

# ---------- 2. APACHE ----------
echo -e "${YELLOW}[2/8] Installing Apache 2.4...${NC}"
apt-get install -y apache2
systemctl start apache2
systemctl enable apache2

# Enable required Apache modules
a2enmod rewrite
a2enmod headers
a2enmod ssl
a2enmod expires
systemctl restart apache2
echo -e "${GREEN}  Apache installed and running.${NC}"

# ---------- 3. MYSQL ----------
echo -e "${YELLOW}[3/8] Installing MySQL 8.0...${NC}"
apt-get install -y mysql-server

# Secure MySQL non-interactively
MYSQL_ROOT_PASS="CloudJobs@2026!"
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASS}';"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "DELETE FROM mysql.user WHERE User='';"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost','127.0.0.1','::1');"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "DROP DATABASE IF EXISTS test;"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "FLUSH PRIVILEGES;"

# Create application database and user
mysql -u root -p"${MYSQL_ROOT_PASS}" <<SQL
CREATE DATABASE IF NOT EXISTS cloudjobs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'cloudjobs_user'@'localhost' IDENTIFIED BY 'SecurePass@2026!';
GRANT ALL PRIVILEGES ON cloudjobs.* TO 'cloudjobs_user'@'localhost';
FLUSH PRIVILEGES;
SQL
echo -e "${GREEN}  MySQL installed. DB: cloudjobs | User: cloudjobs_user${NC}"

# ---------- 4. PHP 8.2 ----------
echo -e "${YELLOW}[4/8] Installing PHP 8.2 and extensions...${NC}"
add-apt-repository ppa:ondrej/php -y
apt-get update -y
apt-get install -y \
    php8.2 \
    php8.2-cli \
    php8.2-fpm \
    php8.2-mysql \
    php8.2-curl \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-zip \
    php8.2-gd \
    php8.2-json \
    php8.2-intl \
    php8.2-bcmath \
    libapache2-mod-php8.2

echo -e "${GREEN}  PHP 8.2 installed.${NC}"
php -v

# ---------- 5. COMPOSER ----------
echo -e "${YELLOW}[5/8] Installing Composer...${NC}"
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
echo -e "${GREEN}  Composer installed.${NC}"

# ---------- 6. DEPLOY APP ----------
echo -e "${YELLOW}[6/8] Deploying CloudJobs application...${NC}"
APP_DIR="/var/www/cloudjobs"

# Copy app files (assumes you've uploaded them)
if [ -d "/home/ubuntu/cloudjobs" ]; then
    cp -r /home/ubuntu/cloudjobs $APP_DIR
else
    mkdir -p $APP_DIR
    echo "<?php echo 'CloudJobs placeholder - upload your source files here'; ?>" > $APP_DIR/index.php
fi

# Set permissions
chown -R www-data:www-data $APP_DIR
find $APP_DIR -type f -exec chmod 644 {} \;
find $APP_DIR -type d -exec chmod 755 {} \;
chmod -R 775 $APP_DIR/public_html/uploads 2>/dev/null || true

# ---------- 7. APACHE VIRTUAL HOST ----------
echo -e "${YELLOW}[7/8] Configuring Apache Virtual Host...${NC}"

# Get public IP
PUBLIC_IP=$(curl -s http://169.254.169.254/latest/meta-data/public-ipv4 2>/dev/null || echo "your-domain.com")

cat > /etc/apache2/sites-available/cloudjobs.conf <<APACHE
<VirtualHost *:80>
    ServerName ${PUBLIC_IP}
    ServerAlias www.${PUBLIC_IP}
    DocumentRoot /var/www/cloudjobs/public_html

    <Directory /var/www/cloudjobs/public_html>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    # Logs
    ErrorLog \${APACHE_LOG_DIR}/cloudjobs_error.log
    CustomLog \${APACHE_LOG_DIR}/cloudjobs_access.log combined

    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"

    # PHP Configuration
    php_value upload_max_filesize 10M
    php_value post_max_size 12M
    php_value max_execution_time 60
</VirtualHost>
APACHE

a2dissite 000-default.conf
a2ensite cloudjobs.conf
systemctl reload apache2
echo -e "${GREEN}  Apache virtual host configured.${NC}"

# ---------- 8. FIREWALL ----------
echo -e "${YELLOW}[8/8] Configuring UFW Firewall...${NC}"
ufw --force enable
ufw allow OpenSSH
ufw allow 'Apache Full'
ufw status
echo -e "${GREEN}  Firewall configured.${NC}"

# ---------- OPTIONAL: SSL with Let's Encrypt ----------
# Uncomment below if you have a real domain pointed to this server:
# apt-get install -y certbot python3-certbot-apache
# certbot --apache -d yourdomain.com -d www.yourdomain.com

# ---------- SUMMARY ----------
echo -e "${BLUE}"
echo "=================================================="
echo "  INSTALLATION COMPLETE!"
echo "=================================================="
echo -e "${NC}"
echo -e "  ${GREEN}Apache:${NC}  Running on port 80"
echo -e "  ${GREEN}MySQL:${NC}   DB=cloudjobs | User=cloudjobs_user"
echo -e "  ${GREEN}PHP:${NC}     Version 8.2"
echo -e "  ${GREEN}App URL:${NC} http://${PUBLIC_IP}"
echo ""
echo -e "  ${YELLOW}Next Steps:${NC}"
echo "  1. Upload source files to /var/www/cloudjobs/"
echo "  2. Import database: mysql -u root -p cloudjobs < db/schema.sql"
echo "  3. Edit includes/config.php with your DB credentials"
echo "  4. Point your domain DNS to this EC2 IP and run certbot for SSL"
echo ""
echo -e "  ${RED}Save these credentials:${NC}"
echo "  MySQL Root Password : ${MYSQL_ROOT_PASS}"
echo "  App DB User Password: SecurePass@2026!"
echo "=================================================="
