<?php
// ============================================================
//  CloudJobs - Jobs Model
//  File: includes/Jobs.php
// ============================================================

require_once 'Database.php';

class Jobs {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ---- Create job slug ----
    private function slugify(string $title, int $jobId): string {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($title)));
        return "{$slug}-{$jobId}";
    }

    // ---- Post a new job ----
    public function create(array $data): int {
        $jobId = $this->db->insert('jobs', [
            'company_id'      => $data['company_id'],
            'title'           => htmlspecialchars($data['title']),
            'slug'            => 'temp',
            'description'     => $data['description'],
            'requirements'    => $data['requirements'] ?? null,
            'benefits'        => $data['benefits'] ?? null,
            'type'            => $data['type'] ?? 'full-time',
            'level'           => $data['level'] ?? 'mid',
            'location'        => $data['location'] ?? null,
            'is_remote'       => (int)($data['is_remote'] ?? 0),
            'salary_min'      => $data['salary_min'] ?? null,
            'salary_max'      => $data['salary_max'] ?? null,
            'salary_currency' => $data['salary_currency'] ?? 'USD',
            'skills'          => json_encode($data['skills'] ?? []),
            'category'        => $data['category'] ?? null,
            'expires_at'      => $data['expires_at'] ?? date('Y-m-d', strtotime('+30 days')),
        ]);
        // Update slug after getting ID
        $this->db->update('jobs', ['slug' => $this->slugify($data['title'], $jobId)], 'id = ?', [$jobId]);
        return $jobId;
    }

    // ---- Search and filter jobs ----
    public function search(array $filters = [], int $page = 1): array {
        $where    = ['j.is_active = 1', '(j.expires_at IS NULL OR j.expires_at >= CURDATE())'];
        $params   = [];
        $offset   = ($page - 1) * JOBS_PER_PAGE;

        if (!empty($filters['q'])) {
            $where[]  = 'MATCH(j.title, j.description, j.requirements) AGAINST(? IN BOOLEAN MODE)';
            $params[] = $filters['q'] . '*';
        }
        if (!empty($filters['type'])) {
            $where[]  = 'j.type = ?';
            $params[] = $filters['type'];
        }
        if (!empty($filters['level'])) {
            $where[]  = 'j.level = ?';
            $params[] = $filters['level'];
        }
        if (!empty($filters['remote'])) {
            $where[]  = 'j.is_remote = 1';
        }
        if (!empty($filters['category'])) {
            $where[]  = 'j.category = ?';
            $params[] = $filters['category'];
        }
        if (!empty($filters['location'])) {
            $where[]  = 'j.location LIKE ?';
            $params[] = '%' . $filters['location'] . '%';
        }
        if (!empty($filters['salary_min'])) {
            $where[]  = 'j.salary_max >= ?';
            $params[] = $filters['salary_min'];
        }

        $whereStr = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) as cnt FROM jobs j WHERE {$whereStr}";
        $total    = (int)$this->db->fetchOne($countSql, $params)['cnt'];

        $sql = "
            SELECT j.*, c.name AS company_name, c.logo AS company_logo, c.location AS company_location
            FROM jobs j
            LEFT JOIN companies c ON c.id = j.company_id
            WHERE {$whereStr}
            ORDER BY j.is_featured DESC, j.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $jobs = $this->db->fetchAll($sql, [...$params, JOBS_PER_PAGE, $offset]);

        // Decode JSON skills
        foreach ($jobs as &$job) {
            $job['skills'] = json_decode($job['skills'] ?? '[]', true);
        }

        return [
            'jobs'       => $jobs,
            'total'      => $total,
            'pages'      => (int)ceil($total / JOBS_PER_PAGE),
            'current'    => $page,
        ];
    }

    // ---- Get single job by slug ----
    public function getBySlug(string $slug): ?array {
        $job = $this->db->fetchOne("
            SELECT j.*, c.name AS company_name, c.logo AS company_logo,
                   c.website AS company_website, c.description AS company_desc,
                   c.size AS company_size, c.industry
            FROM jobs j
            LEFT JOIN companies c ON c.id = j.company_id
            WHERE j.slug = ? AND j.is_active = 1
        ", [$slug]);

        if ($job) {
            $job['skills'] = json_decode($job['skills'] ?? '[]', true);
            // Increment views
            $this->db->query('UPDATE jobs SET views = views + 1 WHERE id = ?', [$job['id']]);
        }
        return $job;
    }

    // ---- Apply to a job ----
    public function apply(int $jobId, int $userId, array $data): array {
        // Check already applied
        $existing = $this->db->fetchOne(
            'SELECT id FROM applications WHERE job_id = ? AND user_id = ?',
            [$jobId, $userId]
        );
        if ($existing) {
            return ['success' => false, 'error' => 'You have already applied for this job.'];
        }

        $appId = $this->db->insert('applications', [
            'job_id'       => $jobId,
            'user_id'      => $userId,
            'cover_letter' => $data['cover_letter'] ?? null,
            'resume'       => $data['resume'] ?? null,
            'status'       => 'applied',
        ]);

        return ['success' => true, 'application_id' => $appId];
    }

    // ---- Get applications for employer ----
    public function getApplications(int $companyId): array {
        return $this->db->fetchAll("
            SELECT a.*, j.title AS job_title, u.name AS applicant_name,
                   u.email AS applicant_email, u.avatar AS applicant_avatar
            FROM applications a
            JOIN jobs j ON j.id = a.job_id
            JOIN users u ON u.id = a.user_id
            WHERE j.company_id = ?
            ORDER BY a.applied_at DESC
        ", [$companyId]);
    }

    // ---- Get featured jobs ----
    public function getFeatured(int $limit = 6): array {
        return $this->db->fetchAll("
            SELECT j.*, c.name AS company_name, c.logo AS company_logo
            FROM jobs j
            LEFT JOIN companies c ON c.id = j.company_id
            WHERE j.is_featured = 1 AND j.is_active = 1
            ORDER BY j.created_at DESC
            LIMIT ?
        ", [$limit]);
    }

    // ---- Save / unsave a job ----
    public function toggleSave(int $userId, int $jobId): bool {
        $saved = $this->db->fetchOne(
            'SELECT id FROM saved_jobs WHERE user_id = ? AND job_id = ?',
            [$userId, $jobId]
        );
        if ($saved) {
            $this->db->delete('saved_jobs', 'user_id = ? AND job_id = ?', [$userId, $jobId]);
            return false; // unsaved
        } else {
            $this->db->insert('saved_jobs', ['user_id' => $userId, 'job_id' => $jobId]);
            return true;  // saved
        }
    }
}
