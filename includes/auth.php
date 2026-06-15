<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if (!$user) {
            logout();
            return null;
        }
    }
    return $user;
}

function hasRole(string $role): bool {
    if (!isLoggedIn()) {
        return false;
    }
    return ($_SESSION['role'] ?? '') === $role;
}

function hasAnyRole(array $roles): bool {
    if (!isLoggedIn()) {
        return false;
    }
    return in_array($_SESSION['role'] ?? '', $roles, true);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        if (isAjax()) {
            jsonResponse(['error' => 'Authentication required'], 401);
        }
        header('Location: ' . APP_URL . '/pages/login.php');
        exit;
    }
}

function requireRole(array $roles): void {
    requireLogin();
    if (!hasAnyRole($roles)) {
        if (isAjax()) {
            jsonResponse(['error' => 'Access denied'], 403);
        }
        http_response_code(403);
        die('<h1>403 — Access Denied</h1>');
    }
}

function isAjax(): bool {
    if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        return false;
    }
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function login(string $email, string $password): array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
    session_regenerate_id(true);

    return ['success' => true, 'user' => [
        'id' => $user['id'],
        'full_name' => $user['full_name'],
        'role' => $user['role'],
    ]];
}

function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function isEmailDomainAllowed(string $email): bool {
    $domain = substr(strrchr($email, '@'), 1);
    if (!$domain) {
        return false;
    }
    $db = getDB();
    $stmt = $db->prepare('SELECT id FROM allowed_domains WHERE ? = domain OR ? LIKE CONCAT("%.", domain)');
    $stmt->execute([$domain, $domain]);
    $result = $stmt->fetch();
    if ($result) {
        return true;
    }
    return false;
}
