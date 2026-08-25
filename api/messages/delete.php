<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');
$user = require_auth_api();
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$projectId = intval($input['project_id'] ?? 0);
$messageId = intval($input['message_id'] ?? 0);
$action = $input['action'] ?? '';

if (!$projectId || !in_array($action, ['for_me', 'for_everyone', 'all'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid delete request']);
    exit;
}

$conn = get_db();
$stmt = $conn->prepare('SELECT p.owner_id FROM projects p JOIN project_users pu ON pu.project_id = p.id WHERE p.id = ? AND pu.user_id = ?');
$stmt->bind_param('ii', $projectId, $user['id']);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$project) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You are not a member of this project']);
    exit;
}

if ($action === 'for_me') {
    if (!$messageId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'message_id is required']);
        exit;
    }
    $stmt = $conn->prepare('INSERT IGNORE INTO message_hidden (message_id, user_id) SELECT id, ? FROM messages WHERE id = ? AND project_id = ?');
    $stmt->bind_param('iii', $user['id'], $messageId, $projectId);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'action' => $action, 'message_id' => $messageId]);
    exit;
}

if ((int) $project['owner_id'] !== (int) $user['id'] && $action === 'all') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only the project owner can delete all chats']);
    exit;
}

if ($action === 'for_everyone') {
    if (!$messageId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'message_id is required']);
        exit;
    }
    $stmt = $conn->prepare('DELETE FROM messages WHERE id = ? AND project_id = ? AND (user_id = ? OR ? = (SELECT owner_id FROM projects WHERE id = ?))');
    $stmt->bind_param('iiiii', $messageId, $projectId, $user['id'], $user['id'], $projectId);
    $stmt->execute();
    $deleted = $stmt->affected_rows > 0;
    $stmt->close();
    echo json_encode(['success' => $deleted, 'message' => $deleted ? null : 'You can only delete your own messages for everyone']);
    exit;
}

$stmt = $conn->prepare('DELETE FROM messages WHERE project_id = ?');
$stmt->bind_param('i', $projectId);
$stmt->execute();
$stmt->close();
$stmt = $conn->prepare('DELETE FROM message_hidden WHERE message_id NOT IN (SELECT id FROM messages)');
$stmt->execute();
$stmt->close();
echo json_encode(['success' => true, 'action' => $action]);
