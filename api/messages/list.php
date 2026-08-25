<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');
$user = require_auth_api();

$projectId = intval($_GET['project_id'] ?? 0);
$afterId = intval($_GET['after_id'] ?? 0);

if (!$projectId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing project_id']);
    exit;
}

$conn = get_db();

$stmt = $conn->prepare('SELECT id FROM project_users WHERE project_id = ? AND user_id = ?');
$stmt->bind_param('ii', $projectId, $user['id']);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You are not a member of this project']);
    exit;
}
$stmt->close();

$stmt = $conn->prepare('SELECT id, user_id, sender_label, message, attachment_url, attachment_type, is_ai, created_at FROM messages WHERE project_id = ? AND id > ? ORDER BY id ASC');
$stmt->bind_param('ii', $projectId, $afterId);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode(['success' => true, 'messages' => $messages]);
