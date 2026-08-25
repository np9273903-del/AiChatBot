<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/email_service.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$email = trim($input['email'] ?? '');
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid email is required']);
    exit;
}
if (strlen($username) < 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username must be at least 2 characters']);
    exit;
}
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

$conn = get_db();

$stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Email is already registered']);
    exit;
}
$stmt->close();

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $conn->prepare('INSERT INTO users (email, username, password_hash) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $email, $username, $hash);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not create user']);
    exit;
}

$userId = $stmt->insert_id;
$stmt->close();

// log the user in immediately
$_SESSION['user_id'] = $userId;
$_SESSION['user_email'] = $email;
$_SESSION['user_username'] = $username;

// real email #1: welcome email
send_welcome_email($email, $username);

echo json_encode(['success' => true, 'user' => ['id' => $userId, 'email' => $email, 'username' => $username]]);
