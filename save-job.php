<?php
// ============================================================
//  CloudJobs - REST API: Save/Unsave Job
//  File: public_html/api/save-job.php
// ============================================================
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../../includes/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/Auth.php';
require_once '../../includes/Jobs.php';

$auth = new Auth();

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Must be logged in
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Parse JSON body
$body = json_decode(file_get_contents('php://input'), true);
$jobId = (int)($body['job_id'] ?? 0);

if (!$jobId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid job ID']);
    exit;
}

$jobsObj = new Jobs();
$saved = $jobsObj->toggleSave($_SESSION['user_id'], $jobId);

echo json_encode(['saved' => $saved, 'job_id' => $jobId]);
