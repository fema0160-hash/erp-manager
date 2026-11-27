<?php
declare(strict_types=1);

/** Authentification et autorisations ERP Manager. Accès total par défaut. */
final class Auth
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        session_name('ERP_MANAGER_SESSION');
        session_set_cookie_params([
            'lifetime' => 0, 'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true, 'samesite' => 'Lax',
        ]);
        session_start();
        if (isset($_SESSION['auth_user_id'])) $this->validateStoredSession();
    }

    public function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return (string) $_SESSION['csrf_token'];
    }

    public function verifyCsrf(?string $token): bool
    {
        return is_string($token) && isset($_SESSION['csrf_token'])
            && hash_equals((string) $_SESSION['csrf_token'], $token);
    }

    public function login(string $username, string $password): bool
    {
        $q = $this->pdo->prepare(
            'SELECT id, password_hash, is_active, failed_login_attempts, locked_until
             FROM users WHERE username = :username LIMIT 1'
        );
        $q->execute(['username' => trim($username)]);
        $user = $q->fetch();

        if (!$user || !(bool) $user['is_active']) {
            password_verify($password, '$2y$12$FPeCH7VGhCNb5fcwyw/2SOc6/moVXmXRyiqej8Uh/RboJacDFzu1m');
            return false;
        }
        if (!empty($user['locked_until']) && strtotime((string) $user['locked_until']) > time()) return false;

        if (!password_verify($password, (string) $user['password_hash'])) {
            $attempts = (int) $user['failed_login_attempts'] + 1;
            $lock = $attempts >= 5 ? date('Y-m-d H:i:s', time() + 900) : null;
            $q = $this->pdo->prepare(
                'UPDATE users SET failed_login_attempts = :attempts, locked_until = :locked WHERE id = :id'
            );
            $q->execute(['attempts' => $attempts >= 5 ? 0 : $attempts, 'locked' => $lock, 'id' => (int) $user['id']]);
            return false;
        }

        session_regenerate_id(true);
        $_SESSION = ['auth_user_id' => (int) $user['id'], 'csrf_token' => bin2hex(random_bytes(32))];
        $q = $this->pdo->prepare(
            'INSERT INTO user_sessions
             (user_id, session_id_hash, ip_address, user_agent, last_activity_at, expires_at)
             VALUES (:uid, :hash, :ip, :agent, NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
        );
        $q->execute([
            'uid' => (int) $user['id'], 'hash' => hash('sha256', session_id()),
            'ip' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            'agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
        $q = $this->pdo->prepare(
            'UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = :id'
        );
        $q->execute(['id' => (int) $user['id']]);
        return true;
    }

    public function currentUser(): ?array
    {
        if (empty($_SESSION['auth_user_id'])) return null;
        $q = $this->pdo->prepare(
            'SELECT id, username, display_name, role, must_change_password
             FROM users WHERE id = :id AND is_active = 1 LIMIT 1'
        );
        $q->execute(['id' => (int) $_SESSION['auth_user_id']]);
        return $q->fetch() ?: null;
    }

    public function requireLogin(): array
    {
        $user = $this->currentUser();
        if (!$user) { header('Location: ?page=login'); exit; }
        return $user;
    }

    public function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) return;
        $q = $this->pdo->prepare('DELETE FROM user_sessions WHERE session_id_hash = :hash');
        $q->execute(['hash' => hash('sha256', session_id())]);
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000, 'path' => $p['path'] ?: '/',
                'domain' => $p['domain'], 'secure' => $p['secure'],
                'httponly' => $p['httponly'], 'samesite' => $p['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $q = $this->pdo->prepare('SELECT password_hash FROM users WHERE id = :id AND is_active = 1');
        $q->execute(['id' => $userId]);
        $row = $q->fetch();
        if (!$row || !password_verify($currentPassword, (string) $row['password_hash'])) return false;

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $q = $this->pdo->prepare(
            'UPDATE users SET password_hash = :hash, must_change_password = 0,
             password_changed_at = NOW(), failed_login_attempts = 0, locked_until = NULL WHERE id = :id'
        );
        $q->execute(['hash' => $hash, 'id' => $userId]);
        return true;
    }

    public function hasCompanyAccess(int $userId, string $role, int $companyId): bool
    {
        if ($role === 'GEST') return true;
        $q = $this->pdo->prepare(
            'SELECT 1 FROM user_company_restrictions WHERE user_id = :uid AND company_id = :cid LIMIT 1'
        );
        $q->execute(['uid' => $userId, 'cid' => $companyId]);
        return !$q->fetchColumn();
    }

    public function hasPermission(int $userId, string $role, int $companyId, string $permissionCode): bool
    {
        if ($role === 'GEST') return true;
        if (!$this->hasCompanyAccess($userId, $role, $companyId)) return false;
        $q = $this->pdo->prepare(
            'SELECT 1 FROM user_permission_restrictions upr
             INNER JOIN permissions p ON p.id = upr.permission_id
             WHERE upr.user_id = :uid AND upr.company_id = :cid AND p.code = :code LIMIT 1'
        );
        $q->execute(['uid' => $userId, 'cid' => $companyId, 'code' => $permissionCode]);
        return !$q->fetchColumn();
    }

    private function validateStoredSession(): void
    {
        $hash = hash('sha256', session_id());
        $q = $this->pdo->prepare(
            'SELECT id FROM user_sessions WHERE session_id_hash = :hash AND expires_at > NOW() LIMIT 1'
        );
        $q->execute(['hash' => $hash]);
        if (!$q->fetchColumn()) {
            $_SESSION = [];
            session_regenerate_id(true);
            return;
        }
        $q = $this->pdo->prepare(
            'UPDATE user_sessions SET last_activity_at = NOW(),
             expires_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE session_id_hash = :hash'
        );
        $q->execute(['hash' => $hash]);
    }
}
