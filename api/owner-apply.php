<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$db = getDB();

$full_name  = trim($_POST['full_name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$address    = trim($_POST['address'] ?? '');
$extra_info = trim($_POST['extra_info'] ?? '');

if (empty($full_name) || empty($email) || empty($phone) || empty($address)) {
    jsonResponse(['error' => 'All required fields must be filled.'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Please enter a valid email address.'], 400);
}

$nidOk  = isset($_FILES['nid'])   && $_FILES['nid']['error']   === UPLOAD_ERR_OK;
$billOk = isset($_FILES['bill'])  && $_FILES['bill']['error']  === UPLOAD_ERR_OK;
$photoOk= isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK;

if (!$nidOk || !$billOk || !$photoOk) {
    jsonResponse(['error' => 'All three documents (NID, electricity bill, photo) are required.'], 400);
}

$existing = $db->prepare("SELECT id FROM owner_applications WHERE email = ?");
$existing->execute([$email]);
if ($existing->fetch()) {
    jsonResponse(['error' => 'An application with this email already exists. Please wait for admin review.'], 400);
}

$alreadyUser = $db->prepare("SELECT id FROM users WHERE email = ?");
$alreadyUser->execute([$email]);
if ($alreadyUser->fetch()) {
    jsonResponse(['error' => 'An account with this email already exists. Try logging in instead.'], 400);
}

$dir = APP_ROOT . '/uploads/owner_applications/' . md5($email . time());
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$allowed_ext = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];

$nid_ext   = strtolower(pathinfo($_FILES['nid']['name'],   PATHINFO_EXTENSION));
$bill_ext  = strtolower(pathinfo($_FILES['bill']['name'],  PATHINFO_EXTENSION));
$photo_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

if (!in_array($nid_ext, $allowed_ext) || !in_array($bill_ext, $allowed_ext) || !in_array($photo_ext, ['jpg','jpeg','png','webp'])) {
    jsonResponse(['error' => 'Invalid file type. Use JPG, PNG, or PDF.'], 400);
}

$folder_name = basename($dir);
$nid_path    = 'uploads/owner_applications/' . $folder_name . '/nid.'   . $nid_ext;
$bill_path   = 'uploads/owner_applications/' . $folder_name . '/bill.'  . $bill_ext;
$photo_path  = 'uploads/owner_applications/' . $folder_name . '/photo.' . $photo_ext;

$moved1 = move_uploaded_file($_FILES['nid']['tmp_name'],   APP_ROOT . '/' . $nid_path);
$moved2 = move_uploaded_file($_FILES['bill']['tmp_name'],  APP_ROOT . '/' . $bill_path);
$moved3 = move_uploaded_file($_FILES['photo']['tmp_name'], APP_ROOT . '/' . $photo_path);

if (!$moved1 || !$moved2 || !$moved3) {
    jsonResponse(['error' => 'File upload failed. Please try again.'], 500);
}

$stmt = $db->prepare("INSERT INTO owner_applications (full_name, email, phone, address, nid_path, electricity_bill_path, photo_path, extra_info, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");

$stmt->execute([
    $full_name,
    $email,
    $phone,
    $address,
    $nid_path,
    $bill_path,
    $photo_path,
    $extra_info
]);

jsonResponse(['success' => true]);
