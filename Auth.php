<?php
// ============================================================
//  CloudJobs - Authentication Class
//  File: includes/Auth.php
// ============================================================

require_once 'Database.php';

class Auth {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_lifetime' => SESSION_LIFETIME,
                'cookie_secure'   => (ENV === 'production'),
                'cookie_httponly' => true,
                'cookie_samesite' => 'Strict',
            ]);
        }
    }

    // ---- Register new user ----
    public function register(array $data): array {
        // Validate required fields
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            return ['success' => false, 'error' => 'All fields are required.'];
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email address.'];
        }
        if (strlen($data['password']) < 8) {
            return ['success' => false, 'error' => 'Password must be at least 8 characters.'];
        }

        // Check duplicate email
        if ($this->db->count('users', 'email = ?', [$data['email']]) > 0) {
            return ['success' => false, 'error' => 'Email already registered.'];
        }

        $verifyToken = bin2hex(random_bytes(32));
        $userId = $this->db->insert('users', [
            'name'         => htmlspecialchars(trim($data['name'])),
            'email'        => strtolower(trim($data['email'])),
            'password'     => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => HASH_COST]),
            'role'         => in_array($data['role'] ?? '', ['jobseeker','employer']) ? $data['role'] : 'jobseeker',
            'verify_token' => $verifyToken,
        ]);

        // TODO: send verification email
        return ['success' => true, 'user_id' => $userId];
    }

    // ---- Login ----
    public function login(string $email, string $password): array {
        $user = $this->db->fetchOne(
            'SELECT * FROM users WHERE email = ? AND is_active = 1',
            [strtolower(trim($email))]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }

        // Rehash if needed
        if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => HASH_COST])) {
            $this->db->update('users', [
                'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST])
            ], 'id = ?', [$user['id']]);
        }

        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        return ['success' => true, 'role' => $user['role']];
    }

    // ---- Logout ----
    public function logout(): void {
        $_SESSION = [];
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // ---- Check if logged in ----
    public function isLoggedIn(): bool {
        return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
    }

    // ---- Get current user ----
    public function currentUser(): ?array {
        if (!$this->isLoggedIn()) return null;
        return $this->db->fetchOne(
            'SELECT id, name, email, role, avatar, location, phone FROM users WHERE id = ?',
            [$_SESSION['user_id']]
        );
    }

    // ---- Require login (redirect if not) ----
    public function requireLogin(string $redirect = '/login.php'): void {
        if (!$this->isLoggedIn()) {
            header("Location: {$redirect}");
            exit;
        }
    }

    // ---- Require specific role ----
    public function requireRole(string $role): void {
        $this->requireLogin();
        if ($_SESSION['user_role'] !== $role && $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            die('<h1>403 Forbidden</h1><p>You do not have permission to access this page.</p>');
        }
    }

    // ---- CSRF Token ----
    public function csrfToken(): string {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    public function verifyCsrf(string $token): bool {
        return isset($_SESSION[CSRF_TOKEN_NAME]) &&
               hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
}
