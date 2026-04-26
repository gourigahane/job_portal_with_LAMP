<?php
// ============================================================
//  CloudJobs - Register Page
//  File: public_html/register.php
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
        $error = 'Invalid form submission.';
    } elseif ($_POST['password'] !== $_POST['password_confirm']) {
        $error = 'Passwords do not match.';
    } else {
        $result = $auth->register([
            'name'     => $_POST['name'] ?? '',
            'email'    => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'role'     => $_POST['role'] ?? 'jobseeker',
        ]);
        if ($result['success']) {
            $success = 'Account created! Please check your email to verify your account.';
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="auth-page">
<div class="auth-container">
    <a href="/" class="logo logo-auth">Cloud<span>Jobs</span></a>
    <div class="auth-card">
        <h2>Create your account</h2>
        <p class="auth-sub">Start your journey on CloudJobs</p>

        <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="/register.php">
            <input type="hidden" name="csrf_token" value="<?= $auth->csrfToken() ?>">

            <!-- Account Type -->
            <div class="role-toggle">
                <label class="role-option<?= ($_POST['role'] ?? 'jobseeker') === 'jobseeker' ? ' active' : '' ?>">
                    <input type="radio" name="role" value="jobseeker"
                           <?= ($_POST['role'] ?? 'jobseeker') === 'jobseeker' ? 'checked' : '' ?>>
                    👤 I'm a Jobseeker
                </label>
                <label class="role-option<?= ($_POST['role'] ?? '') === 'employer' ? ' active' : '' ?>">
                    <input type="radio" name="role" value="employer"
                           <?= ($_POST['role'] ?? '') === 'employer' ? 'checked' : '' ?>>
                    🏢 I'm an Employer
                </label>
            </div>

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required placeholder="John Doe"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="you@example.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Minimum 8 characters" minlength="8">
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirm Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" required
                           placeholder="Repeat password">
                </div>
            </div>

            <label class="checkbox-label">
                <input type="checkbox" required>
                I agree to the <a href="/terms.php">Terms of Service</a> and <a href="/privacy.php">Privacy Policy</a>
            </label>

            <button type="submit" class="btn btn-primary btn-full">Create Account</button>
        </form>
        <?php endif; ?>

        <p class="auth-switch">
            Already have an account? <a href="/login.php">Sign in →</a>
        </p>
    </div>
</div>
<script>
// Highlight selected role option
document.querySelectorAll('.role-option input').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.role-option').forEach(el => el.classList.remove('active'));
        radio.closest('.role-option').classList.add('active');
    });
});
</script>
</body>
</html>
