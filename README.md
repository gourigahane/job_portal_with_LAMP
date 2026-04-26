# CloudJobs - Cloud LAMP Job Portal
### Linux · Apache · MySQL · PHP on AWS

---

## 📁 Project Structure

```
cloudjobs/
├── aws-lamp-setup.sh          ← Run this FIRST on AWS EC2
├── db/
│   └── schema.sql             ← MySQL database schema + seed data
├── includes/
│   ├── config.php             ← App configuration (DB, email, paths)
│   ├── Database.php           ← PDO MySQL wrapper class
│   ├── Auth.php               ← Login, register, sessions, CSRF
│   └── Jobs.php               ← Job CRUD, search, applications
└── public_html/               ← Apache DocumentRoot
    ├── .htaccess              ← URL rewriting, security, caching
    ├── index.php              ← Homepage
    ├── jobs.php               ← Job listing with filters
    ├── job.php                ← Single job detail page
    ├── login.php              ← Sign in
    ├── register.php           ← Sign up (jobseeker / employer)
    ├── post-job.php           ← Employer: post a new job
    ├── dashboard.php          ← User dashboard
    ├── css/style.css          ← Main stylesheet
    ├── js/app.js              ← Frontend JavaScript
    ├── uploads/               ← User uploads (resumes, logos)
    └── api/
        └── save-job.php       ← REST API endpoint
```

---

## 🚀 How to Run on AWS (Step by Step)

### Step 1: Launch EC2 Instance

1. Go to **AWS Console → EC2 → Launch Instance**
2. Choose **Ubuntu Server 22.04 LTS (HVM)** AMI
3. Instance type: **t2.micro** (free tier) or **t3.small** for production
4. Configure **Security Group** — add inbound rules:
   - SSH (port 22) — your IP only
   - HTTP (port 80) — anywhere
   - HTTPS (port 443) — anywhere
5. Create or select a **Key Pair** (.pem file), then launch
6. Note the **Public IPv4** address

### Step 2: Connect via SSH

```bash
# Make key readable
chmod 400 your-key.pem

# Connect
ssh -i your-key.pem ubuntu@YOUR-EC2-PUBLIC-IP
```

### Step 3: Run the LAMP Setup Script

```bash
# Upload the setup script
scp -i your-key.pem aws-lamp-setup.sh ubuntu@YOUR-EC2-IP:~/

# SSH into server
ssh -i your-key.pem ubuntu@YOUR-EC2-IP

# Run the installer
chmod +x aws-lamp-setup.sh
sudo ./aws-lamp-setup.sh
```

This auto-installs: Linux (Ubuntu) → Apache → MySQL → PHP 8.2

### Step 4: Upload Your Source Files

```bash
# From your local machine, upload the project
scp -i your-key.pem -r cloudjobs/ ubuntu@YOUR-EC2-IP:~/

# On the server, move to web root
sudo cp -r ~/cloudjobs /var/www/
sudo chown -R www-data:www-data /var/www/cloudjobs
```

### Step 5: Import Database

```bash
# On the EC2 server
mysql -u cloudjobs_user -p cloudjobs < /var/www/cloudjobs/db/schema.sql
# Password: SecurePass@2026!
```

### Step 6: Configure the App

```bash
# Edit config with your settings
sudo nano /var/www/cloudjobs/includes/config.php
```

Change these values:
```php
define('APP_URL', 'http://YOUR-EC2-IP');  // or your domain
define('DB_PASS', 'SecurePass@2026!');     // your MySQL password
```

### Step 7: Verify It's Running

Open in browser: `http://YOUR-EC2-IP`

---

## 🔒 SSL with Let's Encrypt (Production)

```bash
# Point your domain DNS A record to EC2 IP first, then:
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

---

## 🗄️ Database Management

```bash
# Connect to MySQL
mysql -u root -p

# Useful commands
SHOW DATABASES;
USE cloudjobs;
SHOW TABLES;
SELECT * FROM jobs LIMIT 5;
SELECT * FROM users;

# Backup
mysqldump -u root -p cloudjobs > backup_$(date +%Y%m%d).sql

# Restore
mysql -u root -p cloudjobs < backup_20260101.sql
```

---

## 🔧 Apache Commands

```bash
sudo systemctl start apache2    # Start
sudo systemctl stop apache2     # Stop
sudo systemctl restart apache2  # Restart
sudo systemctl status apache2   # Status

# View error logs
sudo tail -f /var/log/apache2/cloudjobs_error.log
sudo tail -f /var/log/apache2/cloudjobs_access.log

# Test config
sudo apache2ctl configtest
```

---

## 🐘 PHP Configuration

```bash
# Find php.ini
php --ini

# Edit PHP settings
sudo nano /etc/php/8.2/apache2/php.ini

# Key settings to tune:
# upload_max_filesize = 10M
# post_max_size = 12M
# memory_limit = 256M
# max_execution_time = 60
```

---

## 📊 AWS Services You Can Add

| Service       | Purpose                        | How to Use            |
|---------------|--------------------------------|-----------------------|
| RDS (MySQL)   | Managed database               | Change DB_HOST in config.php |
| S3            | Store resumes & logos          | AWS SDK for PHP       |
| SES           | Send emails                    | Update MAIL_* in config.php  |
| CloudFront    | CDN for static assets          | Point to S3/EC2       |
| Route 53      | Domain DNS management          | Point to EC2 IP       |
| ACM           | Free SSL certificates          | Use with CloudFront   |
| ElastiCache   | Redis caching                  | Cache job search results |

---

## 🧪 Default Credentials (Dev Only)

| Role     | Email                    | Password     |
|----------|--------------------------|--------------|
| Admin    | admin@cloudjobs.com      | (set yours)  |
| Employer | hr@techcorp.com          | (set yours)  |
| Jobseeker| jane@example.com         | (set yours)  |

> **Important:** Change all default passwords before going live!

---

## 📋 Features Included

- ✅ User registration (Jobseeker & Employer roles)
- ✅ JWT-safe session auth with CSRF protection
- ✅ Job posting with rich fields (salary, skills, remote, etc.)
- ✅ Full-text search (MySQL FULLTEXT index)
- ✅ Advanced filters (type, level, salary, remote, category)
- ✅ Job applications with status tracking
- ✅ Save/bookmark jobs (AJAX)
- ✅ Company profiles
- ✅ Responsive UI (mobile-friendly)
- ✅ Pagination
- ✅ Apache URL rewriting (.htaccess)
- ✅ Security headers, file upload validation
- ✅ PDO prepared statements (SQL injection safe)
- ✅ bcrypt password hashing
