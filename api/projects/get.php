<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');
$user = require_auth_api();

$projectId = intval($_GET['id'] ?? 0);
if (!$projectId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing project id']);
    exit;
}

$conn = get_db();

// verify membership
$stmt = $conn->prepare('SELECT id FROM project_users WHERE project_id = ? AND user_id = ?');
$stmt->bind_param('ii', $projectId, $user['id']);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You are not a member of this project']);
    exit;
}
$stmt->close();

$stmt = $conn->prepare('SELECT id, name, created_at FROM projects WHERE id = ?');
$stmt->bind_param('i', $projectId);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Project not found']);
    exit;
}
$project['name'] = trim(rtrim(trim($project['name']), ']}))>.,;:/\\|`~*"'));

$stmt = $conn->prepare('
    SELECT u.id, u.email, u.username
    FROM project_users pu JOIN users u ON u.id = pu.user_id
    WHERE pu.project_id = ?
    ORDER BY u.username
');
$stmt->bind_param('i', $projectId);
$stmt->execute();
$result = $stmt->get_result();
$members = [];
while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}

$project['members'] = $members;
echo json_encode(['success' => true, 'project' => $project]);
