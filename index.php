<?php
// ============================================================
//  CloudJobs - Homepage
//  File: public_html/index.php
// ============================================================
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/Jobs.php';

$auth = new Auth();
$jobs = new Jobs();
$db   = Database::getInstance();

$currentUser = $auth->currentUser();

// Fetch stats
$totalJobs      = $db->count('jobs', 'is_active = 1');
$totalCompanies = $db->count('companies');
$totalUsers     = $db->count('users', "role = 'jobseeker'");

// Fetch featured jobs
$featuredJobs = $jobs->getFeatured(6);

// Fetch categories with job counts
$categories = $db->fetchAll("
    SELECT c.*, COUNT(j.id) AS job_count
    FROM categories c
    LEFT JOIN jobs j ON j.category = c.slug AND j.is_active = 1
    GROUP BY c.id
    ORDER BY job_count DESC
    LIMIT 8
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CloudJobs - Find your next tech job. Built on AWS LAMP Stack.">
    <title><?= APP_NAME ?> - Cloud-Powered Job Portal</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- NAVIGATION -->
<nav class="navbar">
    <div class="container nav-inner">
        <a href="/" class="logo">Cloud<span>Jobs</span></a>
        <ul class="nav-links">
            <li><a href="/jobs.php">Browse Jobs</a></li>
            <li><a href="/companies.php">Companies</a></li>
            <li><a href="/salary.php">Salary Guide</a></li>
        </ul>
        <div class="nav-actions">
            <?php if ($currentUser): ?>
                <a href="/dashboard.php" class="btn btn-outline">Dashboard</a>
                <a href="/logout.php" class="btn btn-ghost">Logout</a>
            <?php else: ?>
                <a href="/login.php" class="btn btn-outline">Sign In</a>
                <a href="/register.php" class="btn btn-primary">Get Started</a>
            <?php endif; ?>
            <?php if ($currentUser && $currentUser['role'] === 'employer'): ?>
                <a href="/post-job.php" class="btn btn-success">Post a Job</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="container">
        <span class="hero-badge">☁️ Powered by Cloud LAMP Stack</span>
        <h1>Find Your <em>Dream Job</em><br>in Tech &amp; Cloud</h1>
        <p class="hero-sub">
            <?= number_format($totalJobs) ?>+ active jobs from
            <?= number_format($totalCompanies) ?>+ companies.
            Land your next role today.
        </p>

        <!-- SEARCH FORM -->
        <form class="search-form" action="/jobs.php" method="GET">
            <div class="search-box">
                <input type="text" name="q" placeholder="Job title, skills, or keyword..."
                       value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                <div class="divider"></div>
                <input type="text" name="location" placeholder="Location or Remote"
                       value="<?= htmlspecialchars($_GET['location'] ?? '') ?>">
                <button type="submit" class="btn btn-primary">🔍 Search</button>
            </div>
            <div class="popular-tags">
                <span>Trending:</span>
                <?php foreach (['PHP Developer','MySQL DBA','Linux Admin','AWS DevOps','Full Stack'] as $tag): ?>
                    <a href="/jobs.php?q=<?= urlencode($tag) ?>" class="tag"><?= $tag ?></a>
                <?php endforeach; ?>
            </div>
        </form>

        <!-- STATS -->
        <div class="stats-row">
            <div class="stat"><strong><?= number_format($totalJobs) ?>+</strong><span>Active Jobs</span></div>
            <div class="stat"><strong><?= number_format($totalCompanies) ?>+</strong><span>Companies</span></div>
            <div class="stat"><strong><?= number_format($totalUsers) ?>+</strong><span>Candidates</span></div>
            <div class="stat"><strong>87%</strong><span>Placement Rate</span></div>
        </div>
    </div>
</section>

<!-- CATEGORIES -->
<section class="section categories-section">
    <div class="container">
        <h2 class="section-title">Browse by Category</h2>
        <div class="categories-grid">
            <?php foreach ($categories as $cat): ?>
            <a href="/jobs.php?category=<?= urlencode($cat['slug']) ?>" class="category-card">
                <span class="cat-icon"><?= htmlspecialchars($cat['icon'] ?? '💼') ?></span>
                <span class="cat-name"><?= htmlspecialchars($cat['name']) ?></span>
                <span class="cat-count"><?= $cat['job_count'] ?> jobs</span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FEATURED JOBS -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Featured Jobs</h2>
            <a href="/jobs.php?featured=1" class="see-all">View all →</a>
        </div>
        <div class="jobs-grid">
            <?php foreach ($featuredJobs as $job): ?>
            <div class="job-card<?= $job['is_featured'] ? ' featured' : '' ?>">
                <?php if ($job['is_featured']): ?>
                    <span class="featured-badge">Featured</span>
                <?php endif; ?>
                <div class="job-card-top">
                    <?php if ($job['company_logo']): ?>
                        <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($job['company_logo']) ?>"
                             alt="<?= htmlspecialchars($job['company_name']) ?>" class="company-logo">
                    <?php else: ?>
                        <div class="logo-placeholder"><?= strtoupper(substr($job['company_name'], 0, 2)) ?></div>
                    <?php endif; ?>
                    <div>
                        <p class="company-name"><?= htmlspecialchars($job['company_name']) ?></p>
                        <h3 class="job-title">
                            <a href="/job.php?slug=<?= urlencode($job['slug']) ?>">
                                <?= htmlspecialchars($job['title']) ?>
                            </a>
                        </h3>
                    </div>
                </div>
                <div class="job-tags">
                    <?php if ($job['is_remote']): ?><span class="tag tag-remote">Remote</span><?php endif; ?>
                    <span class="tag tag-type"><?= ucfirst($job['type']) ?></span>
                    <span class="tag tag-level"><?= ucfirst($job['level']) ?></span>
                    <?php foreach (array_slice($job['skills'], 0, 2) as $skill): ?>
                        <span class="tag tag-skill"><?= htmlspecialchars($skill) ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="job-card-footer">
                    <?php if ($job['salary_min']): ?>
                        <span class="salary">
                            <?= $job['salary_currency'] ?>
                            <?= number_format($job['salary_min']/1000) ?>K –
                            <?= number_format($job['salary_max']/1000) ?>K
                        </span>
                    <?php endif; ?>
                    <span class="posted-time"><?= human_time_diff($job['created_at']) ?></span>
                    <?php if ($auth->isLoggedIn()): ?>
                    <button class="save-btn" data-job-id="<?= $job['id'] ?>"
                            onclick="toggleSave(this, <?= $job['id'] ?>)">♡</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="logo">Cloud<span>Jobs</span></div>
                <p>Built on Cloud LAMP Stack.<br>Linux · Apache · MySQL · PHP</p>
            </div>
            <div>
                <h4>For Jobseekers</h4>
                <ul><li><a href="/jobs.php">Browse Jobs</a></li>
                    <li><a href="/register.php">Create Profile</a></li>
                    <li><a href="/salary.php">Salary Guide</a></li></ul>
            </div>
            <div>
                <h4>For Employers</h4>
                <ul><li><a href="/post-job.php">Post a Job</a></li>
                    <li><a href="/pricing.php">Pricing</a></li></ul>
            </div>
            <div>
                <h4>Company</h4>
                <ul><li><a href="/about.php">About</a></li>
                    <li><a href="/contact.php">Contact</a></li>
                    <li><a href="/privacy.php">Privacy</a></li></ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> CloudJobs. Built on AWS LAMP Stack.</p>
        </div>
    </div>
</footer>

<script src="/js/app.js"></script>
</body>
</html>

<?php
// Helper: human-readable time diff
function human_time_diff(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 3600)  return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    return floor($diff/86400) . 'd ago';
}
?>
