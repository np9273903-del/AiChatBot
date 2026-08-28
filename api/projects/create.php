<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');
$user = require_auth_api();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$rawName = trim($input['name'] ?? '');
// Clean stray brackets, parentheses, quotes, or trailing punctuation
$name = trim(preg_replace('/[\]\}\)\>]+$/', '', $rawName));
$name = trim($name, "[]{}()<>.,;:/\\|`~*\"'");

if (strlen($name) < 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Project name must be at least 2 characters']);
    exit;
}

$conn = get_db();
$conn->begin_transaction();
try {
    $stmt = $conn->prepare('INSERT INTO projects (name, owner_id) VALUES (?, ?)');
    $stmt->bind_param('si', $name, $user['id']);
    $stmt->execute();
    $projectId = $stmt->insert_id;
    $stmt->close();

    $stmt = $conn->prepare('INSERT INTO project_users (project_id, user_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $projectId, $user['id']);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'project' => ['id' => $projectId, 'name' => $name]]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not create project']);
}
