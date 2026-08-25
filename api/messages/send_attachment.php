<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');
$user = require_auth_api();

$projectId = intval($_POST['project_id'] ?? 0);
$clientId = substr(trim((string) ($_POST['client_id'] ?? '')), 0, 40);
$caption = trim((string) ($_POST['caption'] ?? ''));

if (!$projectId || !isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A file and project_id are required']);
    exit;
}
if ($_FILES['file']['size'] > 15 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Attachments must be under 15 MB']);
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

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['file']['tmp_name']);
$imageExt = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
$isImage = isset($imageExt[$mime]);

// Non-image files are still allowed (like WhatsApp's document sharing) but are
// restricted to a small safe allowlist — never anything executable.
$fileExt = ['application/pdf' => 'pdf', 'text/plain' => 'txt', 'application/zip' => 'zip',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.ms-excel' => 'xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx'];

$ext = $imageExt[$mime] ?? $fileExt[$mime] ?? null;
if (!$ext) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'That file type is not allowed']);
    exit;
}

$directory = __DIR__ . '/../../uploads/attachments';
if (!is_dir($directory) && !mkdir($directory, 0750, true)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not create attachment folder']);
    exit;
}

$filename = bin2hex(random_bytes(16)) . '.' . $ext;
if (!move_uploaded_file($_FILES['file']['tmp_name'], $directory . '/' . $filename)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not save attachment']);
    exit;
}

$url = 'uploads/attachments/' . $filename;
$type = $isImage ? 'image' : 'file';
$originalName = basename($_FILES['file']['name']);
$text = $caption !== '' ? $caption : ($isImage ? '📷 Photo' : '📎 ' . $originalName);

$stmt = $conn->prepare('INSERT INTO messages (project_id, user_id, client_id, sender_label, message, attachment_url, attachment_type, is_ai) VALUES (?, ?, ?, ?, ?, ?, ?, 0)');
$stmt->bind_param('iisssss', $projectId, $user['id'], $clientId, $user['email'], $text, $url, $type);
$stmt->execute();
$messageId = $stmt->insert_id;
$stmt->close();

echo json_encode(['success' => true, 'id' => $messageId, 'attachment_url' => $url, 'attachment_type' => $type, 'file_name' => $originalName]);
