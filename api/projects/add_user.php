<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/email_service.php';

header('Content-Type: application/json');
$user = require_auth_api();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$projectId = intval($input['project_id'] ?? 0);
$email = trim($input['email'] ?? '');

if (!$projectId || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'project_id and a valid email are required']);
    exit;
}

$conn = get_db();

// confirm requester is a member
$stmt = $conn->prepare('SELECT p.name FROM project_users pu JOIN projects p ON p.id = pu.project_id WHERE pu.project_id = ? AND pu.user_id = ?');
$stmt->bind_param('ii', $projectId, $user['id']);
$stmt->execute();
$projectRow = $stmt->get_result()->fetch_assoc();
if (!$projectRow) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You are not a member of this project']);
    exit;
}
$stmt->close();

// find the target user
$stmt = $conn->prepare('SELECT id, username FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$targetUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$targetUser) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'No user with that email is registered']);
    exit;
}

$stmt = $conn->prepare('INSERT IGNORE INTO project_users (project_id, user_id) VALUES (?, ?)');
$stmt->bind_param('ii', $projectId, $targetUser['id']);
$stmt->execute();
$stmt->close();

// real email #2: notify the newly-added collaborator
send_added_to_project_email($email, $projectRow['name'], $user['email']);

echo json_encode(['success' => true, 'message' => 'User added to project']);
