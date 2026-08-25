<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');
$user = require_auth_api();

$conn = get_db();
$stmt = $conn->prepare('
    SELECT p.id, p.name, p.created_at, COUNT(pu2.id) AS member_count
    FROM projects p
    JOIN project_users pu ON pu.project_id = p.id AND pu.user_id = ?
    JOIN project_users pu2 ON pu2.project_id = p.id
    GROUP BY p.id, p.name, p.created_at
    ORDER BY p.created_at DESC
');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$result = $stmt->get_result();

$projects = [];
while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
}

echo json_encode(['success' => true, 'projects' => $projects]);
