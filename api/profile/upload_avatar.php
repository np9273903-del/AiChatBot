<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');
$user = require_auth_api();
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK || $_FILES['avatar']['size'] > 5 * 1024 * 1024) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Photo must be smaller than 5 MB']); exit; }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['avatar']['tmp_name']);
$extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
if (!isset($extensions[$mime])) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Only JPG, PNG, GIF, and WEBP photos are allowed']); exit; }
$directory = __DIR__ . '/../../uploads/avatars';
if (!is_dir($directory) && !mkdir($directory, 0750, true)) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Could not create avatar folder']); exit; }
$filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $directory . '/' . $filename)) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Could not save profile photo']); exit; }
$avatarUrl = 'uploads/avatars/' . $filename;
$conn = get_db(); $stmt = $conn->prepare('UPDATE users SET avatar_url = ? WHERE id = ?'); $stmt->bind_param('si', $avatarUrl, $user['id']); $stmt->execute(); $stmt->close();
echo json_encode(['success'=>true,'avatar_url'=>$avatarUrl]);
