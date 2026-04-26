<?php
// ============================================================
//  CloudJobs - Login Page
//  File: public_html/login.php
// ============================================================
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
if ($auth->isLoggedIn()) { header('Location: /dashboard.php'); exit; }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $result = $auth->login($_POST['email'] ?? '', $_POST['password'] ?? '');
        if ($result['success']) {
            $redirect = $_GET['redirect'] ?? '/dashboard.php';
            header("Location: {$redirect}");
            exit;
        }
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="auth-page">
<div class="auth-container">
    <a href="/" class="logo logo-auth">Cloud<span>Jobs</span></a>
    <div class="auth-card">
        <h2>Welcome back</h2>
        <p class="auth-sub">Sign in to your account</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/login.php">
            <input type="hidden" name="csrf_token" value="<?= $auth->csrfToken() ?>">

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required autocomplete="email"
                       placeholder="you@example.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">
                    Password
                    <a href="/forgot-password.php" class="label-link">Forgot password?</a>
                </label>
                <div class="input-password">
                    <input type="password" id="password" name="password" required
                           autocomplete="current-password" placeholder="Enter your password">
                    <button type="button" onclick="togglePwd(this)" class="pwd-toggle">👁</button>
                </div>
            </div>

            <label class="checkbox-label">
                <input type="checkbox" name="remember"> Remember me for 30 days
            </label>

            <button type="submit" class="btn btn-primary btn-full">Sign In</button>
        </form>

        <div class="auth-divider"><span>or</span></div>
        <p class="auth-switch">
            Don't have an account? <a href="/register.php">Create one free →</a>
        </p>
    </div>
</div>
<script>
function togglePwd(btn) {
    const input = btn.previousElementSibling;
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
