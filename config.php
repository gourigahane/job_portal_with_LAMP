<?php
// ============================================================
//  CloudJobs Portal - Configuration File
//  File: includes/config.php
// ============================================================

// ---- Environment ----
define('ENV', 'development'); // 'development' | 'production'
define('APP_NAME', 'CloudJobs');
define('APP_URL', 'http://localhost'); // Change to your domain

// ---- Database (MySQL) ----
define('DB_HOST', 'localhost');       // Use RDS endpoint on AWS
define('DB_PORT', '3306');
define('DB_NAME', 'cloudjobs');
define('DB_USER', 'cloudjobs_user');
define('DB_PASS', 'SecurePass@2026!');
define('DB_CHARSET', 'utf8mb4');

// ---- File Paths ----
define('ROOT_PATH',   dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public_html');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');
define('UPLOAD_URL',  APP_URL . '/uploads');

// ---- Upload Limits ----
define('MAX_RESUME_SIZE', 5 * 1024 * 1024);  // 5 MB
define('MAX_AVATAR_SIZE', 2 * 1024 * 1024);  // 2 MB
define('ALLOWED_RESUME_TYPES', ['application/pdf', 'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// ---- Session ----
define('SESSION_LIFETIME', 7200); // 2 hours

// ---- Email (PHP Mailer / AWS SES) ----
define('MAIL_HOST',     'email-smtp.us-east-1.amazonaws.com'); // AWS SES SMTP
define('MAIL_PORT',     587);
define('MAIL_USER',     'your-ses-smtp-user');
define('MAIL_PASS',     'your-ses-smtp-password');
define('MAIL_FROM',     'noreply@cloudjobs.com');
define('MAIL_FROM_NAME', APP_NAME);

// ---- Pagination ----
define('JOBS_PER_PAGE', 12);

// ---- Security ----
define('HASH_COST', 12);       // bcrypt cost
define('CSRF_TOKEN_NAME', 'csrf_token');

// ---- Error Reporting ----
if (ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ---- Timezone ----
date_default_timezone_set('Asia/Kolkata'); // Change to your timezone
