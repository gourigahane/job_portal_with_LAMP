<?php
// ============================================================
//  CloudJobs - Post a Job (Employer Only)
//  File: public_html/post-job.php
// ============================================================
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/Jobs.php';

$auth = new Auth();
$auth->requireLogin('/login.php?redirect=/post-job.php');
$auth->requireRole('employer');

$db      = Database::getInstance();
$jobsObj = new Jobs();
$user    = $auth->currentUser();

// Get employer's company
$company = $db->fetchOne('SELECT * FROM companies WHERE user_id = ?', [$user['id']]);
if (!$company) {
    header('Location: /company/create.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $skills = array_map('trim', explode(',', $_POST['skills'] ?? ''));
        $skills = array_filter($skills);

        $jobId = $jobsObj->create([
            'company_id'      => $company['id'],
            'title'           => $_POST['title'] ?? '',
            'description'     => $_POST['description'] ?? '',
            'requirements'    => $_POST['requirements'] ?? '',
            'benefits'        => $_POST['benefits'] ?? '',
            'type'            => $_POST['type'] ?? 'full-time',
            'level'           => $_POST['level'] ?? 'mid',
            'location'        => $_POST['location'] ?? '',
            'is_remote'       => !empty($_POST['is_remote']) ? 1 : 0,
            'salary_min'      => !empty($_POST['salary_min']) ? (float)$_POST['salary_min'] : null,
            'salary_max'      => !empty($_POST['salary_max']) ? (float)$_POST['salary_max'] : null,
            'salary_currency' => $_POST['salary_currency'] ?? 'USD',
            'skills'          => array_values($skills),
            'category'        => $_POST['category'] ?? '',
            'expires_at'      => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null,
        ]);

        if ($jobId) {
            header("Location: /dashboard.php?posted=1");
            exit;
        }
        $error = 'Failed to post job. Please try again.';
    }
}

$categories = $db->fetchAll('SELECT * FROM categories ORDER BY name');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Job – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php include 'partials/navbar.php'; ?>

<div class="container form-page">
    <div class="form-card">
        <h2>Post a New Job</h2>
        <p class="form-sub">Fill in the details below. Good job descriptions get 3× more applicants.</p>

        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST" action="/post-job.php">
            <input type="hidden" name="csrf_token" value="<?= $auth->csrfToken() ?>">

            <div class="form-section">
                <h3>Basic Information</h3>
                <div class="form-group">
                    <label for="title">Job Title *</label>
                    <input type="text" id="title" name="title" required maxlength="200"
                           placeholder="e.g. Senior PHP Developer">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Job Type *</label>
                        <select name="type" required>
                            <option value="full-time">Full Time</option>
                            <option value="part-time">Part Time</option>
                            <option value="contract">Contract</option>
                            <option value="internship">Internship</option>
                            <option value="freelance">Freelance</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Experience Level *</label>
                        <select name="level" required>
                            <option value="junior">Junior</option>
                            <option value="mid" selected>Mid-level</option>
                            <option value="senior">Senior</option>
                            <option value="lead">Lead</option>
                            <option value="executive">Executive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="">Select category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['slug']) ?>">
                                    <?= htmlspecialchars($cat['icon'] . ' ' . $cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Location</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" placeholder="e.g. Pune, India or New York, USA">
                    </div>
                    <div class="form-group center-label">
                        <label class="checkbox-label large-check">
                            <input type="checkbox" name="is_remote" value="1">
                            This is a Remote Job
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Salary (Optional)</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Currency</label>
                        <select name="salary_currency">
                            <option value="USD">USD ($)</option>
                            <option value="INR">INR (₹)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Min Salary</label>
                        <input type="number" name="salary_min" placeholder="e.g. 80000" min="0">
                    </div>
                    <div class="form-group">
                        <label>Max Salary</label>
                        <input type="number" name="salary_max" placeholder="e.g. 120000" min="0">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Job Details</h3>
                <div class="form-group">
                    <label for="description">Job Description *</label>
                    <textarea id="description" name="description" required rows="8"
                              placeholder="Describe the role, responsibilities, and day-to-day work..."></textarea>
                </div>
                <div class="form-group">
                    <label for="requirements">Requirements</label>
                    <textarea id="requirements" name="requirements" rows="5"
                              placeholder="Skills, qualifications, and experience required..."></textarea>
                </div>
                <div class="form-group">
                    <label for="benefits">Benefits &amp; Perks</label>
                    <textarea id="benefits" name="benefits" rows="4"
                              placeholder="Health insurance, stock options, flexible hours..."></textarea>
                </div>
                <div class="form-group">
                    <label for="skills">Required Skills <small>(comma separated)</small></label>
                    <input type="text" id="skills" name="skills"
                           placeholder="PHP, MySQL, Linux, Apache, Docker">
                </div>
                <div class="form-group">
                    <label for="expires_at">Listing Expires On</label>
                    <input type="date" id="expires_at" name="expires_at"
                           min="<?= date('Y-m-d') ?>"
                           value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">Post Job →</button>
                <a href="/dashboard.php" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
</body>
</html>
