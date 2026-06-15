<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: ' . APP_URL . '/pages/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (isset($_SESSION['user_id'])) {
        redirect(APP_URL . '/pages/dashboard.php');
    }
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$rawBody = file_get_contents('php://input');
$input = $rawBody ? (json_decode($rawBody, true) ?? $_POST) : $_POST;
if ($rawBody && json_last_error() !== JSON_ERROR_NONE) {
    jsonResponse(['error' => 'Invalid JSON body'], 400);
}

if ($action === 'login') {
    $db = getDB();
    $email    = strtolower(trim($input['email'] ?? ''));
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'Email and password are required.'], 400);
    }

    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Invalid email or password.'], 401);
    }

    if (!password_verify($password, $user['password_hash'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid email or password.'], 401);
    }

    $_SESSION['user_id']  = $user['id'];
    $_SESSION['role']     = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
    session_regenerate_id(true);
    session_write_close(); // Force write before exit to prevent silent failure on shared hosting

    jsonResponse(['success' => true, 'user' => [
        'id'        => $user['id'],
        'full_name' => $user['full_name'],
        'role'      => $user['role']
    ]]);
}

if ($action === 'register') {
    $db    = getDB();
    $email = strtolower(trim($input['email'] ?? ''));
    $name  = trim($input['full_name'] ?? '');
    $pass  = $input['password'] ?? '';
    $role  = $input['role'] ?? 'student';

    if (empty($email) || empty($name) || empty($pass)) {
        jsonResponse(['success' => false, 'message' => 'All fields are required.'], 400);
    }

    if (strlen($pass) < 6) {
        jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters.'], 400);
    }

    if ($role !== 'student') {
        jsonResponse(['success' => false, 'message' => 'Only students can self-register. Owners must apply separately.'], 400);
    }

    $domain      = substr(strrchr($email, '@'), 1);
    $domainCheck = $db->prepare('SELECT id FROM allowed_domains WHERE ? = domain OR ? LIKE CONCAT("%.", domain)');
    $domainCheck->execute([$domain, $domain]);


    if (!$domainCheck->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Please use your university email (e.g. @uiu.ac.bd or @bscse.uiu.ac.bd).'], 400);
    }

    $existing = $db->prepare('SELECT id FROM users WHERE email = ?');
    $existing->execute([$email]);
    if ($existing->fetch()) {
        jsonResponse(['success' => false, 'message' => 'An account with this email already exists.'], 400);
    }

    $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare('INSERT INTO users (full_name, email, password_hash, role, is_active, email_verified_at, gender, student_id, department, year_of_study) VALUES (?, ?, ?, ?, 1, NOW(), ?, ?, ?, ?)');
    $stmt->execute([
        $name,
        $email,
        $hash,
        'student',
        $input['gender'] ?? null,
        $input['student_id'] ?? null,
        $input['department'] ?? null,
        $input['year_of_study'] ?? null
    ]);

    $newId = (int) $db->lastInsertId();
    $_SESSION['user_id']   = $newId;
    $_SESSION['role']      = 'student';
    $_SESSION['full_name'] = $name;
    session_regenerate_id(true);
    session_write_close(); // Force write before exit to prevent silent failure on shared hosting

    jsonResponse(['success' => true, 'user' => ['id' => $newId, 'full_name' => $name, 'role' => 'student']]);
}

jsonResponse(['error' => 'Invalid action'], 400);
