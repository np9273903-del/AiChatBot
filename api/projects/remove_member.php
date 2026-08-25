<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');
$user = require_auth_api();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$projectId = intval($input['project_id'] ?? 0);
$targetUserId = intval($input['user_id'] ?? 0);

if (!$projectId || !$targetUserId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Project ID and user ID are required']);
    exit;
}

$conn = get_db();

// Verify requester is the project owner
$stmt = $conn->prepare('SELECT owner_id FROM projects WHERE id = ?');
$stmt->bind_param('i', $projectId);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Project not found']);
    exit;
}

if ((int) $project['owner_id'] !== (int) $user['id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only the project owner can remove members']);
    exit;
}

if ((int) $targetUserId === (int) $user['id']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'You cannot remove yourself']);
    exit;
}

// Remove member
$stmt = $conn->prepare('DELETE FROM project_users WHERE project_id = ? AND user_id = ?');
$stmt->bind_param('ii', $projectId, $targetUserId);
$stmt->execute();
$removed = $stmt->affected_rows > 0;
$stmt->close();

if (!$removed) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User is not a member of this project']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Member removed']);

