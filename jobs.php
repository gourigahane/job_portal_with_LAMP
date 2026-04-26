<?php
// ============================================================
//  CloudJobs - Jobs Listing Page with Search & Filters
//  File: public_html/jobs.php
// ============================================================
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/Jobs.php';

$auth    = new Auth();
$jobsObj = new Jobs();

$filters = [
    'q'          => trim($_GET['q'] ?? ''),
    'type'       => $_GET['type'] ?? '',
    'level'      => $_GET['level'] ?? '',
    'remote'     => $_GET['remote'] ?? '',
    'category'   => $_GET['category'] ?? '',
    'location'   => trim($_GET['location'] ?? ''),
    'salary_min' => $_GET['salary_min'] ?? '',
];
$page   = max(1, (int)($_GET['page'] ?? 1));
$result = $jobsObj->search($filters, $page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Jobs – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php include 'partials/navbar.php'; ?>

<div class="page-container">
    <!-- SIDEBAR FILTERS -->
    <aside class="filters-sidebar">
        <h3>Filter Jobs</h3>
        <form method="GET" action="/jobs.php" id="filter-form">
            <input type="hidden" name="q" value="<?= htmlspecialchars($filters['q']) ?>">

            <div class="filter-group">
                <label>Job Type</label>
                <?php foreach (['full-time'=>'Full Time','part-time'=>'Part Time','contract'=>'Contract','internship'=>'Internship'] as $val=>$label): ?>
                <label class="checkbox-label">
                    <input type="radio" name="type" value="<?= $val ?>"
                           <?= $filters['type'] === $val ? 'checked' : '' ?>
                           onchange="this.form.submit()">
                    <?= $label ?>
                </label>
                <?php endforeach; ?>
            </div>

            <div class="filter-group">
                <label>Experience Level</label>
                <?php foreach (['junior','mid','senior','lead'] as $level): ?>
                <label class="checkbox-label">
                    <input type="radio" name="level" value="<?= $level ?>"
                           <?= $filters['level'] === $level ? 'checked' : '' ?>
                           onchange="this.form.submit()">
                    <?= ucfirst($level) ?>
                </label>
                <?php endforeach; ?>
            </div>

            <div class="filter-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="remote" value="1"
                           <?= $filters['remote'] ? 'checked' : '' ?>
                           onchange="this.form.submit()">
                    Remote Only
                </label>
            </div>

            <div class="filter-group">
                <label>Min Salary ($K)</label>
                <select name="salary_min" onchange="this.form.submit()">
                    <option value="">Any</option>
                    <option value="50000"  <?= $filters['salary_min']=='50000'  ? 'selected':'' ?>>$50K+</option>
                    <option value="80000"  <?= $filters['salary_min']=='80000'  ? 'selected':'' ?>>$80K+</option>
                    <option value="100000" <?= $filters['salary_min']=='100000' ? 'selected':'' ?>>$100K+</option>
                    <option value="120000" <?= $filters['salary_min']=='120000' ? 'selected':'' ?>>$120K+</option>
                </select>
            </div>

            <a href="/jobs.php" class="btn btn-ghost btn-sm">Clear Filters</a>
        </form>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="jobs-main">
        <!-- SEARCH BAR -->
        <form class="inline-search" method="GET" action="/jobs.php">
            <input type="text" name="q" placeholder="Search jobs, skills, companies..."
                   value="<?= htmlspecialchars($filters['q']) ?>">
            <input type="hidden" name="type"       value="<?= htmlspecialchars($filters['type']) ?>">
            <input type="hidden" name="level"      value="<?= htmlspecialchars($filters['level']) ?>">
            <input type="hidden" name="remote"     value="<?= htmlspecialchars($filters['remote']) ?>">
            <input type="hidden" name="category"   value="<?= htmlspecialchars($filters['category']) ?>">
            <input type="hidden" name="salary_min" value="<?= htmlspecialchars($filters['salary_min']) ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <div class="results-meta">
            <p>Found <strong><?= number_format($result['total']) ?></strong> jobs
               <?= $filters['q'] ? 'for "<em>' . htmlspecialchars($filters['q']) . '</em>"' : '' ?>
            </p>
            <div class="sort-options">
                <select onchange="window.location.href='?'+new URLSearchParams({...Object.fromEntries(new URLSearchParams(window.location.search)),sort:this.value})">
                    <option value="recent">Most Recent</option>
                    <option value="salary">Highest Salary</option>
                    <option value="featured">Featured First</option>
                </select>
            </div>
        </div>

        <!-- JOB LIST -->
        <?php if (empty($result['jobs'])): ?>
            <div class="empty-state">
                <p>🔍 No jobs found matching your criteria.</p>
                <a href="/jobs.php" class="btn btn-outline">Clear search</a>
            </div>
        <?php else: ?>
        <div class="job-list">
            <?php foreach ($result['jobs'] as $job): ?>
            <div class="job-list-item<?= $job['is_featured'] ? ' featured' : '' ?>">
                <div class="job-logo-col">
                    <?php if ($job['company_logo']): ?>
                        <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($job['company_logo']) ?>"
                             alt="<?= htmlspecialchars($job['company_name']) ?>">
                    <?php else: ?>
                        <div class="logo-placeholder-sm"><?= strtoupper(substr($job['company_name'], 0, 2)) ?></div>
                    <?php endif; ?>
                </div>
                <div class="job-info-col">
                    <div class="job-meta-row">
                        <span class="company-name-sm"><?= htmlspecialchars($job['company_name']) ?></span>
                        <?php if ($job['is_featured']): ?>
                            <span class="badge-featured">Featured</span>
                        <?php endif; ?>
                    </div>
                    <h3 class="job-title-list">
                        <a href="/job.php?slug=<?= urlencode($job['slug']) ?>">
                            <?= htmlspecialchars($job['title']) ?>
                        </a>
                    </h3>
                    <div class="job-tags-row">
                        <?php if ($job['location']): ?>
                            <span class="tag-loc">📍 <?= htmlspecialchars($job['location']) ?></span>
                        <?php endif; ?>
                        <?php if ($job['is_remote']): ?>
                            <span class="tag tag-remote">Remote</span>
                        <?php endif; ?>
                        <span class="tag tag-type"><?= ucfirst($job['type']) ?></span>
                        <span class="tag tag-level"><?= ucfirst($job['level']) ?></span>
                        <?php foreach (array_slice($job['skills'], 0, 3) as $skill): ?>
                            <span class="tag tag-skill"><?= htmlspecialchars($skill) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="job-salary-col">
                    <?php if ($job['salary_min']): ?>
                        <div class="salary-display">
                            <?= $job['salary_currency'] ?>
                            <?= number_format($job['salary_min']/1000) ?>K–<?= number_format($job['salary_max']/1000) ?>K
                        </div>
                    <?php endif; ?>
                    <div class="posted-time">
                        <?= human_time_diff($job['created_at']) ?>
                    </div>
                    <a href="/job.php?slug=<?= urlencode($job['slug']) ?>" class="btn btn-outline btn-sm">View Job</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- PAGINATION -->
        <?php if ($result['pages'] > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
                <?php
                $qp = http_build_query(array_merge($filters, ['page' => $i]));
                $active = $i === $result['current'] ? ' active' : '';
                ?>
                <a href="/jobs.php?<?= $qp ?>" class="page-btn<?= $active ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<?php include 'partials/footer.php'; ?>
<script src="/js/app.js"></script>
</body>
</html>

<?php
function human_time_diff(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 3600)  return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    return floor($diff/86400) . 'd ago';
}
?>
