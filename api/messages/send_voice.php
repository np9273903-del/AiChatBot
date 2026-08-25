<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');
$user = require_auth_api();

$projectId = intval($_POST['project_id'] ?? 0);
$clientId = substr(trim((string) ($_POST['client_id'] ?? '')), 0, 40);

if (!$projectId || !isset($_FILES['voice']) || $_FILES['voice']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A voice recording and project_id are required']);
    exit;
}
if ($_FILES['voice']['size'] > 10 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Voice notes must be under 10 MB']);
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

// Browsers record via MediaRecorder as webm/ogg — accept those two, nothing else.
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['voice']['tmp_name']);
$extensions = ['audio/webm' => 'webm', 'video/webm' => 'webm', 'audio/ogg' => 'ogg', 'audio/mpeg' => 'mp3'];
if (!isset($extensions[$mime])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unsupported audio format']);
    exit;
}

$directory = __DIR__ . '/../../uploads/voice';
if (!is_dir($directory) && !mkdir($directory, 0750, true)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not create voice upload folder']);
    exit;
}

$filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
if (!move_uploaded_file($_FILES['voice']['tmp_name'], $directory . '/' . $filename)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not save voice note']);
    exit;
}

$url = 'uploads/voice/' . $filename;
$placeholderText = '🎤 Voice message';

$stmt = $conn->prepare("INSERT INTO messages (project_id, user_id, client_id, sender_label, message, attachment_url, attachment_type, is_ai) VALUES (?, ?, ?, ?, ?, ?, 'audio', 0)");
$stmt->bind_param('iissss', $projectId, $user['id'], $clientId, $user['email'], $placeholderText, $url);
$stmt->execute();
$messageId = $stmt->insert_id;
$stmt->close();

echo json_encode(['success' => true, 'id' => $messageId, 'attachment_url' => $url]);
