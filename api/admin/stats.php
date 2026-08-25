<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');
$user = require_auth_api();
$conn = get_db();
$stmt = $conn->prepare('SELECT id FROM projects WHERE owner_id = ? LIMIT 1'); $stmt->bind_param('i', $user['id']); $stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) { $stmt->close(); http_response_code(403); echo json_encode(['success'=>false,'message'=>'Only group admins can access this dashboard']); exit; }
$stmt->close();
$stmt = $conn->prepare('SELECT COUNT(*) AS total FROM projects WHERE owner_id = ?'); $stmt->bind_param('i', $user['id']); $stmt->execute(); $projectCount = (int) $stmt->get_result()->fetch_assoc()['total']; $stmt->close();
$stmt = $conn->prepare('SELECT COUNT(DISTINCT pu.user_id) AS total FROM project_users pu JOIN projects p ON p.id = pu.project_id WHERE p.owner_id = ?'); $stmt->bind_param('i', $user['id']); $stmt->execute(); $memberCount = (int) $stmt->get_result()->fetch_assoc()['total']; $stmt->close();
$stmt = $conn->prepare('SELECT COUNT(m.id) AS total FROM messages m JOIN projects p ON p.id = m.project_id WHERE p.owner_id = ?'); $stmt->bind_param('i', $user['id']); $stmt->execute(); $messageCount = (int) $stmt->get_result()->fetch_assoc()['total']; $stmt->close();
$stmt = $conn->prepare('SELECT p.id, p.name, p.created_at, COUNT(DISTINCT pu.user_id) AS member_count, COUNT(DISTINCT m.id) AS message_count FROM projects p LEFT JOIN project_users pu ON pu.project_id = p.id LEFT JOIN messages m ON m.project_id = p.id WHERE p.owner_id = ? GROUP BY p.id, p.name, p.created_at ORDER BY p.created_at DESC'); $stmt->bind_param('i', $user['id']); $stmt->execute(); $result = $stmt->get_result(); $projects = []; while ($row = $result->fetch_assoc()) $projects[] = $row; $stmt->close();
echo json_encode(['success'=>true,'stats'=>['projects'=>$projectCount,'members'=>$memberCount,'messages'=>$messageCount],'projects'=>$projects]);
